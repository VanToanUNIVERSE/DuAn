<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ProgressService;
use App\Models\StudyPlan;
use App\Models\User;
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
        // Bảo mật: chỉ admin mới được xem dữ liệu của user khác
        $authId = Auth::id();
        if (!$authId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $requestedId = $request->input('user_id');
        if ($requestedId && (int) $requestedId !== $authId) {
            $authUser = User::find($authId);
            if (!$authUser || !$authUser->is_admin) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        }
        $userId = $requestedId ? (int) $requestedId : $authId;

        $progress = $this->progressService->evaluateProgress($userId);

        $activePlan = StudyPlan::where('user_id', $userId)->where('is_active', true)->first();

        // Tổng số kỳ mục tiêu
        $targetSems = 8;
        if ($activePlan?->target_semester_count) {
            $targetSems = $activePlan->target_semester_count;
        } else {
            $user = User::find($userId);
            if ($user?->pref_graduation_semester) {
                $targetSems = $user->pref_graduation_semester;
            } elseif ($user?->pref_target_years) {
                $targetSems = $user->pref_target_years * 2;
            }
        }

        $completedSems   = $progress['completed_semesters'];
        $remainingCredits = $progress['remaining_credits'];
        $avgPerSem       = max(1, $progress['avg_credits_per_sem']);
        $gpa             = $progress['current_gpa'];
        $gpaTrend        = $progress['gpa_trend'] ?? 'stable';

        // ── 3 Kịch bản dự báo ──────────────────────────────────────────────

        // Lạc quan: tăng 25% tốc độ, tối đa 22 TC/kỳ
        $optimisticPerSem = min(22, $avgPerSem * 1.25);

        // Trung bình: giữ tốc độ hiện tại
        $averagePerSem = $avgPerSem;

        // Rủi ro: giảm 20% (rớt môn, bệnh, nghỉ học...)
        $pessimisticPerSem = max(8, $avgPerSem * 0.80);

        $optimistic  = $this->buildScenario('optimistic', $remainingCredits, $optimisticPerSem, $completedSems, $gpa, $gpaTrend);
        $average     = $this->buildScenario('average',    $remainingCredits, $averagePerSem,    $completedSems, $gpa, $gpaTrend);
        $pessimistic = $this->buildScenario('pessimistic', $remainingCredits, $pessimisticPerSem, $completedSems, $gpa, $gpaTrend);

        // ── Trạng thái hiện tại ─────────────────────────────────────────────
        $remainingSems        = max(1, $targetSems - $completedSems);
        $creditsPerSemNeeded  = round($remainingCredits / $remainingSems, 1);

        $status      = 'ON_TRACK';
        $message     = "Bạn đang học đúng tiến độ. Cần hoàn thành trung bình {$creditsPerSemNeeded} TC/kỳ.";
        $statusColor = '#10b981';

        if ($remainingCredits == 0) {
            $status      = 'GRADUATED';
            $message     = 'Chúc mừng! Bạn đã hoàn thành toàn bộ chương trình học.';
            $statusColor = '#8b5cf6';
        } elseif ($creditsPerSemNeeded > 25) {
            $status      = 'BEHIND';
            $message     = "Tiến độ đang chậm. Cần học {$creditsPerSemNeeded} TC/kỳ — vượt ngưỡng an toàn (25 TC). Nguy cơ trễ tốt nghiệp.";
            $statusColor = '#ef4444';
        } elseif ($creditsPerSemNeeded < 14 && $remainingSems > 1) {
            $status      = 'AHEAD';
            $message     = "Bạn đang vượt tiến độ! Chỉ cần {$creditsPerSemNeeded} TC/kỳ để tốt nghiệp trong {$remainingSems} học kỳ tới.";
            $statusColor = '#3b82f6';
        }

        // ── Cảnh báo rủi ro ─────────────────────────────────────────────────
        $risks = $this->detectRisks($progress, $gpa);

        return response()->json([
            'success' => true,
            'data'    => [
                // Tổng quan hiện tại
                'status'               => $status,
                'message'              => $message,
                'status_color'         => $statusColor,
                'target_semesters'     => $targetSems,
                'completed_semesters'  => $completedSems,
                'remaining_semesters'  => $remainingSems,
                'remaining_credits'    => $remainingCredits,
                'credits_per_sem_needed' => $creditsPerSemNeeded,
                'gpa_trend'            => $gpaTrend,
                'current_gpa'          => $gpa,

                // 3 kịch bản
                'scenarios' => [
                    'optimistic'  => $optimistic,
                    'average'     => $average,
                    'pessimistic' => $pessimistic,
                ],

                // Cảnh báo rủi ro
                'risks' => $risks,
            ],
        ]);
    }

    /**
     * Xây dựng 1 kịch bản dự báo tốt nghiệp
     */
    private function buildScenario(
        string $type,
        int $remainingCredits,
        float $perSem,
        int $completedSems,
        float $gpa,
        string $gpaTrend
    ): array {
        $semsNeeded  = $remainingCredits > 0 ? (int) ceil($remainingCredits / max(1, $perSem)) : 0;
        $gradSem     = $completedSems + $semsNeeded;

        // Tính năm và học kỳ tốt nghiệp dự kiến (bắt đầu từ năm hiện tại)
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');
        // Ước tính: mỗi HK = 6 tháng, HK1 bắt đầu tháng 9, HK2 bắt đầu tháng 2
        $monthsFromNow  = $semsNeeded * 6;
        $gradMonth      = ($currentMonth + $monthsFromNow - 1) % 12 + 1;
        $gradYear       = $currentYear + (int) floor(($currentMonth + $monthsFromNow - 1) / 12);
        $gradSemName    = ($gradMonth >= 2 && $gradMonth <= 7) ? 'HK2' : 'HK1';

        $labels = [
            'optimistic'  => 'Lạc quan',
            'average'     => 'Trung bình',
            'pessimistic' => 'Rủi ro',
        ];

        $colors = [
            'optimistic'  => '#10b981',
            'average'     => '#3b82f6',
            'pessimistic' => '#ef4444',
        ];

        // Dự báo GPA cuối khoá dựa trên xu hướng
        $projectedGpa = $this->projectGpa($gpa, $type, $gpaTrend);

        return [
            'type'             => $type,
            'label'            => $labels[$type],
            'color'            => $colors[$type],
            'sems_needed'      => $semsNeeded,
            'grad_semester'    => $gradSem,
            'grad_label'       => "{$gradSemName}/{$gradYear}",
            'credits_per_sem'  => round($perSem, 1),
            'projected_gpa'    => $projectedGpa,
            'description'      => $this->buildScenarioDescription($type, $semsNeeded, round($perSem, 1), $gradSemName, $gradYear),
        ];
    }

    /**
     * Dự báo GPA cuối khoá theo kịch bản
     */
    private function projectGpa(float $currentGpa, string $type, string $trend): float
    {
        if ($currentGpa <= 0) return 0;

        $delta = match ($type) {
            'optimistic'  => ($trend === 'improving') ? 0.5 : 0.2,
            'pessimistic' => ($trend === 'declining') ? -0.5 : -0.2,
            default       => ($trend === 'improving') ? 0.1 : (($trend === 'declining') ? -0.1 : 0),
        };

        return round(max(0, min(10, $currentGpa + $delta)), 2);
    }

    /**
     * Mô tả ngắn cho từng kịch bản
     */
    private function buildScenarioDescription(string $type, int $sems, float $perSem, string $semName, int $year): string
    {
        return match ($type) {
            'optimistic'  => "Nếu tăng tốc lên {$perSem} TC/kỳ, bạn có thể tốt nghiệp vào {$semName}/{$year} (còn {$sems} học kỳ).",
            'pessimistic' => "Nếu gặp khó khăn (~{$perSem} TC/kỳ), nguy cơ tốt nghiệp muộn nhất {$semName}/{$year} (còn {$sems} học kỳ).",
            default       => "Với tốc độ hiện tại ({$perSem} TC/kỳ), dự kiến tốt nghiệp {$semName}/{$year} (còn {$sems} học kỳ).",
        };
    }

    /**
     * Phát hiện rủi ro học vụ
     */
    private function detectRisks(array $progress, float $gpa): array
    {
        $risks = [];

        if ($gpa > 0 && $gpa < 3.5) {
            $risks[] = [
                'type'    => 'dismissal_risk',
                'level'   => 'danger',
                'message' => 'GPA dưới 3.5/10 — nguy cơ bị buộc thôi học. Liên hệ cố vấn học tập ngay.',
            ];
        } elseif ($gpa > 0 && $gpa < 5.0) {
            $risks[] = [
                'type'    => 'low_gpa',
                'level'   => 'warning',
                'message' => "GPA tích lũy {$gpa}/10 — dưới mức an toàn 5.0. Cần cải thiện kết quả học tập.",
            ];
        }

        if ($progress['failed_subjects_count'] >= 5) {
            $risks[] = [
                'type'    => 'heavy_debt',
                'level'   => 'danger',
                'message' => "Đang nợ {$progress['failed_subjects_count']} môn — ảnh hưởng nghiêm trọng đến tiến độ tốt nghiệp.",
            ];
        } elseif ($progress['failed_subjects_count'] >= 3) {
            $risks[] = [
                'type'    => 'subject_debt',
                'level'   => 'warning',
                'message' => "Đang nợ {$progress['failed_subjects_count']} môn. Cần ưu tiên học lại để không ảnh hưởng tiến độ.",
            ];
        }

        $neededPerSem = $progress['needed_credits_per_sem'] ?? 0;
        if ($neededPerSem > 25) {
            $risks[] = [
                'type'    => 'overload_risk',
                'level'   => 'danger',
                'message' => "Cần {$neededPerSem} TC/kỳ để tốt nghiệp đúng hạn — vượt ngưỡng an toàn. Nguy cơ trễ tốt nghiệp cao.",
            ];
        }

        if (($progress['gpa_trend'] ?? '') === 'declining' && $gpa < 6.0) {
            $risks[] = [
                'type'    => 'declining_trend',
                'level'   => 'warning',
                'message' => 'GPA đang có xu hướng giảm liên tiếp. Cần điều chỉnh phương pháp học ngay.',
            ];
        }

        return $risks;
    }
}
