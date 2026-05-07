<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SuggestionService;

class SuggestionController extends Controller
{
    protected $suggestionService;

    public function __construct(SuggestionService $suggestionService)
    {
        $this->suggestionService = $suggestionService;
    }

    public function index(Request $request)
    {
        $userId = auth()->id();

        if (!$userId) {
            return response()->json([
                'message' => 'Bạn cần đăng nhập để sử dụng tính năng này.'
            ], 401);
        }

        $currentSemester = $request->query('semester', 1);

        $result = $this->suggestionService
            ->suggestSubjects($userId, $currentSemester);

        return response()->json($result);
    }
}
