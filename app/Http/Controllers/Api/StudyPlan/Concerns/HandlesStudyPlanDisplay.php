<?php

namespace App\Http\Controllers\Api\StudyPlan\Concerns;

use App\Models\CurriculumSubject;
use App\Models\SemesterHistory;
use App\Models\StudyPlan;
use App\Models\Subject;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Models\UserGrade;
use Illuminate\Support\Collection;

trait HandlesStudyPlanDisplay
{
    private function attachGrades(StudyPlan $plan, int $userId): StudyPlan
    {
        $plan->loadMissing(
            'semesters.subjects.subject.prerequisites',
            'semesters.subjects.subject.corequisites',
            'semesters.subjects.subject.relatedRelations'
        );

        $userGrades    = UserGrade::where('user_id', $userId)->pluck('grade', 'subject_id')->toArray();
        $passedIds     = array_keys(array_filter($userGrades, fn($g) => $g !== null && $g >= 5.0));
        $allSubjectsMap = Subject::all()->keyBy('id');

        $electiveGroupMap  = $this->loadElectiveGroupMap($userId, $plan);
        $allElectiveGroups = $this->loadAllElectiveGroupSubjects($userId);

        foreach ($plan->semesters as $sem) {
            foreach ($sem->subjects as $ss) {
                if (!$ss->subject) continue;

                $ss->grade        = $ss->subject_grade;
                $ss->is_completed = $ss->grade !== null && $ss->grade >= 5.0;
                $ss->is_failed    = $ss->grade !== null && $ss->grade < 5.0;

                $ss->subject->prerequisites_info = $this->buildPrereqDetails($ss->subject, $passedIds, $allSubjectsMap);

                $dependentCount = $ss->subject->relatedRelations->where('type', 'prerequisite')->count();
                $ss->is_highly_recommended = $dependentCount >= 2
                    || in_array($ss->subject->requirement_type, ['completed_basic', 'completed_major']);

                $eg = $electiveGroupMap[$ss->subject_id] ?? null;
                $ss->subject->elective_group_id         = $eg?->elective_group_id;
                $ss->subject->elective_group_name       = $eg?->electiveGroup?->name;
                $ss->subject->elective_required_credits = $eg?->electiveGroup?->required_credits;
            }

            $semGroupPlanIds = [];
            foreach ($sem->subjects as $ss) {
                $eg = $electiveGroupMap[$ss->subject_id] ?? null;
                if ($eg?->elective_group_id) {
                    $semGroupPlanIds[$eg->elective_group_id][] = $ss->subject_id;
                }
            }

            $semElectiveGroups = [];
            foreach ($semGroupPlanIds as $gid => $planSubjectIds) {
                $groupData = $allElectiveGroups[$gid] ?? null;
                if (!$groupData) continue;

                $semElectiveGroups[] = [
                    'id'               => $gid,
                    'name'             => $groupData['name'],
                    'required_credits' => $groupData['required_credits'],
                    'options'          => array_map(fn($m) => [
                        'id'       => $m->id,
                        'name'     => $m->name,
                        'code'     => $m->subject_code ?? '',
                        'credits'  => (int) ($m->credits ?? 0),
                        'selected' => in_array($m->id, $planSubjectIds),
                    ], $groupData['subjects']),
                ];
            }

            $sem->setAttribute('elective_groups', $semElectiveGroups);
        }

        return $plan;
    }

    private function loadElectiveGroupMap(int $userId, StudyPlan $plan): array
    {
        $frameworkId = $this->resolveFrameworkId($userId);
        if (!$frameworkId) return [];

        $subjectIds = $plan->semesters
            ->flatMap(fn($s) => $s->subjects->pluck('subject_id'))
            ->unique()->toArray();

        return CurriculumSubject::where('curriculum_framework_id', $frameworkId)
            ->whereIn('subject_id', $subjectIds)
            ->whereNotNull('elective_group_id')
            ->with('electiveGroup')
            ->get()
            ->keyBy('subject_id')
            ->all();
    }

    private function loadAllElectiveGroupSubjects(int $userId): array
    {
        $frameworkId = $this->resolveFrameworkId($userId);
        if (!$frameworkId) return [];

        $groups = [];
        \App\Models\ElectiveGroup::where('curriculum_framework_id', $frameworkId)
            ->with('subjects:id,name,subject_code,credits')
            ->each(function ($eg) use (&$groups) {
                $groups[$eg->id] = [
                    'id'               => $eg->id,
                    'name'             => $eg->name,
                    'required_credits' => $eg->required_credits,
                    'subjects'         => $eg->subjects->all(),
                ];
            });

        return $groups;
    }

    private function resolveFrameworkId(int $userId): ?int
    {
        $user = User::find($userId);
        if (!$user?->pref_academic_year || !$user?->pref_program_type) return null;

        $program = TrainingProgram::where('academic_year', $user->pref_academic_year)
            ->where('program_type', $user->pref_program_type)
            ->first();

        return $program?->curriculumFrameworks()->first()?->id;
    }

    private function buildPrereqDetails(object $subject, array $passedIds, Collection $allSubjectsMap): array
    {
        $details = [];

        foreach ($subject->prerequisites ?? [] as $prereq) {
            $details[$prereq->id] = [
                'id'        => $prereq->id,
                'name'      => $prereq->name,
                'is_passed' => in_array($prereq->id, $passedIds),
                'type'      => 'explicit',
            ];
        }

        foreach ($subject->corequisites ?? [] as $coreq) {
            $details['co_' . $coreq->id] = [
                'id'        => $coreq->id,
                'name'      => $coreq->name,
                'is_passed' => in_array($coreq->id, $passedIds),
                'type'      => 'corequisite',
            ];
        }

        $req = $subject->requirement_type ?? null;
        if ($req && $req !== 'none') {
            $groupLabel = match ($req) {
                'completed_basic'       => 'Đại cương',
                'completed_major'       => 'Cơ sở ngành',
                'completed_specialized' => 'Chuyên ngành',
                'completed_all'         => 'Toàn bộ',
                default                 => $req,
            };
            $details["_group_{$req}"] = [
                'id'        => null,
                'name'      => "Hoàn thành khối {$groupLabel}",
                'is_passed' => false,
                'type'      => 'group',
            ];
        }

        return array_values($details);
    }

    private function syncUserGrade(int $userId, int $subjectId, StudyPlan $plan): void
    {
        $grade = null;
        foreach ($plan->semesters as $sem) {
            foreach ($sem->subjects as $ss) {
                if ($ss->subject_id === $subjectId && $ss->subject_grade !== null) {
                    $grade = $ss->subject_grade;
                    break 2;
                }
            }
        }

        if ($grade === null) {
            UserGrade::where('user_id', $userId)->where('subject_id', $subjectId)->delete();
            return;
        }

        UserGrade::updateOrCreate(
            ['user_id' => $userId, 'subject_id' => $subjectId],
            ['grade' => $grade, 'status' => $grade >= 5.0 ? 'pass' : 'fail']
        );
    }

    private function detectCurrentSemester(StudyPlan $plan, int $userId): int
    {
        $lastHistory = SemesterHistory::where('user_id', $userId)->max('semester_number');
        if ($lastHistory) return (int) $lastHistory + 1;

        foreach ($plan->semesters->sortBy('semester_index') as $sem) {
            if ($sem->subjects->some(fn($ss) => !$ss->is_completed && !$ss->is_retake)) {
                return (int) $sem->semester_index;
            }
        }

        return 1;
    }
}
