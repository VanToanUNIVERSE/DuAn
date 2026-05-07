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
        // 1. Nhận các tham số đầu vào từ Request (không cần đăng nhập)
        $academicYear = $request->input('academic_year'); // Ví dụ: '2022-2026'
        $programType = $request->input('program_type');   // Ví dụ: 'Chính quy'
        $passedSubjects = $request->input('passed_subjects', []); // Ví dụ: [1, 2, 5] hoặc "1,2,5"
        $currentSemester = $request->input('semester', 1); // Học kỳ hiện tại, mặc định là 1

        // 2. Chuẩn hóa passed_subjects thành mảng số nguyên (phòng tránh ConvertEmptyStringsToNull của Laravel biến chuỗi rỗng thành null)
        if (empty($passedSubjects)) {
            $passedSubjects = [];
        } elseif (is_string($passedSubjects)) {
            $passedSubjects = array_filter(explode(',', $passedSubjects));
        }
        $passedSubjects = array_map('intval', (array)$passedSubjects);

        $userId = auth()->id(); // Lấy ID nếu có đăng nhập, null nếu là khách

        // 3. Gọi Service xử lý lấy ra các môn học đề xuất (Dùng 1 hàm duy nhất)
        $result = $this->suggestionService
            ->suggestSubjects($userId, $currentSemester, $academicYear, $programType, $passedSubjects);

        return response()->json($result);
    }
}
