<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecommendationController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function index(Request $request)
    {
        $userId = Auth::id();

        // Get current semester and study plan context (optional)
        $currentSemester = $request->input('current_semester');
        $studyPlanId = $request->input('study_plan_id');

        $recommendations = $this->recommendationService->getRecommendations($userId, $currentSemester, $studyPlanId);

        return response()->json([
            'success' => true,
            'data' => $recommendations
        ]);
    }
}
