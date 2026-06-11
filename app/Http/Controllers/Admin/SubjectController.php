<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\SkillGroup;
use App\Models\ProgramGroup;
use App\Models\SubjectType;
use App\Models\Semester;
use App\Imports\SubjectsImport;
use App\Exports\SubjectsTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Subject::with(['subjectType', 'skillGroup', 'programGroup']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('program_group_id')) {
            $query->where('program_group_id', $request->program_group_id);
        }

        if ($request->filled('skill_group_id')) {
            $query->where('skill_group_id', $request->skill_group_id);
        }

        if ($request->filled('subject_type_id')) {
            $query->where('subject_type_id', $request->subject_type_id);
        }

        $subjects      = $query->orderBy('name')->paginate(20)->withQueryString();
        $programGroups = ProgramGroup::orderBy('name')->get();
        $skillGroups   = SkillGroup::orderBy('name')->get();
        $subjectTypes  = SubjectType::orderBy('name')->get();

        return view('admin.subjects.index', compact(
            'subjects', 'programGroups', 'skillGroups', 'subjectTypes'
        ));
    }

    public function create()
    {
        $programGroups = ProgramGroup::orderBy('name')->get();
        $skillGroups   = SkillGroup::orderBy('name')->get();
        $subjectTypes  = SubjectType::orderBy('name')->get();

        return view('admin.subjects.create', compact(
            'programGroups', 'skillGroups', 'subjectTypes'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_code'     => 'nullable|string|max:50|unique:subjects,subject_code',
            'name'             => 'required|string|max:255',
            'credits'          => 'nullable|integer|min:1|max:20',
            'subject_type_id'  => 'nullable|exists:subject_types,id',
            'skill_group_id'   => 'nullable|exists:skill_groups,id',
            'program_group_id' => 'nullable|exists:program_groups,id',
            'semester_id'      => 'nullable|exists:semesters,id',
            'note'             => 'nullable|string',
            'requirement_type' => 'nullable|in:none,completed_basic,completed_major,completed_all,min_credits',
        ]);

        Subject::create($validated);

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Thêm môn học thành công!');
    }

    public function edit(Subject $subject)
    {
        $programGroups = ProgramGroup::orderBy('name')->get();
        $skillGroups   = SkillGroup::orderBy('name')->get();
        $subjectTypes  = SubjectType::orderBy('name')->get();

        return view('admin.subjects.edit', compact(
            'subject', 'programGroups', 'skillGroups', 'subjectTypes'
        ));
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'subject_code'     => 'nullable|string|max:50|unique:subjects,subject_code,' . $subject->id,
            'name'             => 'required|string|max:255',
            'credits'          => 'nullable|integer|min:1|max:20',
            'subject_type_id'  => 'nullable|exists:subject_types,id',
            'skill_group_id'   => 'nullable|exists:skill_groups,id',
            'program_group_id' => 'nullable|exists:program_groups,id',
            'semester_id'      => 'nullable|exists:semesters,id',
            'note'             => 'nullable|string',
            'requirement_type' => 'nullable|in:none,completed_basic,completed_major,completed_all,min_credits',
        ]);

        $subject->update($validated);

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Cập nhật môn học thành công!');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Đã xóa môn học.');
    }

    // ── Import ────────────────────────────────────────────────────────────────

    public function importForm()
    {
        return view('admin.subjects.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ], [
            'file.required' => 'Vui lòng chọn file.',
            'file.mimes'    => 'File phải là .xlsx, .xls hoặc .csv.',
            'file.max'      => 'File tối đa 10MB.',
        ]);

        try {
            $import = new SubjectsImport();
            Excel::import($import, $request->file('file'));
            $count = $import->getRowCount();

            return redirect()->route('admin.subjects.index')
                ->with('success', "✅ Import thành công {$count} môn học!");

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errMsg = collect($failures)->map(fn($f) =>
                "Dòng {$f->row()}: " . implode(', ', $f->errors())
            )->implode(' | ');
            return back()->with('error', "Lỗi dữ liệu: {$errMsg}");

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi đọc file: ' . $e->getMessage());
        }
    }

    // ── Download file mẫu ────────────────────────────────────────────────────

    public function downloadTemplate()
    {
        return Excel::download(
            new SubjectsTemplateExport(),
            'mau-import-mon-hoc.xlsx'
        );
    }
}
