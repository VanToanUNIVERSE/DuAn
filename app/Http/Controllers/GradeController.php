<?php

namespace App\Http\Controllers;

use App\Models\UserGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GradeController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/grades
    // Trả về toàn bộ điểm đã lưu của người dùng hiện tại.
    // Response: JSON array [{ subject_id, grade, status }, ...]
    // ─────────────────────────────────────────────────────────────────────────
    public function index()
    {
        // Lấy user đang đăng nhập (session auth)
        $user = Auth::user();

        if (!$user) {
            // Trả về mảng rỗng nếu chưa đăng nhập (phòng trường hợp gọi sai)
            return response()->json([]);
        }

        // Lấy tất cả bản ghi điểm của user, chỉ chọn các cột cần thiết
        $grades = UserGrade::where('user_id', $user->id)
            ->select('subject_id', 'grade', 'status')
            ->get();

        return response()->json($grades);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/grades/save
    // Lưu (upsert) một hoặc nhiều điểm vào database.
    //
    // Request body (JSON):
    //   [
    //     { "subject_id": 1, "grade": 8.5 },
    //     { "subject_id": 2, "grade": 4.0 },
    //     ...
    //   ]
    //
    // Nếu bản ghi (user_id + subject_id) đã tồn tại → cập nhật grade & status.
    // Nếu chưa có → tạo mới.
    // ─────────────────────────────────────────────────────────────────────────
    public function save(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Validate: phải là mảng, mỗi phần tử có subject_id (số nguyên) và grade (số thực, nullable)
        $validated = $request->validate([
            '*'             => 'array',
            '*.subject_id'  => 'required|integer|exists:subjects,id',
            '*.grade'       => 'nullable|numeric|min:0|max:10',
        ]);

        // Duyệt qua từng mục và upsert vào bảng user_grades
        foreach ($validated as $item) {
            $grade  = isset($item['grade']) ? (float) $item['grade'] : null;

            // Xác định status dựa theo ngưỡng điểm 5.0
            $status = null;
            if ($grade !== null) {
                $status = $grade > 5.0 ? 'pass' : 'fail';
            }

            // updateOrCreate: tìm theo (user_id + subject_id), cập nhật grade + status
            UserGrade::updateOrCreate(
                [
                    'user_id'    => $user->id,
                    'subject_id' => $item['subject_id'],
                ],
                [
                    'grade'  => $grade,
                    'status' => $status,
                ]
            );
        }

        return response()->json(['message' => 'Đã lưu thành công', 'count' => count($validated)]);
    }
}
