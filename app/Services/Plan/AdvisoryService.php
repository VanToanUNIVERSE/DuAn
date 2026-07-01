<?php

namespace App\Services\Plan;

use App\Models\SemesterHistory;
use App\Models\StudyPlan;
use App\Models\User;
use App\Services\AcademicEvaluationService;
use App\Services\StudyPlanService;

class AdvisoryService
{
    public function __construct(
        protected StudyPlanService $planService,
        protected AcademicEvaluationService $academicEvaluation
    ) {}

    /**
     * Tính toán tư vấn điều chỉnh TC/kỳ dựa trên lịch sử GPA.
     *
     * @return array{recommend: string, reason: string, current_tc_per_sem: int,
     *               recommended_tc_per_sem: int, new_graduation_estimate: ?int, semesters_delta: int}
     */
    public function computeAdvisory(StudyPlan $plan, int $userId): array
    {
        $histories = SemesterHistory::where('user_id', $userId)->orderBy('semester_number')->get();
        if ($histories->isEmpty()) {
            return [
                'recommend'               => 'maintain',
                'reason'                  => 'Chưa có dữ liệu học kỳ.',
                'current_tc_per_sem'      => $plan->tc_per_sem,
                'recommended_tc_per_sem'  => $plan->tc_per_sem,
                'new_graduation_estimate' => null,
                'semesters_delta'         => 0,
            ];
        }

        $currentTarget = $this->configuredTarget($plan, $userId);
        $currentSem    = ((int) $histories->max('semester_number')) + 1;
        $evaluation    = $this->academicEvaluation->evaluate(
            $userId,
            $plan->mode ?? 'normal',
            $currentTarget,
            $currentSem
        );

        $recommendedTc = (int) ($evaluation['recommended_tc_per_sem'] ?? $plan->tc_per_sem ?? 18);
        $newTarget      = $evaluation['status'] === 'KEEP'
            ? null
            : (int) ($evaluation['suggested_sems'] ?? $currentTarget);

        $recommend = match ($evaluation['status']) {
            'SPEED_UP'          => 'increase',
            'REPLAN', 'REDUCE'  => 'decrease',
            default             => 'maintain',
        };

        return [
            'recommend'               => $recommend,
            'reason'                  => $evaluation['message'],
            'current_tc_per_sem'      => (int) ($plan->tc_per_sem ?? 18),
            'recommended_tc_per_sem'  => $recommendedTc,
            'new_graduation_estimate' => $newTarget,
            'semesters_delta'         => $newTarget === null ? 0 : $newTarget - $currentTarget,
            'evaluation'              => $evaluation,
        ];
    }

    /**
     * Áp dụng tư vấn: cập nhật tc_per_sem và tùy chọn rải lại môn học.
     */
    public function applyAdvisory(
        StudyPlan $plan,
        int $userId,
        int $newTcPerSem,
        bool $redistribute,
        ?int $estimatedSemesters = null
    ): StudyPlan
    {
        $newTcPerSem = max(12, min(22, $newTcPerSem));
        $mode        = $newTcPerSem >= 20 ? 'fast' : ($newTcPerSem <= 14 ? 'slow' : 'normal');

        $lastHistory = SemesterHistory::where('user_id', $userId)->max('semester_number') ?? 0;
        $currentSem  = max(1, $lastHistory + 1);

        $configuredTarget = $this->configuredTarget($plan, $userId);
        $planningHorizon  = null;
        $update = [
            'tc_per_sem'       => $newTcPerSem,
            'mode'             => $mode,
            // Mục tiêu là cấu hình của sinh viên, không phải số kỳ dự kiến sau tư vấn.
            // Gán lại trường này cũng tự sửa các kế hoạch từng bị luồng cũ ghi đè.
            'target_semesters' => $configuredTarget,
        ];

        if ($redistribute) {
            // Số kỳ dự kiến chỉ là mốc dùng để rải lịch. Nó tuyệt đối không được ghi đè
            // mục tiêu tốt nghiệp mà sinh viên đã chọn trong cấu hình.
            $plan->loadMissing('semesters.subjects.subject');
            $remainingCredits = 0;
            foreach ($plan->semesters as $sem) {
                if ($sem->semester_index < $currentSem) continue;   // các kỳ đã xong: giữ nguyên
                foreach ($sem->subjects as $ss) {
                    $remainingCredits += (int) ($ss->subject->credits ?? 3);
                }
            }
            $neededSems = max(1, (int) ceil($remainingCredits / $newTcPerSem));
            $calculatedTarget = ($currentSem - 1) + $neededSems;
            $planningHorizon = max(6, min(10, $estimatedSemesters ?? $calculatedTarget));
        }

        $plan->update($update);
        User::whereKey($userId)->update([
            'pref_graduation_semester' => $configuredTarget,
        ]);

        if ($redistribute) {
            return $this->planService->redistributeFrom(
                $plan->fresh(),
                $currentSem,
                [],
                $planningHorizon
            );
        }

        return $plan->load('semesters.subjects.subject');
    }

    /**
     * Mục tiêu cấu hình là nguồn dữ liệu chuẩn. Fallback về kế hoạch để hỗ trợ
     * tài khoản cũ chưa có pref_graduation_semester.
     */
    private function configuredTarget(StudyPlan $plan, int $userId): int
    {
        $preference = User::whereKey($userId)->value('pref_graduation_semester');
        if ($preference) {
            return max(6, min(10, (int) $preference));
        }

        // Tự sửa dữ liệu do phiên bản cũ gây ra: tên kế hoạch được tạo theo mục tiêu
        // ban đầu (ví dụ "Kế hoạch tốt nghiệp 8 kỳ"), trong khi target_semesters
        // có thể đã bị tư vấn ghi đè thành con số dự kiến 7.
        if (preg_match('/(\d+)\s*kỳ/ui', (string) $plan->name, $matches)) {
            $legacyTarget = (int) $matches[1];
            if ($legacyTarget >= 6 && $legacyTarget <= 10) {
                return $legacyTarget;
            }
        }

        $target = (int) ($plan->target_semesters ?: $plan->target_semester_count ?: 8);

        return max(6, min(10, $target));
    }
}
