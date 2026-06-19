<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ProgressService;
use App\Models\StudyPlan;
use Illuminate\Support\Facades\Auth;

class GraduationForecastController extends Controller
{
    protected $progressService;

    public function __construct(ProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    public function index(Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized or missing user_id'], 401);
        }

        $progress = $this->progressService->evaluateProgress($userId);

        $activePlan = StudyPlan::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        // Target semesters: từ DB User preference hoặc Active Plan hoặc default = 8
        $targetSems = 8;
        if ($activePlan && $activePlan->target_semester_count) {
            $targetSems = $activePlan->target_semester_count;
        } else {
            $user = \App\Models\User::find($userId);
            if ($user && $user->pref_graduation_semester) {
                $targetSems = $user->pref_graduation_semester;
            } elseif ($user && $user->pref_target_years) {
                $targetSems = $user->pref_target_years * 2;
            }
        }

        $completedSems = $progress['completed_semesters'];
        $remainingSems = max(1, $targetSems - $completedSems);
        $remainingCredits = $progress['remaining_credits'];
        $creditsPerSemNeeded = round($remainingCredits / $remainingSems, 1);

        $status = 'ON_TRACK';
        $message = "Bạn đang học đúng tiến độ. Cần hoàn thành trung bình {$creditsPerSemNeeded} TC/kỳ.";
        $statusColor = '#10b981'; // Green

        if ($creditsPerSemNeeded > 25) {
            $status = 'BEHIND';
            $message = "Tiến độ đang chậm. Việc phải học {$creditsPerSemNeeded} TC/kỳ là quá tải (vượt ngưỡng an toàn 25 TC). Nguy cơ trễ tốt nghiệp.";
            $statusColor = '#ef4444'; // Red
        } elseif ($creditsPerSemNeeded < 14 && $remainingSems > 1) {
            $status = 'AHEAD';
            $message = "Bạn đang vượt tiến độ! Chỉ cần {$creditsPerSemNeeded} TC/kỳ để tốt nghiệp trong {$remainingSems} học kỳ tới.";
            $statusColor = '#3b82f6'; // Blue
        }

        if ($remainingCredits == 0) {
            $status = 'GRADUATED';
            $message = "Chúc mừng! Bạn đã hoàn thành toàn bộ chương trình học.";
            $statusColor = '#8b5cf6'; // Purple
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $status,
                'message' => $message,
                'status_color' => $statusColor,
                'target_semesters' => $targetSems,
                'completed_semesters' => $completedSems,
                'remaining_semesters' => $remainingSems,
                'remaining_credits' => $remainingCredits,
                'credits_per_sem_needed' => $creditsPerSemNeeded,
                'gpa_trend' => $progress['gpa_trend'] ?? 'stable',
            ]
        ]);
    }
}
