<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserPreferenceController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /preferences
    // Trả về cấu hình chương trình hiện tại của user.
    //
    // Response JSON:
    // {
    //   "academic_year":    "2022-2026",   // null nếu chưa từng lưu
    //   "program_type":     "Chính quy",
    //   "current_semester": 3,
    //   "target_years":     4
    // }
    // ─────────────────────────────────────────────────────────────────────────
    public function index()
    {
        $user = Auth::user();

        return response()->json([
            'academic_year'    => $user->pref_academic_year,
            'program_type'     => $user->pref_program_type,
            'current_semester' => $user->pref_current_semester,
            'target_years'     => $user->pref_target_years,
            'current_courses'  => $user->pref_current_courses ? json_decode($user->pref_current_courses, true) : [],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /preferences/save
    // Lưu cấu hình chương trình của user (cập nhật trực tiếp trên bảng users).
    //
    // Request body (JSON):
    // {
    //   "academic_year":    "2022-2026",  // tùy chọn — null = không thay đổi
    //   "program_type":     "Chính quy",
    //   "current_semester": 3,
    //   "target_years":     4
    // }
    // ─────────────────────────────────────────────────────────────────────────
    public function save(Request $request)
    {
        $user = Auth::user();

        // Validate — tất cả đều nullable để hỗ trợ lưu từng phần (partial update)
        $validated = $request->validate([
            'academic_year'    => 'nullable|string|max:20',
            'program_type'     => 'nullable|string|max:50',
            'current_semester' => 'nullable|integer|min:1|max:10',
            'target_years'     => 'nullable|integer|min:3|max:8',
            'current_courses'  => 'nullable|array',
        ]);

        // Chỉ cập nhật các trường được gửi lên (bỏ qua null từ key không tồn tại)
        $toUpdate = [];

        if (array_key_exists('academic_year', $validated)) {
            $toUpdate['pref_academic_year'] = $validated['academic_year'];
        }
        if (array_key_exists('program_type', $validated)) {
            $toUpdate['pref_program_type'] = $validated['program_type'];
        }
        if (array_key_exists('current_semester', $validated)) {
            $toUpdate['pref_current_semester'] = $validated['current_semester'];
        }
        if (array_key_exists('target_years', $validated)) {
            $toUpdate['pref_target_years'] = $validated['target_years'];
        }
        if (array_key_exists('current_courses', $validated)) {
            $toUpdate['pref_current_courses'] = $validated['current_courses'] === null ? null : json_encode($validated['current_courses']);
        }

        if (!empty($toUpdate)) {
            $user->update($toUpdate);
        }

        return response()->json(['message' => 'Đã lưu cấu hình', 'saved' => $toUpdate]);
    }
}
