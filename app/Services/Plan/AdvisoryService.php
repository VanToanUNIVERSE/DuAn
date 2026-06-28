<?php

namespace App\Services\Plan;

use App\Models\SemesterHistory;
use App\Models\StudyPlan;
use App\Services\ProgressService;
use App\Services\StudyPlanService;

class AdvisoryService
{
    public function __construct(
        protected PlanDataService $dataService,
        protected StudyPlanService $planService,
        protected ProgressService $progressService
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

        [$allSubjects, $passedIds] = $this->dataService->loadPlanningData($userId);
        $completedSems    = $histories->count();
        $remainingSems    = max(1, $plan->target_semesters - $completedSems);
        $remainingCredits = $allSubjects
            ->reject(fn($s) => in_array($s->id, $passedIds))
            ->sum(fn($s) => (int)($s->credits ?? 3));

        // GPA tích lũy chuẩn (cùng nguồn ProgressService với modal kết quả học kỳ →
        // tránh hiện 2 con số GPA khác nhau ở 2 thông báo).
        $progress     = $this->progressService->evaluateProgress($userId);
        $gpa          = round((float) $progress['current_gpa'], 2);
        $effectiveGpa = $gpa;
        $currentTc    = $plan->tc_per_sem;

        $neededTc   = $remainingSems > 0 ? (int) ceil($remainingCredits / $remainingSems) : $currentTc;
        $gpaContext = "GPA {$gpa}";

        if ($effectiveGpa >= 7.0 && $currentTc < 22) {
            $newTc   = min(22, $currentTc + max(2, (int)($currentTc * 0.15)));
            $newSems = (int) ceil($remainingCredits / $newTc) + $completedSems;
            $delta   = $plan->target_semesters - $newSems;
            $earlyBy = $delta > 0 ? " Dự kiến tốt nghiệp sớm hơn {$delta} học kỳ so với mục tiêu." : '';
            return [
                'recommend'               => 'increase',
                'reason'                  => "{$gpaContext} — học lực tốt, bạn hoàn toàn có thể tăng tải lên {$newTc} TC/kỳ.{$earlyBy}",
                'current_tc_per_sem'      => $currentTc,
                'recommended_tc_per_sem'  => $newTc,
                'new_graduation_estimate' => $newSems,
                'semesters_delta'         => $delta,
            ];
        }

        if ($effectiveGpa < 5.5 || $neededTc > $currentTc * 1.15) {
            $newTc    = max(12, $currentTc - max(2, (int)($currentTc * 0.15)));
            $newSems  = (int) ceil($remainingCredits / $newTc) + $completedSems;
            $delta    = $newSems - $plan->target_semesters;
            $tradeOff = $delta > 0
                ? " Tuy nhiên, điều này sẽ khiến bạn tốt nghiệp trễ hơn mục tiêu ban đầu {$delta} học kỳ (dự kiến học kỳ {$newSems})."
                : '';
            $trigger  = $effectiveGpa < 5.5
                ? "{$gpaContext} — học lực yếu"
                : "{$gpaContext} — cần đến {$neededTc} TC/kỳ để đúng tiến độ trong khi hiện tại chỉ có {$currentTc} TC/kỳ";
            return [
                'recommend'               => 'decrease',
                'reason'                  => "{$trigger}. Giảm xuống {$newTc} TC/kỳ giúp tránh nguy cơ học lại.{$tradeOff}",
                'current_tc_per_sem'      => $currentTc,
                'recommended_tc_per_sem'  => $newTc,
                'new_graduation_estimate' => $newSems,
                'semesters_delta'         => $delta,
            ];
        }

        return [
            'recommend'               => 'maintain',
            'reason'                  => "{$gpaContext} — tiến độ ổn định, phù hợp với kế hoạch hiện tại. Tiếp tục duy trì.",
            'current_tc_per_sem'      => $currentTc,
            'recommended_tc_per_sem'  => $currentTc,
            'new_graduation_estimate' => null,
            'semesters_delta'         => 0,
        ];
    }

    /**
     * Áp dụng tư vấn: cập nhật tc_per_sem và tùy chọn rải lại môn học.
     */
    public function applyAdvisory(StudyPlan $plan, int $userId, int $newTcPerSem, bool $redistribute): StudyPlan
    {
        $newTcPerSem = max(12, min(22, $newTcPerSem));
        $mode        = $newTcPerSem >= 20 ? 'fast' : ($newTcPerSem <= 14 ? 'slow' : 'normal');

        $plan->update(['tc_per_sem' => $newTcPerSem, 'mode' => $mode]);

        if ($redistribute) {
            $lastHistory = SemesterHistory::where('user_id', $userId)->max('semester_number') ?? 0;
            $currentSem  = max(1, $lastHistory + 1);
            return $this->planService->redistributeFrom($plan->fresh(), $currentSem);
        }

        return $plan->load('semesters.subjects.subject');
    }
}
