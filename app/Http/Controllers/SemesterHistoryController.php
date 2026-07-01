<?php

namespace App\Http\Controllers;

use App\Models\SemesterHistory;
use App\Models\SemesterHistoryItem;
use App\Models\StudyPlan;
use App\Models\StudyPlanSubject;
use App\Models\Subject;
use App\Models\UserGrade;
use App\Services\AcademicEvaluationService;
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
            $status  = $grade !== null ? ($grade >= 5.0 ? 'pass' : 'fail') : null;

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
            $history = SemesterHistory::updateOrCreate(
                [
                    'user_id'         => $user->id,
                    'semester_number' => $validated['semester_number'],
                ],
                [
                    'academic_year'   => $validated['academic_year'] ?? $user->pref_academic_year,
                    'program_type'    => $validated['program_type']  ?? $user->pref_program_type,
                    'total_credits'   => $totalCredits,
                    'passed_credits'  => $passedCredits,
                    'gpa'             => $gpa,
                ]
            );

            // Xóa các item cũ nếu có
            SemesterHistoryItem::where('semester_history_id', $history->id)->delete();

            foreach ($validated['courses'] as $course) {
                $grade  = isset($course['grade']) ? (float) $course['grade'] : null;
                $status = $grade !== null ? ($grade >= 5.0 ? 'pass' : 'fail') : null;

                SemesterHistoryItem::create([
                    'semester_history_id' => $history->id,
                    'subject_id'          => $course['subject_id'],
                    'grade'               => $grade,
                    'status'              => $status,
                ]);
            }
        });

        // ── Đồng bộ UserGrade từ study_plan_subjects.subject_grade ──────────────────
        // Đảm bảo suggestion engine và prerequisite check luôn đọc điểm chính xác
        // sau khi lưu lịch sử (dù grades được gửi từ DOM input).
        $activePlanForSync = StudyPlan::with('semesters.subjects')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($activePlanForSync) {
            // Thu thập tất cả subject_id trong học kỳ này
            $semSubjectIds = collect($validated['courses'])->pluck('subject_id')->toArray();

            foreach ($semSubjectIds as $subjectId) {
                $origGrade   = null;
                $retakeGrade = null;

                foreach ($activePlanForSync->semesters as $sem) {
                    foreach ($sem->subjects as $ss) {
                        if ($ss->subject_id == $subjectId && $ss->subject_grade !== null) {
                            if (!$ss->is_retake) {
                                $origGrade = (float) $ss->subject_grade;
                            } else {
                                $retakeGrade = (float) $ss->subject_grade;
                            }
                        }
                    }
                }

                // GPA = max(orig, retake)
                $allGrades = array_filter([$origGrade, $retakeGrade], fn($v) => $v !== null);
                if (count($allGrades) > 0) {
                    $bestGrade  = max($allGrades);
                    $bestStatus = $bestGrade >= 5.0 ? 'pass' : 'fail';
                    UserGrade::updateOrCreate(
                        ['user_id' => $user->id, 'subject_id' => $subjectId],
                        ['grade' => $bestGrade, 'status' => $bestStatus]
                    );
                }
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // TRIGGER: Đánh giá kế hoạch học tập sau khi hoàn tất học kỳ
        // Trả về evaluation để FE hiển thị modal tư vấn điều chỉnh
        // ═══════════════════════════════════════════════════════════════════
        $evaluation    = null;
        $needsAdjust   = false;
        $activePlanId  = null;

        try {
            // Lấy kế hoạch học tập đang active của sinh viên
            $activePlan = StudyPlan::where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if ($activePlan) {
                $activePlanId = $activePlan->id;
                $evalService  = app(AcademicEvaluationService::class);

                $evaluation = $evalService->evaluate(
                    $user->id,
                    $activePlan->mode ?? 'normal',
                    $activePlan->target_semesters ?? $activePlan->target_semester_count ?? 8,
                    $validated['semester_number'] + 1  // Học kỳ tiếp theo
                );

                // Xác định xem có cần điều chỉnh kế hoạch không
                $needsAdjust = in_array($evaluation['status'], ['REPLAN', 'SPEED_UP', 'REDUCE']);
            }
        } catch (\Exception $e) {
            // Không làm gián đoạn luồng chính nếu đánh giá lỗi
            \Illuminate\Support\Facades\Log::warning(
                'AcademicEvaluationService error after semester complete: ' . $e->getMessage()
            );
        }

        return response()->json([
            'message'         => 'Đã lưu lịch sử học kỳ thành công',
            'evaluation'      => $evaluation,     // null nếu chưa có kế hoạch
            'needs_adjustment'=> $needsAdjust,    // true → FE hiển thị modal điều chỉnh
            'active_plan_id'  => $activePlanId,   // để FE biết ID kế hoạch cần điều chỉnh
        ]);
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
