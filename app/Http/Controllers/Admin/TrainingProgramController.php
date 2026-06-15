<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingProgram;
use Illuminate\Http\Request;

class TrainingProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $trainingPrograms = TrainingProgram::orderBy('academic_year', 'desc')
                                           ->orderBy('program_name', 'asc')
                                           ->get();
        return view('admin.training-programs.index', compact('trainingPrograms'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_name' => 'required|string|max:255',
            'program_code' => 'required|string|max:100|unique:training_programs,program_code',
            'education_level' => 'required|string|max:100',
            'program_type' => 'required|string|max:100',
            'program_duration' => 'required|numeric|min:0.5|max:10',
            'academic_year' => 'required|string|max:50',
        ]);

        TrainingProgram::create($validated);

        return redirect()->route('admin.training-programs.index')
                         ->with('success', 'Đã thêm chương trình đào tạo thành công!');
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
