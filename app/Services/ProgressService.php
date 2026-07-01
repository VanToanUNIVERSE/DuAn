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
        
        // Hỗ trợ cả 'pass'/'passed' và 'fail'/'failed' để nhất quán với phần còn lại của hệ thống
        $passedGrades = $userGrades->filter(fn($g) => in_array($g->status, ['pass', 'passed']) || ($g->grade !== null && $g->grade >= 5.0));
        $failedGrades = $userGrades->filter(fn($g) => in_array($g->status, ['fail', 'failed']) || ($g->grade !== null && $g->grade < 5.0 && !in_array($g->status, ['pass', 'passed'])));
        
        $earnedCredits = $passedGrades->sum(function ($grade) {
            return $grade->subject->credits ?? 0;
        });

        $failedCredits = $failedGrades->sum(function ($grade) {
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
                $totalRequiredCredits = $program->curriculumFrameworks->first()->calculatedTotalCredits();
            }
        }
        // Guard tránh division by zero nếu total_credits = 0 trong DB
        if (!$totalRequiredCredits) $totalRequiredCredits = 140;

        $completionPercentage = round(($earnedCredits / $totalRequiredCredits) * 100, 2);
        $gradedCreditsPercentage = round((($earnedCredits + $failedCredits) / $totalRequiredCredits) * 100, 2);

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

        // ═══════════════════════════════════════════════════════════════════
        // TÍCH HỢP SEMESTER HISTORY
        // ═══════════════════════════════════════════════════════════════════
        $semesterHistories = \App\Models\SemesterHistory::where('user_id', $userId)
            ->orderBy('semester_number')
            ->get();
            
        $semesterGpas = $semesterHistories->pluck('gpa', 'semester_number')->toArray();
        $completedSemesters = $semesterHistories->count();
        
        $gpaTrend = 'stable';
        if ($completedSemesters >= 2) {
            $lastTwo = $semesterHistories->slice(-2)->values();
            if ($lastTwo[1]->gpa > $lastTwo[0]->gpa + 0.2) $gpaTrend = 'improving';
            elseif ($lastTwo[1]->gpa < $lastTwo[0]->gpa - 0.2) $gpaTrend = 'declining';
        }

        $avgCreditsPerSem = $completedSemesters > 0
            ? $earnedCredits / $completedSemesters
            : 0;

        $remainingCredits = max(0, $totalRequiredCredits - $earnedCredits);
        $targetSems = $user->pref_target_years ? $user->pref_target_years * 2 : 8;
        $remainingSems = max(1, $targetSems - $completedSemesters);
        $canGraduateOntime = ($remainingCredits / $remainingSems) <= 25;

        return [
            'earned_credits' => $earnedCredits,
            'total_required_credits' => $totalRequiredCredits,
            'completion_percentage' => $completionPercentage,
            'current_gpa' => $currentGpa,
            'passed_subjects_count' => $passedGrades->count(),
            'failed_subjects_count' => $failedGrades->count(),
            'graded_credits_percentage' => $gradedCreditsPercentage,
            
            // Dữ liệu mới thêm
            'semester_gpas'       => $semesterGpas,
            'gpa_trend'           => $gpaTrend,
            'completed_semesters' => $completedSemesters,
            'avg_credits_per_sem' => round($avgCreditsPerSem, 1),
            'remaining_credits'   => $remainingCredits,
            'remaining_semesters' => $remainingSems,
            'can_graduate_ontime' => $canGraduateOntime,
            'needed_credits_per_sem' => $remainingSems > 0 ? (int) ceil($remainingCredits / $remainingSems) : 0,
        ];
    }

    /**
     * Generate warnings based on student progress.
     * Uses delete-when-resolved / create-when-triggered pattern
     * to avoid stale warnings persisting after grades change.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function generateWarnings(int $userId)
    {
        $progress = $this->evaluateProgress($userId);

        // ── 1. GPA Warning ─────────────────────────────────────────────
        // Thang điểm 10: GPA < 5.0 = yếu, < 4.0 = nguy hiểm, < 3.5 = nguy cơ bị đình chỉ
        if ($progress['current_gpa'] > 0 && $progress['current_gpa'] < 5.0) {
            $isExpelled = $progress['current_gpa'] < 3.5;
            Warning::updateOrCreate(
                ['user_id' => $userId, 'type' => 'low_gpa'],
                [
                    'message'  => $isExpelled
                        ? 'NGUY HIỂM: GPA tích lũy của bạn dưới 3.5/10. Nguy cơ bị buộc thôi học. Hãy liên hệ cố vấn học tập ngay.'
                        : 'Cảnh báo: GPA tích lũy của bạn đang dưới 5.0/10. Cần cải thiện kết quả học tập.',
                    'is_read'  => false,
                ]
            );
        } else {
            // GPA đã cải thiện → xóa cảnh báo cũ
            Warning::where('user_id', $userId)->where('type', 'low_gpa')->delete();
        }

        // ── 2. Debt (Nợ môn) Warning ──────────────────────────────────
        if ($progress['failed_subjects_count'] >= 3) {
            Warning::updateOrCreate(
                ['user_id' => $userId, 'type' => 'debt'],
                [
                    'message' => "Cảnh báo: Bạn đang nợ {$progress['failed_subjects_count']} môn. Điều này có thể ảnh hưởng nghiêm trọng đến tiến độ tốt nghiệp.",
                    'is_read' => false,
                ]
            );
        } else {
            // Đã xóa điểm rớt / cải thiện → xóa cảnh báo nợ môn
            Warning::where('user_id', $userId)->where('type', 'debt')->delete();
        }

        // ── 3. Late graduation warning ────────────────────────────────
        $neededPerSem = $progress['needed_credits_per_sem'] ?? 0;
        if ($neededPerSem > 25) {
            $lateBy = $progress['remaining_semesters'] - ($progress['remaining_credits'] > 0 ? 8 : 0);
            Warning::updateOrCreate(
                ['user_id' => $userId, 'type' => 'late_graduation'],
                [
                    'message' => "Dựa trên tiến độ hiện tại, bạn có nguy cơ trễ tốt nghiệp. Chỉ cần {$neededPerSem} TC/kỳ để tốt nghiệp trong {$progress['remaining_semesters']} học kỳ tới.",
                    'is_read' => false,
                ]
            );
        } else {
            Warning::where('user_id', $userId)->where('type', 'late_graduation')->delete();
        }

        // Trả về các cảnh báo chưa đọc hiện tại (đã được sync với thực tế)
        return Warning::where('user_id', $userId)->where('is_read', false)->get();
    }
}
