<?php

namespace App\Http\Controllers;

use App\Models\SemesterHistory;
use App\Models\SemesterHistoryItem;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SemesterHistoryController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // POST /semester-history/complete
    // Lưu lịch sử khi người dùng ấn "Hoàn tất học kỳ".
    //
    // Request body (JSON):
    // {
    //   "semester_number": 3,
    //   "academic_year":   "2022-2026",
    //   "program_type":    "Chính quy",
    //   "courses": [
    //     { "subject_id": 5, "grade": 8.5 },
    //     ...
    //   ]
    // }
    // ─────────────────────────────────────────────────────────────────────────
    public function complete(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'semester_number' => 'required|integer|min:1|max:10',
            'academic_year'   => 'nullable|string|max:20',
            'program_type'    => 'nullable|string|max:50',
            'courses'         => 'required|array|min:1',
            'courses.*.subject_id' => 'required|integer|exists:subjects,id',
            'courses.*.grade'      => 'nullable|numeric|min:0|max:10',
        ]);

        // Tính tổng tín chỉ và GPA
        $subjectIds   = collect($validated['courses'])->pluck('subject_id');
        $subjectMap   = Subject::whereIn('id', $subjectIds)->pluck('credits', 'id');

        $totalCredits  = 0;
        $passedCredits = 0;
        $weightedSum   = 0;
        $gradedCredits = 0;

        foreach ($validated['courses'] as $course) {
            $credits = $subjectMap[$course['subject_id']] ?? 0;
            $grade   = isset($course['grade']) ? (float) $course['grade'] : null;
            $status  = $grade !== null ? ($grade > 5.0 ? 'pass' : 'fail') : null;

            $totalCredits += $credits;
            if ($status === 'pass') {
                $passedCredits += $credits;
            }
            if ($grade !== null) {
                $weightedSum   += $grade * $credits;
                $gradedCredits += $credits;
            }
        }

        $gpa = $gradedCredits > 0 ? round($weightedSum / $gradedCredits, 2) : null;

        // Lưu vào DB trong transaction
        DB::transaction(function () use ($user, $validated, $totalCredits, $passedCredits, $gpa, $subjectMap) {
            $history = SemesterHistory::create([
                'user_id'         => $user->id,
                'semester_number' => $validated['semester_number'],
                'academic_year'   => $validated['academic_year'] ?? $user->pref_academic_year,
                'program_type'    => $validated['program_type']  ?? $user->pref_program_type,
                'total_credits'   => $totalCredits,
                'passed_credits'  => $passedCredits,
                'gpa'             => $gpa,
            ]);

            foreach ($validated['courses'] as $course) {
                $grade  = isset($course['grade']) ? (float) $course['grade'] : null;
                $status = $grade !== null ? ($grade > 5.0 ? 'pass' : 'fail') : null;

                SemesterHistoryItem::create([
                    'semester_history_id' => $history->id,
                    'subject_id'          => $course['subject_id'],
                    'grade'               => $grade,
                    'status'              => $status,
                ]);
            }
        });

        return response()->json(['message' => 'Đã lưu lịch sử học kỳ thành công']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /semester-history
    // Lấy toàn bộ lịch sử học kỳ của user hiện tại, mới nhất trước.
    // ─────────────────────────────────────────────────────────────────────────
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([], 401);
        }

        $histories = SemesterHistory::where('user_id', $user->id)
            ->with(['items.subject:id,name,credits'])
            ->orderByDesc('semester_number')
            ->orderByDesc('completed_at')
            ->get()
            ->map(function ($h) {
                return [
                    'id'              => $h->id,
                    'semester_number' => $h->semester_number,
                    'academic_year'   => $h->academic_year,
                    'program_type'    => $h->program_type,
                    'total_credits'   => $h->total_credits,
                    'passed_credits'  => $h->passed_credits,
                    'gpa'             => $h->gpa,
                    'completed_at'    => $h->completed_at?->format('d/m/Y'),
                    'items'           => $h->items->map(fn($item) => [
                        'subject_id'   => $item->subject_id,
                        'subject_name' => $item->subject?->name,
                        'credits'      => $item->subject?->credits,
                        'grade'        => $item->grade,
                        'status'       => $item->status,
                    ]),
                ];
            });

        return response()->json($histories);
    }
}
