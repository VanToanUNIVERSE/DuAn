<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/suggest');
});

Route::get('/suggest', function () {
    $subjects = App\Models\Subject::with(['subjectType', 'subjectGroup', 'semester'])
        ->get()
        ->groupBy(function ($subject) {
            return $subject->semester?->name ?? 'Môn khác';
        });

    $totalCredits = App\Models\Subject::sum('credits');
    $academicYears = App\Models\TrainingProgram::distinct()->pluck('academic_year')->toArray();
    $programTypes = App\Models\TrainingProgram::distinct()->pluck('program_type')->toArray();

    return view('suggest', compact('subjects', 'academicYears', 'programTypes', 'totalCredits'));
});

