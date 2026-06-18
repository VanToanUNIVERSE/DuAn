<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\User;
use App\Models\UserGrade;
use App\Models\Warning;
use App\Models\TrainingProgram;
use Illuminate\Support\Facades\Log;

class ProgressService
{
    /**
     * Evaluate student's progress
     *
     * @param int $userId
     * @return array
     */
    public function evaluateProgress(int $userId): array
    {
        $user = User::findOrFail($userId);
        
        // Fetch all grades and keep only the highest grade per subject
        $allUserGrades = UserGrade::where('user_id', $userId)->with('subject')->get();
        $userGrades = $allUserGrades->groupBy('subject_id')->map(function ($grades) {
            // Sort by grade descending and take the first one
            // Note: This assumes 'grade' is numeric. If some grades are letter grades, 
            // you might need a more sophisticated sorting logic.
            return $grades->sortByDesc('grade')->first();
        })->values();
        
        $passedGrades = $userGrades->where('status', 'passed');
        $failedGrades = $userGrades->where('status', 'failed');
        
        $earnedCredits = $passedGrades->sum(function ($grade) {
            return $grade->subject->credits ?? 0;
        });

        // Fetch curriculum framework total credits dynamically
        $totalRequiredCredits = 140;
        if ($user->pref_academic_year && $user->pref_program_type) {
            $program = TrainingProgram::with('curriculumFrameworks')
                ->where('academic_year', $user->pref_academic_year)
                ->where('program_type', $user->pref_program_type)
                ->first();
                
            if ($program && $program->curriculumFrameworks->isNotEmpty()) {
                $totalRequiredCredits = $program->curriculumFrameworks->first()->total_credits ?? 140;
            }
        } 
        $completionPercentage = round(($earnedCredits / $totalRequiredCredits) * 100, 2);

        // Simple GPA calculation (assuming 4.0 scale and grade is numeric)
        // If grade is not numeric, this logic needs adjustment based on actual grading system
        $totalScore = 0;
        $totalAttemptedCredits = 0;
        foreach ($userGrades as $grade) {
            $credit = $grade->subject->credits ?? 0;
            if (is_numeric($grade->grade)) {
                $totalScore += ($grade->grade * $credit);
                $totalAttemptedCredits += $credit;
            }
        }
        
        $currentGpa = $totalAttemptedCredits > 0 ? round($totalScore / $totalAttemptedCredits, 2) : 0;

        return [
            'earned_credits' => $earnedCredits,
            'total_required_credits' => $totalRequiredCredits,
            'completion_percentage' => $completionPercentage,
            'current_gpa' => $currentGpa,
            'passed_subjects_count' => $passedGrades->count(),
            'failed_subjects_count' => $failedGrades->count(),
        ];
    }

    /**
     * Generate warnings based on student progress
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function generateWarnings(int $userId)
    {
        $progress = $this->evaluateProgress($userId);
        
        // 1. GPA Warning
        if ($progress['current_gpa'] > 0 && $progress['current_gpa'] < 2.0) {
            Warning::firstOrCreate([
                'user_id' => $userId,
                'type' => 'low_gpa',
            ], [
                'message' => 'Cảnh báo: Điểm trung bình tích lũy (GPA) của bạn đang dưới mức 2.0. Bạn có nguy cơ bị buộc thôi học. Hãy liên hệ cố vấn học tập ngay.',
            ]);
        }

        // 2. Debt Warning
        if ($progress['failed_subjects_count'] >= 3) {
            Warning::firstOrCreate([
                'user_id' => $userId,
                'type' => 'debt',
            ], [
                'message' => "Cảnh báo: Bạn đang nợ {$progress['failed_subjects_count']} môn. Điều này có thể ảnh hưởng nghiêm trọng đến tiến độ tốt nghiệp.",
            ]);
        }

        // Return unread warnings
        return Warning::where('user_id', $userId)->where('is_read', false)->get();
    }
}
