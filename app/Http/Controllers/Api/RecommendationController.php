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
        // For testing, user_id can be passed, otherwise get logged in user
        $userId = $request->input('user_id') ?? Auth::id();
        
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized or missing user_id'], 401);
        }

        $recommendations = $this->recommendationService->getRecommendations($userId);

        return response()->json([
            'success' => true,
            'data' => $recommendations
        ]);
    }
}
