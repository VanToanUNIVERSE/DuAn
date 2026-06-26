<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentStatsController extends Controller
{
    public function index(Request $request)
    {
        $programs = TrainingProgram::orderBy('academic_year', 'desc')
            ->orderBy('program_name')->get();

        $selectedProgramId = $request->input('program_id');
        $selectedSemester  = $request->input('semester');
        $minCount          = max(1, (int) $request->input('min_count', 1));

        // ── Core query ─────────────────────────────────────────────────────
        // Đếm số SV có môn trong kế hoạch đang hoạt động,
        // liên kết chương trình qua pref_academic_year + pref_program_type
        $query = DB::table('study_plan_subjects as sps')
            ->join('study_plan_semesters as sem', 'sem.id', '=', 'sps.study_plan_semester_id')
            ->join('study_plans as sp',   'sp.id',  '=', 'sem.study_plan_id')
            ->join('users as u',          'u.id',   '=', 'sp.user_id')
            ->join('training_programs as tp', function ($join) {
                $join->on('tp.academic_year', '=', 'u.pref_academic_year')
                     ->on('tp.program_type',  '=', 'u.pref_program_type');
            })
            ->join('subjects as s', 's.id', '=', 'sps.subject_id')
            ->where('sp.is_active', true)
            ->where('sp.is_saved',  true)
            ->whereNull('sp.deleted_at')
            ->select([
                'tp.id as program_id',
                'tp.program_name',
                'tp.academic_year',
                'tp.program_type',
                'sem.semester_index',
                's.id as subject_id',
                's.subject_code',
                's.name as subject_name',
                's.credits',
                's.is_elective',
                DB::raw('COUNT(DISTINCT sp.user_id) as student_count'),
            ])
            ->groupBy([
                'tp.id', 'tp.program_name', 'tp.academic_year', 'tp.program_type',
                'sem.semester_index',
                's.id', 's.subject_code', 's.name', 's.credits', 's.is_elective',
            ])
            ->having('student_count', '>=', $minCount)
            ->orderBy('tp.academic_year', 'desc')
            ->orderBy('sem.semester_index')
            ->orderByDesc('student_count');

        if ($selectedProgramId) {
            $query->where('tp.id', $selectedProgramId);
        }
        if ($selectedSemester) {
            $query->where('sem.semester_index', $selectedSemester);
        }

        $rows = $query->get();

        // ── Summary stats ─────────────────────────────────────────────────
        $totalActivePlans = DB::table('study_plans')
            ->where('is_active', true)->where('is_saved', true)
            ->whereNull('deleted_at')->count();

        $totalStudentsWithPlan = DB::table('study_plans')
            ->where('is_active', true)->where('is_saved', true)
            ->whereNull('deleted_at')->distinct('user_id')->count('user_id');

        // ── Group: program → semester → subjects ──────────────────────────
        $grouped = $rows->groupBy('program_id')->map(function ($programRows) {
            $first = $programRows->first();
            return [
                'program_name' => $first->program_name,
                'academic_year'=> $first->academic_year,
                'program_type' => $first->program_type,
                'total_students'=> $programRows->pluck('student_count')->max(),
                'semesters'    => $programRows->groupBy('semester_index')->sortKeys(),
            ];
        });

        // Available semester indices for filter dropdown
        $availableSemesters = $rows->pluck('semester_index')->unique()->sort()->values();

        return view('admin.enrollment-stats.index', compact(
            'programs', 'grouped', 'rows',
            'selectedProgramId', 'selectedSemester', 'minCount',
            'availableSemesters',
            'totalActivePlans', 'totalStudentsWithPlan'
        ));
    }

    public function export(Request $request)
    {
        $selectedProgramId = $request->input('program_id');
        $selectedSemester  = $request->input('semester');
        $minCount          = max(1, (int) $request->input('min_count', 1));

        $query = DB::table('study_plan_subjects as sps')
            ->join('study_plan_semesters as sem', 'sem.id', '=', 'sps.study_plan_semester_id')
            ->join('study_plans as sp',   'sp.id',  '=', 'sem.study_plan_id')
            ->join('users as u',          'u.id',   '=', 'sp.user_id')
            ->join('training_programs as tp', function ($join) {
                $join->on('tp.academic_year', '=', 'u.pref_academic_year')
                     ->on('tp.program_type',  '=', 'u.pref_program_type');
            })
            ->join('subjects as s', 's.id', '=', 'sps.subject_id')
            ->where('sp.is_active', true)->where('sp.is_saved', true)
            ->whereNull('sp.deleted_at')
            ->select([
                'tp.program_name', 'tp.academic_year', 'tp.program_type',
                'sem.semester_index',
                's.subject_code', 's.name as subject_name', 's.credits',
                's.is_elective',
                DB::raw('COUNT(DISTINCT sp.user_id) as student_count'),
            ])
            ->groupBy([
                'tp.id', 'tp.program_name', 'tp.academic_year', 'tp.program_type',
                'sem.semester_index', 's.id', 's.subject_code', 's.name', 's.credits', 's.is_elective',
            ])
            ->having('student_count', '>=', $minCount)
            ->orderBy('tp.academic_year', 'desc')
            ->orderBy('sem.semester_index')
            ->orderByDesc('student_count');

        if ($selectedProgramId) $query->where('tp.id', $selectedProgramId);
        if ($selectedSemester)  $query->where('sem.semester_index', $selectedSemester);

        $rows = $query->get();

        $headers = ['Chương trình', 'Niên khóa', 'Hệ', 'Học kỳ', 'Mã môn', 'Tên môn', 'TC', 'Loại', 'Số SV đăng ký'];
        $csv = implode(',', $headers) . "\n";
        foreach ($rows as $r) {
            $csv .= implode(',', [
                '"' . str_replace('"', '""', $r->program_name)   . '"',
                '"' . $r->academic_year . '"',
                '"' . $r->program_type  . '"',
                $r->semester_index,
                '"' . $r->subject_code  . '"',
                '"' . str_replace('"', '""', $r->subject_name)   . '"',
                $r->credits,
                $r->is_elective ? 'Tự chọn' : 'Bắt buộc',
                $r->student_count,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="dang-ky-hoc-phan-' . now()->format('Ymd') . '.csv"',
        ]);
    }
}
