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
        $allSubjects   = Subject::orderBy('name')->get(['id', 'subject_code', 'name']);

        return view('admin.subjects.create', compact(
            'programGroups', 'skillGroups', 'subjectTypes', 'allSubjects'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_code'     => 'required|string|max:50|unique:subjects,subject_code',
            'name'             => 'required|string|max:255',
            'credits'          => 'nullable|integer|min:1|max:20',
            'subject_type_id'  => 'nullable|exists:subject_types,id',
            'skill_group_id'   => 'nullable|exists:skill_groups,id',
            'program_group_id' => 'nullable|exists:program_groups,id',
            'note'             => 'nullable|string',
            'requirement_type' => 'nullable|in:none,completed_basic,completed_major,completed_specialized,completed_all,min_credits',
            'prerequisites'    => 'nullable|array',
            'prerequisites.*'  => 'exists:subjects,id',
            'corequisites'     => 'nullable|array',
            'corequisites.*'   => 'exists:subjects,id',
        ], [
            'subject_code.required' => 'Mã môn học là bắt buộc.',
            'subject_code.unique'   => 'Mã môn “:input” đã tồn tại trong hệ thống.',
            'name.required'         => 'Tên môn học là bắt buộc.',
        ]);

        $subject = Subject::create($validated);

        $relations = [];
        if (!empty($validated['prerequisites'])) {
            foreach ($validated['prerequisites'] as $prereqId) {
                if ($prereqId != $subject->id) {
                    $relations[] = ['subject_id' => $subject->id, 'related_subject_id' => $prereqId, 'type' => 'prerequisite', 'created_at' => now(), 'updated_at' => now()];
                }
            }
        }
        if (!empty($validated['corequisites'])) {
            foreach ($validated['corequisites'] as $coreqId) {
                if ($coreqId != $subject->id) {
                    $relations[] = ['subject_id' => $subject->id, 'related_subject_id' => $coreqId, 'type' => 'corequisite', 'created_at' => now(), 'updated_at' => now()];
                    // Song hành là quan hệ 2 chiều, nên tự động thêm chiều ngược lại
                    $relations[] = ['subject_id' => $coreqId, 'related_subject_id' => $subject->id, 'type' => 'corequisite', 'created_at' => now(), 'updated_at' => now()];
                }
            }
        }
        if (!empty($relations)) {
            \App\Models\SubjectRelation::insert($relations);
        }

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Thêm môn học thành công!');
    }

    public function edit(Subject $subject)
    {
        $programGroups = ProgramGroup::orderBy('name')->get();
        $skillGroups   = SkillGroup::orderBy('name')->get();
        $subjectTypes  = SubjectType::orderBy('name')->get();
        $allSubjects   = Subject::orderBy('name')->get(['id', 'subject_code', 'name']);
        
        $prerequisiteIds = $subject->prerequisites()->pluck('subjects.id')->toArray();
        $corequisiteIds  = $subject->corequisites()->pluck('subjects.id')->toArray();

        return view('admin.subjects.edit', compact(
            'subject', 'programGroups', 'skillGroups', 'subjectTypes', 'allSubjects', 'prerequisiteIds', 'corequisiteIds'
        ));
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'subject_code'     => 'required|string|max:50|unique:subjects,subject_code,' . $subject->id,
            'name'             => 'required|string|max:255',
            'credits'          => 'nullable|integer|min:1|max:20',
            'subject_type_id'  => 'nullable|exists:subject_types,id',
            'skill_group_id'   => 'nullable|exists:skill_groups,id',
            'program_group_id' => 'nullable|exists:program_groups,id',
            'note'             => 'nullable|string',
            'requirement_type' => 'nullable|in:none,completed_basic,completed_major,completed_specialized,completed_all,min_credits',
            'prerequisites'    => 'nullable|array',
            'prerequisites.*'  => 'exists:subjects,id',
            'corequisites'     => 'nullable|array',
            'corequisites.*'   => 'exists:subjects,id',
        ], [
            'subject_code.required' => 'Mã môn học là bắt buộc.',
            'subject_code.unique'   => 'Mã môn “:input” đã tồn tại trong hệ thống.',
            'name.required'         => 'Tên môn học là bắt buộc.',
        ]);

        $subject->update($validated);

        // Xóa quan hệ cũ (chỉ xóa những quan hệ mà subject này là chủ thể, và các quan hệ song hành trỏ về subject này)
        \App\Models\SubjectRelation::where('subject_id', $subject->id)->delete();
        \App\Models\SubjectRelation::where('related_subject_id', $subject->id)->where('type', 'corequisite')->delete();

        $relations = [];
        if (!empty($validated['prerequisites'])) {
            foreach ($validated['prerequisites'] as $prereqId) {
                if ($prereqId != $subject->id) {
                    $relations[] = ['subject_id' => $subject->id, 'related_subject_id' => $prereqId, 'type' => 'prerequisite', 'created_at' => now(), 'updated_at' => now()];
                }
            }
        }
        if (!empty($validated['corequisites'])) {
            foreach ($validated['corequisites'] as $coreqId) {
                if ($coreqId != $subject->id) {
                    $relations[] = ['subject_id' => $subject->id, 'related_subject_id' => $coreqId, 'type' => 'corequisite', 'created_at' => now(), 'updated_at' => now()];
                    // Song hành là quan hệ 2 chiều
                    $relations[] = ['subject_id' => $coreqId, 'related_subject_id' => $subject->id, 'type' => 'corequisite', 'created_at' => now(), 'updated_at' => now()];
                }
            }
        }
        
        // Tránh insert trùng lặp do song hành có thể đã tự định nghĩa ngược lại, dù code ở trên đã xoá sạch nên ko lo trùng nếu insert batch.
        // Tuy nhiên hàm insert ko xử lý unique constraint tự động nếu ta có unique trên [subject_id, related_subject_id, type].
        // Ở DB, không có unique constraint đó (để an toàn).
        // Ta dùng collection unique để chắc ăn:
        $relations = collect($relations)->unique(function ($item) {
            return $item['subject_id'] . '-' . $item['related_subject_id'] . '-' . $item['type'];
        })->values()->toArray();

        if (!empty($relations)) {
            \App\Models\SubjectRelation::insert($relations);
        }

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
