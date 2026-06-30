<?php

namespace App\Services\Plan;

use App\Models\StudyPlan;
use App\Models\StudyPlanRevision;
use App\Services\ProgressService;

/**
 * Ghi & truy xuất LỊCH SỬ THAY ĐỔI KẾ HOẠCH học tập.
 *
 * Mỗi lần kế hoạch thay đổi lớn (áp dụng tư vấn, đổi mục tiêu, tạo lại, xử lý
 * rớt môn) ta lưu một bản ghi gồm snapshot TRƯỚC/SAU + lý do + GPA tại thời điểm.
 */
class PlanRevisionService
{
    public function __construct(protected ProgressService $progressService) {}

    /**
     * Chụp ảnh cấu trúc kế hoạch: từng học kỳ kèm danh sách môn.
     */
    public function snapshot(?StudyPlan $plan): array
    {
        if (!$plan) {
            return [];
        }

        $plan->loadMissing('semesters.subjects.subject');

        return $plan->semesters
            ->sortBy('semester_index')
            ->map(fn($sem) => [
                'index'    => (int) $sem->semester_index,
                'subjects' => $sem->subjects->map(fn($sps) => [
                    'id'        => (int) $sps->subject_id,
                    'name'      => $sps->subject->name ?? '—',
                    'credits'   => (int) ($sps->subject->credits ?? 0),
                    'is_retake' => (bool) $sps->is_retake,
                ])->values()->all(),
            ])->values()->all();
    }

    /**
     * Ghi một phiên bản nếu cấu trúc môn/kỳ THỰC SỰ khác so với snapshot cũ.
     *
     * @return StudyPlanRevision|null  null nếu không có thay đổi → không tạo bản ghi rác.
     */
    public function record(StudyPlan $plan, string $reason, array $oldSnapshot): ?StudyPlanRevision
    {
        $newSnapshot = $this->snapshot($plan->fresh());

        if ($this->fingerprint($oldSnapshot) === $this->fingerprint($newSnapshot)) {
            return null;
        }

        $gpa = round((float) ($this->progressService->evaluateProgress($plan->user_id)['current_gpa'] ?? 0), 2);

        return StudyPlanRevision::create([
            'user_id'         => $plan->user_id,
            'study_plan_id'   => $plan->id,
            'gpa_at_revision' => $gpa,
            'reason'          => $reason,
            'old_plan_data'   => $oldSnapshot,
            'new_plan_data'   => $newSnapshot,
        ]);
    }

    /**
     * Dấu vân tay cấu trúc (môn theo từng kỳ) để phát hiện thay đổi.
     */
    private function fingerprint(array $snapshot): string
    {
        $map = [];
        foreach ($snapshot as $sem) {
            $ids = array_map(fn($s) => $s['id'], $sem['subjects'] ?? []);
            sort($ids);
            $map[$sem['index']] = $ids;
        }
        ksort($map);

        return json_encode($map);
    }
}
