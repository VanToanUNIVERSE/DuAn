<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CascadeAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CascadeController extends Controller
{
    public function __construct(protected CascadeAnalysisService $cascadeService) {}

    /**
     * GET /api/v1/cascade-impact/{subjectId}
     * Phân tích ảnh hưởng dây chuyền khi rớt một môn.
     */
    public function show(int $subjectId)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $result = $this->cascadeService->analyze($userId, $subjectId);
        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * POST /api/v1/cascade-impact/multiple
     * Body: { "subject_ids": [1, 2, 3] }
     * Phân tích ảnh hưởng khi rớt nhiều môn cùng lúc.
     */
    public function multiple(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'subject_ids'   => 'required|array|min:1|max:20',
            'subject_ids.*' => 'integer|exists:subjects,id',
        ]);

        $result = $this->cascadeService->analyzeMultiple($userId, $validated['subject_ids']);
        return response()->json(['success' => true, 'data' => $result]);
    }
}
