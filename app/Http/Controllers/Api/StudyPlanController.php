<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StudyPlanService;
use App\Models\StudyPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudyPlanController extends Controller
{
    protected $studyPlanService;

    public function __construct(StudyPlanService $studyPlanService)
    {
        $this->studyPlanService = $studyPlanService;
    }

    public function generate(Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();
        
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized or missing user_id'], 401);
        }

        $request->validate([
            'name' => 'required|string',
            'mode' => 'nullable|string|in:normal,fast,slow',
        ]);

        $mode = $request->input('mode', 'normal');
        $name = $request->input('name');

        $plan = $this->studyPlanService->generatePlan($userId, $name, $mode);

        return response()->json([
            'success' => true,
            'message' => 'Study plan generated successfully',
            'data' => $plan
        ]);
    }

    public function index(Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();
        $plans = StudyPlan::where('user_id', $userId)->with('semesters.subjects.subject')->get();
        return response()->json(['success' => true, 'data' => $plans]);
    }
}
