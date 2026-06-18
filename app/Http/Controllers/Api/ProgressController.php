<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $userId = $request->input('user_id') ?? Auth::id();
        
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized or missing user_id'], 401);
        }

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
}
