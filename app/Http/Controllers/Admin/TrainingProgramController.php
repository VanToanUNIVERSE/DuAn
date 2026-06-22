<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurriculumFramework;
use App\Models\CurriculumSubject;
use App\Models\ElectiveGroup;
use App\Models\Semester;
use App\Models\TrainingProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainingProgramController extends Controller
{
    public function index()
    {
        $trainingPrograms = TrainingProgram::orderBy('academic_year', 'desc')
                                           ->orderBy('program_name', 'asc')
                                           ->get();
        return view('admin.training-programs.index', compact('trainingPrograms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_name'   => 'required|string|max:255',
            'program_code'   => 'required|string|max:100|unique:training_programs,program_code',
            'education_level'=> 'required|string|max:100',
            'program_type'   => 'required|string|max:100',
            'program_duration'=> 'required|numeric|min:0.5|max:10',
            'academic_year'  => 'required|string|max:50',
            'clone_from'     => 'nullable|exists:training_programs,id',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $newProgram = TrainingProgram::create(collect($validated)->except('clone_from')->toArray());

            if (!empty($validated['clone_from'])) {
                $source = TrainingProgram::with([
                    'curriculumFrameworks.semesters',
                    'curriculumFrameworks.electiveGroups.subjects',
                ])->findOrFail($validated['clone_from']);

                foreach ($source->curriculumFrameworks as $srcFw) {
                    // Tạo framework mới
                    $newFw = CurriculumFramework::create([
                        'training_program_id' => $newProgram->id,
                        'number_of_semesters' => $srcFw->number_of_semesters,
                        'total_credits'       => $srcFw->total_credits,
                    ]);

                    // Map semester cũ → mới
                    $semMap = [];
                    foreach ($srcFw->semesters as $srcSem) {
                        $newSem = Semester::create([
                            'curriculum_framework_id' => $newFw->id,
                            'name'                    => $srcSem->name,
                            'total_credits'           => $srcSem->total_credits,
                        ]);
                        $semMap[$srcSem->id] = $newSem->id;
                    }

                    // Map elective group cũ → mới
                    $egMap = [];
                    foreach ($srcFw->electiveGroups as $srcEg) {
                        $newEg = ElectiveGroup::create([
                            'curriculum_framework_id' => $newFw->id,
                            'name'                    => $srcEg->name,
                            'required_credits'        => $srcEg->required_credits,
                        ]);
                        $egMap[$srcEg->id] = $newEg->id;
                        // Sao chép danh sách môn trong nhóm tự chọn
                        $subjectIds = $srcEg->subjects->pluck('id')->toArray();
                        if ($subjectIds) {
                            $newEg->subjects()->syncWithoutDetaching($subjectIds);
                        }
                    }

                    // Sao chép curriculum_subject (phân công môn → học kỳ)
                    $srcAssignments = CurriculumSubject::where('curriculum_framework_id', $srcFw->id)->get();
                    foreach ($srcAssignments as $cs) {
                        $newSemId = $semMap[$cs->semester_id] ?? null;
                        if (!$newSemId) continue;
                        CurriculumSubject::create([
                            'curriculum_framework_id' => $newFw->id,
                            'semester_id'             => $newSemId,
                            'subject_id'              => $cs->subject_id,
                            'elective_group_id'       => $cs->elective_group_id ? ($egMap[$cs->elective_group_id] ?? null) : null,
                        ]);
                    }
                }
            }
        });

        $cloneMsg = !empty($validated['clone_from'])
            ? ' (đã sao chép toàn bộ phân công môn học từ chương trình nguồn)'
            : '';

        return redirect()->route('admin.training-programs.index')
                         ->with('success', "Đã thêm chương trình đào tạo thành công!{$cloneMsg}");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TrainingProgram $trainingProgram)
    {
        $validated = $request->validate([
            'program_name' => 'required|string|max:255',
            'program_code' => 'required|string|max:100|unique:training_programs,program_code,' . $trainingProgram->id,
            'education_level' => 'required|string|max:100',
            'program_type' => 'required|string|max:100',
            'program_duration' => 'required|numeric|min:0.5|max:10',
            'academic_year' => 'required|string|max:50',
        ]);

        $trainingProgram->update($validated);

        return redirect()->route('admin.training-programs.index')
                         ->with('success', 'Cập nhật chương trình đào tạo thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TrainingProgram $trainingProgram)
    {
        $trainingProgram->delete();

        return redirect()->route('admin.training-programs.index')
                         ->with('success', 'Đã xóa chương trình đào tạo!');
    }
}
