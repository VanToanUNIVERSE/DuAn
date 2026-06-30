<?php

namespace App\Http\Controllers\Api\StudyPlan;

use App\Http\Controllers\Controller;
use App\Models\StudyPlan;
use App\Models\StudyPlanRevision;
use Illuminate\Support\Facades\Auth;

class StudyPlanRevisionController extends Controller
{
    // GET /api/v1/study-plans/{id}/revisions
    public function index($id)
    {
        $userId = Auth::id();

        // Đảm bảo kế hoạch thuộc về user hiện tại
        StudyPlan::where('id', $id)->where('user_id', $userId)->firstOrFail();

        $revisions = StudyPlanRevision::where('study_plan_id', $id)
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($rev) {
                $old = $rev->old_plan_data ?? [];
                $new = $rev->new_plan_data ?? [];

                return [
                    'id'         => $rev->id,
                    'reason'     => $rev->reason,
                    'gpa'        => $rev->gpa_at_revision,
                    'created_at' => optional($rev->created_at)->format('d/m/Y H:i'),
                    'summary'    => $this->summarize($old, $new),
                    'old'        => $old,
                    'new'        => $new,
                ];
            });

        return response()->json(['success' => true, 'data' => $revisions]);
    }

    /**
     * Tóm tắt khác biệt giữa snapshot cũ và mới (để hiển thị nhanh ở danh sách).
     */
    private function summarize(array $old, array $new): array
    {
        $sumCredits = fn(array $snap) => collect($snap)
            ->flatMap(fn($s) => $s['subjects'] ?? [])
            ->sum('credits');

        $flatIds = fn(array $snap) => collect($snap)
            ->flatMap(fn($s) => collect($s['subjects'] ?? [])->pluck('id'))
            ->all();

        $oldIds = $flatIds($old);
        $newIds = $flatIds($new);

        // Vị trí (kỳ) của từng môn — dùng để đếm số môn đổi học kỳ
        $posOld = [];
        foreach ($old as $s) {
            foreach ($s['subjects'] ?? [] as $sub) {
                $posOld[$sub['id']] = $s['index'];
            }
        }
        $posNew = [];
        foreach ($new as $s) {
            foreach ($s['subjects'] ?? [] as $sub) {
                $posNew[$sub['id']] = $s['index'];
            }
        }

        $moved = 0;
        foreach ($posNew as $sid => $idx) {
            if (isset($posOld[$sid]) && $posOld[$sid] !== $idx) {
                $moved++;
            }
        }

        return [
            'from_sems'    => count($old),
            'to_sems'      => count($new),
            'from_credits' => (int) $sumCredits($old),
            'to_credits'   => (int) $sumCredits($new),
            'added'        => count(array_diff(array_unique($newIds), array_unique($oldIds))),
            'removed'      => count(array_diff(array_unique($oldIds), array_unique($newIds))),
            'moved'        => $moved,
        ];
    }
}
