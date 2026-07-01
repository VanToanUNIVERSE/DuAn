<?php

namespace App\Services\Plan;

use App\Models\CurriculumSubject;
use App\Models\ProgramGroup;
use App\Models\SemesterHistory;
use App\Models\Subject;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Models\UserGrade;
use Illuminate\Support\Collection;

class PlanDataService
{
    /**
     * Load toàn bộ dữ liệu cần thiết để lập kế hoạch.
     * @return array{Collection, int[], int[], int[]}
     */
    public function loadPlanningData(int $userId): array
    {
        $allUserGrades = UserGrade::where('user_id', $userId)->get()
            ->groupBy('subject_id')
            ->map(fn($g) => $g->sortByDesc('grade')->first());

        $passedIds = $allUserGrades
            ->filter(fn($g) => $g->grade >= 5.0 || in_array($g->status, ['pass', 'passed']))
            ->pluck('subject_id')->toArray();

        $failedIds = $allUserGrades
            ->filter(fn($g) => $g->grade !== null && $g->grade < 5.0
                && !in_array($g->status, ['pass', 'passed']))
            ->pluck('subject_id')->toArray();

        $allSubjects = $this->loadSubjectsForUser($userId);

        // Môn trong LỊCH SỬ học kỳ cũng phải tính vào passed/failed như UserGrade:
        // nếu không, môn đã đậu trong lịch sử (nhưng không có bản ghi UserGrade — vd sau
        // khi reset demo) sẽ bị coi là "chưa học" → xếp LẶP (trùng kỳ lịch sử + kỳ mới) và
        // nhóm tự chọn bị chọn DƯ (prune không đếm được TC đã đậu → giữ thêm môn thay thế).
        $historyItems = SemesterHistory::where('user_id', $userId)
            ->with('items')
            ->get()
            ->flatMap(fn($h) => $h->items);

        $historySubjectIds = $historyItems->pluck('subject_id')->unique()->toArray();

        $historyPassed = $historyItems
            ->filter(fn($it) => ($it->grade !== null && $it->grade >= 5.0)
                || in_array($it->status, ['pass', 'passed']))
            ->pluck('subject_id')->toArray();

        $historyFailed = $historyItems
            ->filter(fn($it) => $it->grade !== null && $it->grade < 5.0
                && !in_array($it->status, ['pass', 'passed']))
            ->pluck('subject_id')->toArray();

        $passedIds = array_values(array_unique(array_merge($passedIds, $historyPassed)));
        // Rớt = rớt ở bất kỳ đâu, nhưng nếu ĐÃ đậu ở nơi khác (học lại đạt) thì bỏ khỏi failed.
        $failedIds = array_values(array_diff(
            array_unique(array_merge($failedIds, $historyFailed)),
            $passedIds
        ));

        return [$allSubjects, $passedIds, $failedIds, $historySubjectIds];
    }

    /**
     * Load danh sách môn học theo chương trình khung của sinh viên.
     * Mỗi môn có thêm thuộc tính `assigned_semester_index`.
     */
    public function loadSubjectsForUser(int $userId): Collection
    {
        $user        = User::find($userId);
        $frameworkId = null;

        if ($user?->pref_academic_year && $user?->pref_program_type) {
            $program = TrainingProgram::where('academic_year', $user->pref_academic_year)
                ->where('program_type', $user->pref_program_type)
                ->first();
            if ($program) {
                $frameworkId = $program->curriculumFrameworks()->first()?->id;
            }
        }

        if (!$frameworkId) {
            $subjects = Subject::with(['prerequisites', 'corequisites', 'relatedRelations', 'skillGroup'])->get();
            $subjects->each(fn($s) => $s->assigned_semester_index = (int)($s->semester_id ?? 1));
            return $subjects;
        }

        return CurriculumSubject::where('curriculum_framework_id', $frameworkId)
            ->with(['subject.prerequisites', 'subject.corequisites', 'subject.relatedRelations', 'subject.skillGroup', 'semester', 'electiveGroup'])
            ->get()
            ->filter(fn($cs) => $cs->subject !== null)
            ->map(function ($cs) {
                $subject = $cs->subject;
                $subject->assigned_semester_index   = (int)($cs->semester?->name ?? $subject->semester_id ?? 1);
                $subject->elective_group_id         = $cs->elective_group_id;
                $subject->elective_required_credits = $cs->electiveGroup?->required_credits;
                $subject->elective_group_name       = $cs->electiveGroup?->name;
                return $subject;
            })
            ->unique('id')
            ->values();
    }

    /**
     * Lấy program_group_id cho từng loại nhóm (tra DB 1 lần).
     */
    public function resolveGroupIds(): array
    {
        return [
            'basic'       => ProgramGroup::where('name', 'like', '%Đại cương%')
                ->orWhere('name', 'like', '%Anh văn%')->pluck('id')->toArray(),
            'major'       => ProgramGroup::where('name', 'like', '%Cơ sở ngành%')->pluck('id')->toArray(),
            'specialized' => ProgramGroup::where('name', 'like', '%Chuyên ngành%')->pluck('id')->toArray(),
        ];
    }
}
