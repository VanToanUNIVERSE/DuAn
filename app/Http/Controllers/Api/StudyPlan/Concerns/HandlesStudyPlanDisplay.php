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
        $homeSemesters     = $this->loadElectiveGroupHomeSemesters($userId);
        $emittedGroupIds   = [];

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
                    $emittedGroupIds[$eg->elective_group_id]   = true;
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

        // ── Nhóm tự chọn đã bị BỎ CHỌN HẾT (0 môn trong kế hoạch) ───────────────
        // Vẫn phát ra khung nhóm tại "học kỳ nhà" (theo khung chương trình) để nhóm
        // không biến mất khỏi lịch và sinh viên có thể chọn lại. Nhóm tự chọn là
        // yêu cầu tốt nghiệp bắt buộc nên không được phép ẩn đi khi trống.
        $currentSem = $this->detectCurrentSemester($plan, $userId);
        foreach ($allElectiveGroups as $gid => $groupData) {
            if (isset($emittedGroupIds[$gid])) continue;

            // Kẹp về ít nhất học kỳ hiện tại để khung nhóm không rơi vào kỳ đã qua
            // (kỳ đã qua bị khóa, không bấm chọn lại được).
            $homeSem   = max($homeSemesters[$gid] ?? $currentSem, $currentSem);
            $targetSem = $plan->semesters->firstWhere('semester_index', $homeSem)
                ?? $plan->semesters->sortByDesc('semester_index')->first();
            if (!$targetSem) continue;

            $groups   = $targetSem->elective_groups ?? [];
            $groups[] = [
                'id'               => $gid,
                'name'             => $groupData['name'],
                'required_credits' => $groupData['required_credits'],
                'options'          => array_map(fn($m) => [
                    'id'       => $m->id,
                    'name'     => $m->name,
                    'code'     => $m->subject_code ?? '',
                    'credits'  => (int) ($m->credits ?? 0),
                    'selected' => false,
                ], $groupData['subjects']),
            ];
            $targetSem->setAttribute('elective_groups', $groups);
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

    /**
     * Học kỳ "nhà" của mỗi nhóm tự chọn theo khung chương trình (semester nhỏ nhất
     * mà nhóm được gán). Dùng để hiển thị lại nhóm khi nó đã bị bỏ chọn hết môn.
     *
     * @return array<int,int>  [elective_group_id => semester_index]
     */
    private function loadElectiveGroupHomeSemesters(int $userId): array
    {
        $frameworkId = $this->resolveFrameworkId($userId);
        if (!$frameworkId) return [];

        $home = [];
        CurriculumSubject::where('curriculum_framework_id', $frameworkId)
            ->whereNotNull('elective_group_id')
            ->with('semester')
            ->get()
            ->each(function ($cs) use (&$home) {
                $semIdx = (int) ($cs->semester?->name ?? 0);
                $gid    = $cs->elective_group_id;
                if ($semIdx > 0 && (!isset($home[$gid]) || $semIdx < $home[$gid])) {
                    $home[$gid] = $semIdx;
                }
            });

        return $home;
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
