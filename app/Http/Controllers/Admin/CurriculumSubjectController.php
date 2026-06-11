<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurriculumFramework;
use App\Models\CurriculumSubject;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TrainingProgram;
use Illuminate\Http\Request;

class CurriculumSubjectController extends Controller
{
    /**
     * Danh sách chương trình đào tạo để chọn phân công môn học
     */
    public function index()
    {
        $trainingPrograms = TrainingProgram::with([
            'curriculumFrameworks.semesters'
        ])->orderBy('academic_year', 'desc')->get();

        return view('admin.curriculum.index', compact('trainingPrograms'));
    }

    /**
     * Trang phân công môn học cho một chương trình đào tạo
     * Hiển thị từng học kỳ với danh sách môn đã được gán và ô tìm kiếm thêm môn
     */
    public function show(CurriculumFramework $curriculumFramework)
    {
        $curriculumFramework->load([
            'trainingProgram',
            'semesters' => function ($q) {
                $q->orderBy('name');
            },
        ]);

        // Với mỗi học kỳ, load môn học đã được phân công
        $semestersWithSubjects = $curriculumFramework->semesters->map(function ($semester) use ($curriculumFramework) {
            $semester->assignedSubjects = CurriculumSubject::where('curriculum_framework_id', $curriculumFramework->id)
                ->where('semester_id', $semester->id)
                ->with('subject.skillGroup', 'subject.programGroup')
                ->get();
            return $semester;
        });

        // Tất cả môn học (để tìm kiếm thêm)
        $allSubjects = Subject::with(['skillGroup', 'programGroup'])
            ->orderBy('name')
            ->get(['id', 'subject_code', 'name', 'credits', 'skill_group_id', 'program_group_id']);

        return view('admin.curriculum.show', compact(
            'curriculumFramework',
            'semestersWithSubjects',
            'allSubjects'
        ));
    }

    /**
     * Thêm môn học vào học kỳ của chương trình
     */
    public function assign(Request $request, CurriculumFramework $curriculumFramework)
    {
        $validated = $request->validate([
            'semester_id' => 'required|exists:semesters,id',
            'subject_id'  => 'required|exists:subjects,id',
        ]);

        // Kiểm tra semester thuộc đúng curriculum framework
        $semester = Semester::where('id', $validated['semester_id'])
            ->where('curriculum_framework_id', $curriculumFramework->id)
            ->first();

        if (!$semester) {
            return back()->with('error', 'Học kỳ không thuộc chương trình này.');
        }

        // Tránh trùng lặp
        $exists = CurriculumSubject::where([
            'curriculum_framework_id' => $curriculumFramework->id,
            'semester_id'             => $validated['semester_id'],
            'subject_id'              => $validated['subject_id'],
        ])->exists();

        if ($exists) {
            return back()->with('error', 'Môn học này đã được phân công vào học kỳ đó.');
        }

        CurriculumSubject::create([
            'curriculum_framework_id' => $curriculumFramework->id,
            'semester_id'             => $validated['semester_id'],
            'subject_id'              => $validated['subject_id'],
        ]);

        $subject = Subject::find($validated['subject_id']);
        return back()->with('success', "✅ Đã thêm \"{$subject->name}\" vào học kỳ {$semester->name}.");
    }

    /**
     * Xóa môn học khỏi học kỳ của chương trình
     */
    public function remove(CurriculumFramework $curriculumFramework, CurriculumSubject $assignment)
    {
        if ($assignment->curriculum_framework_id !== $curriculumFramework->id) {
            return back()->with('error', 'Không tìm thấy phân công này.');
        }

        $subjectName = $assignment->subject->name ?? 'Môn học';
        $assignment->delete();

        return back()->with('success', "Đã xóa \"{$subjectName}\" khỏi học kỳ.");
    }
}
