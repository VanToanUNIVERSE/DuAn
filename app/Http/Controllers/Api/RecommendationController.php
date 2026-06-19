<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserGrade;
use App\Services\GraduationAdvisorService;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecommendationController extends Controller
{
    protected RecommendationService $recommendationService;
    protected GraduationAdvisorService $advisorService;

    public function __construct(
        RecommendationService $recommendationService,
        GraduationAdvisorService $advisorService
    ) {
        $this->recommendationService = $recommendationService;
        $this->advisorService        = $advisorService;
    }

    /**
     * Lấy danh sách môn học gợi ý cho kỳ tiếp theo.
     *
     * Tích hợp GraduationAdvisorService để:
     *  - Tính advisor_score theo 8 tiêu chí
     *  - Kèm theo credit_target và graduation_advice
     *  - Sắp xếp theo thứ tự ưu tiên thông minh
     */
    public function index(Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();

        if (!$userId) {
            return response()->json(['error' => 'Unauthorized or missing user_id'], 401);
        }

        // 1. Lấy danh sách gợi ý từ RecommendationService
        $recommendations = $this->recommendationService->getRecommendations($userId);

        // 2. Lấy tư vấn toàn diện từ GraduationAdvisorService
        $advisorData = $this->advisorService->advise($userId);
        $progress    = $advisorData['progress'];

        // 3. Lấy danh sách môn đã rớt để ưu tiên học lại
        $failedSubjectIds = UserGrade::where('user_id', $userId)
            ->where('status', 'failed')
            ->pluck('subject_id')
            ->toArray();

        // 4. Chuyển đổi format và áp dụng advisor scoring
        $currentSem = $progress['current_semester'] ?? 1;
        $recArray   = [];
        foreach ($recommendations as $item) {
            $recArray[] = [
                'subject'        => $item['subject'] ?? $item,
                'score'          => $item['score'] ?? 0,
                'reasons'        => $item['reasons'] ?? [],
                'skill_group_avg'=> $item['skill_group_avg'] ?? 0,
                'dependent_count'=> $item['dependent_count'] ?? 0,
            ];
        }

        // 5. Sắp xếp theo advisor score (8 tiêu chí)
        $prioritized = $this->advisorService->prioritizeSubjects(
            $recArray,
            $progress,
            $currentSem,
            $failedSubjectIds
        );

        // 6. Rebuild format cũ để không phá vỡ FE hiện tại
        $formattedData = array_map(function ($rec) {
            return [
                'subject'       => $rec['subject'],
                'score'         => $rec['score'],
                'advisor_score' => $rec['advisor_score'] ?? $rec['score'], // Điểm tổng hợp
                'reasons'       => $rec['reasons'] ?? [],
            ];
        }, $prioritized);

        return response()->json([
            'success'           => true,
            'data'              => $formattedData,
            // Thêm metadata tư vấn toàn diện
            'graduation_advice' => $advisorData['advice'],
            'credit_target'     => $advisorData['credit_target'],
            'graduation_risk'   => $progress['graduation_risk'] ?? 'low',
            'gpa_trend'         => $progress['gpa_trend'] ?? 'stable',
        ]);
    }
}
