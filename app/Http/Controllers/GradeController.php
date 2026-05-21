<?php

namespace App\Http\Controllers;

use App\Models\UserGrade;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    // ─────────────────────────────────────────────────────────────────────────
    // GET /grades/chart-data
    // Trả về dữ liệu cho biểu đồ cột so sánh điểm cá nhân vs. điểm TB cùng khóa.
    //
    // Response JSON:
    // {
    //   "labels":     ["Tên môn 1", ...],
    //   "my_grades":  [8.5, null, 7.0, ...],        // null = chưa nhập
    //   "avg_grades": [6.2, 5.8, 7.1, ...],         // TB tất cả SV cùng niên khóa
    //   "semesters":  ["1", "1", "2", ...]           // học kỳ chuẩn
    // }
    // ─────────────────────────────────────────────────────────────────────────
    public function chartData()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Niên khóa của user (dùng để lọc "cùng khóa")
        $academicYear = $user->pref_academic_year;

        // Lấy tất cả môn học kèm học kỳ
        $subjects = Subject::with('semester')->orderBy('id')->get();

        // Điểm của user hiện tại (indexed by subject_id)
        $myGradesRaw = UserGrade::where('user_id', $user->id)
            ->whereNotNull('grade')
            ->pluck('grade', 'subject_id');

        // Điểm TB của SV cùng niên khóa cho mỗi môn
        // → JOIN user_grades với users, lọc theo pref_academic_year
        $peerAvgRaw = DB::table('user_grades')
            ->join('users', 'user_grades.user_id', '=', 'users.id')
            ->whereNotNull('user_grades.grade')
            ->when($academicYear, fn($q) => $q->where('users.pref_academic_year', $academicYear))
            ->groupBy('user_grades.subject_id')
            ->select(
                'user_grades.subject_id',
                DB::raw('ROUND(AVG(user_grades.grade), 2) as avg_grade'),
                DB::raw('COUNT(DISTINCT user_grades.user_id) as student_count')
            )
            ->get()
            ->keyBy('subject_id');

        // Chỉ lấy môn mà user đã nhập điểm
        $filtered = $subjects->filter(fn($s) => isset($myGradesRaw[$s->id]));

        $labels    = [];
        $myGrades  = [];
        $avgGrades = [];
        $semesters = [];

        foreach ($filtered as $subject) {
            $labels[]    = $subject->name;
            $myGrades[]  = (float) $myGradesRaw[$subject->id];
            $avgGrades[] = isset($peerAvgRaw[$subject->id])
                ? (float) $peerAvgRaw[$subject->id]->avg_grade
                : null;
            $semesters[] = $subject->semester?->name ?? '?';
        }

        return response()->json([
            'labels'        => $labels,
            'my_grades'     => $myGrades,
            'avg_grades'    => $avgGrades,
            'semesters'     => $semesters,
            'academic_year' => $academicYear,
            'peer_count'    => $peerAvgRaw->isNotEmpty()
                ? (int) $peerAvgRaw->first()->student_count
                : 0,
        ]);
    }
}
