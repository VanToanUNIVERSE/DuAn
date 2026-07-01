<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SemesterHistory;
use App\Models\Warning;
use App\Services\ProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProgressController extends Controller
{
    protected $progressService;

    public function __construct(ProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    public function index(Request $request)
    {
        $userId = Auth::id();

        $progress = $this->progressService->evaluateProgress($userId);
        $warnings = $this->progressService->generateWarnings($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'progress' => $progress,
                'warnings' => $warnings,
            ]
        ]);
    }

    /**
     * GET /api/v1/gpa-trend
     * Trả về lịch sử GPA theo từng học kỳ để vẽ biểu đồ đường.
     */
    public function gpaTrend(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $histories = SemesterHistory::where('user_id', $userId)
            ->orderBy('semester_number')
            ->get(['semester_number', 'academic_year', 'gpa', 'passed_credits', 'total_credits']);

        if ($histories->isEmpty()) {
            return response()->json([
                'success'   => true,
                'semesters' => [],
                'gpas'      => [],
                'credits'   => [],
                'trend'     => 'no_data',
                'message'   => 'Chưa có lịch sử học kỳ nào được ghi nhận.',
            ]);
        }

        $labels  = [];
        $gpas    = [];
        $credits = [];

        foreach ($histories as $h) {
            $yearPart = $h->academic_year ? " ({$h->academic_year})" : '';
            $labels[] = "HK{$h->semester_number}{$yearPart}";
            $gpas[]   = round((float) $h->gpa, 2);
            $credits[] = (int) $h->passed_credits;
        }

        // Tính xu hướng: so sánh nửa đầu vs nửa sau
        $trend = 'stable';
        if (count($gpas) >= 4) {
            $mid    = (int) (count($gpas) / 2);
            $first  = array_sum(array_slice($gpas, 0, $mid)) / $mid;
            $second = array_sum(array_slice($gpas, $mid)) / (count($gpas) - $mid);
            if ($second > $first + 0.3) $trend = 'improving';
            elseif ($second < $first - 0.3) $trend = 'declining';
        } elseif (count($gpas) >= 2) {
            $diff = end($gpas) - $gpas[0];
            if ($diff > 0.5) $trend = 'improving';
            elseif ($diff < -0.5) $trend = 'declining';
        }

        $trendMessages = [
            'improving' => 'GPA của bạn đang có xu hướng cải thiện qua các học kỳ.',
            'declining' => 'GPA của bạn đang có xu hướng giảm — hãy chú ý hơn nhé.',
            'stable'    => 'GPA của bạn khá ổn định qua các học kỳ.',
            'no_data'   => 'Chưa đủ dữ liệu để phân tích xu hướng.',
        ];

        return response()->json([
            'success'   => true,
            'semesters' => $labels,
            'gpas'      => $gpas,
            'credits'   => $credits,
            'trend'     => $trend,
            'message'   => $trendMessages[$trend],
        ]);
    }

    /**
     * GET /api/v1/warnings
     * Trả về các cảnh báo học tập hiện tại của sinh viên.
     */
    public function warnings(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Tính/sync cảnh báo trước, sau đó mới đọc DB để unread_count phản ánh ngay
        // trong chính request đầu tiên.
        $rawWarnings = $this->progressService->generateWarnings($userId);

        $dbWarnings = Warning::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($w) => [
                'id'         => $w->id,
                'type'       => $w->type,
                'message'    => $w->message,
                'is_read'    => (bool) $w->is_read,
                'created_at' => $w->created_at?->format('d/m/Y'),
            ]);

        $severityMap = [
            'low_gpa'         => ['severity' => 'critical', 'title' => 'GPA thấp'],
            'debt'            => ['severity' => 'warning',  'title' => 'Nợ môn học'],
            'late_graduation' => ['severity' => 'warning',  'title' => 'Nguy cơ trễ tốt nghiệp'],
        ];

        $liveWarnings = $rawWarnings->map(function ($w) use ($severityMap) {
            $meta = $severityMap[$w->type] ?? ['severity' => 'info', 'title' => 'Thông báo'];
            return [
                'id'       => $w->id,
                'type'     => $w->type,
                'severity' => $meta['severity'],
                'title'    => $meta['title'],
                'message'  => $w->message,
            ];
        })->values();

        return response()->json([
            'success'       => true,
            'live_warnings' => $liveWarnings,
            'db_warnings'   => $dbWarnings,
            'unread_count'  => $dbWarnings->where('is_read', false)->count(),
        ]);
    }

    /**
     * POST /api/v1/warnings/{id}/read
     * Đánh dấu cảnh báo đã đọc.
     */
    public function markWarningRead(int $id)
    {
        $userId = Auth::id();
        $warning = Warning::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $warning->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }
}
