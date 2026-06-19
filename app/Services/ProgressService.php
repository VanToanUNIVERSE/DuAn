<?php

namespace App\Services;

use App\Models\SemesterHistory;
use App\Models\Subject;
use App\Models\StudyPlan;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Models\UserGrade;
use App\Models\Warning;
use Illuminate\Support\Facades\Log;

class ProgressService
{
    /**
     * Đánh giá toàn diện tiến độ học tập của sinh viên.
     *
     * Cải tiến so với phiên bản cũ:
     * - Tích hợp SemesterHistory để tính GPA từng kỳ (xu hướng tăng/giảm)
     * - Tính TC trung bình mỗi kỳ thực tế
     * - Dự báo khả năng tốt nghiệp đúng hạn
     * - Số kỳ đã hoàn thành và còn lại
     *
     * @param int $userId
     * @return array
     */
    public function evaluateProgress(int $userId): array
    {
        $user = User::findOrFail($userId);

        // ── 1. Điểm số từ UserGrade (kết quả cuối cùng của từng môn) ───────
        $allUserGrades = UserGrade::where('user_id', $userId)->with('subject')->get();
        $userGrades    = $allUserGrades->groupBy('subject_id')->map(function ($grades) {
            // Lấy điểm cao nhất của mỗi môn (xét cả khi học lại)
            return $grades->sortByDesc('grade')->first();
        })->values();

        $passedGrades = $userGrades->where('status', 'passed');
        $failedGrades = $userGrades->where('status', 'failed');

        $earnedCredits = $passedGrades->sum(fn($g) => $g->subject->credits ?? 0);
        $failedCredits = $failedGrades->sum(fn($g) => $g->subject->credits ?? 0);

        // ── 2. Tổng tín chỉ yêu cầu từ chương trình đào tạo ───────────────
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

        $completionPercentage      = round(($earnedCredits / $totalRequiredCredits) * 100, 2);
        $gradedCreditsPercentage   = round((($earnedCredits + $failedCredits) / $totalRequiredCredits) * 100, 2);
        $remainingCredits          = max(0, $totalRequiredCredits - $earnedCredits);

        // ── 3. GPA tích lũy (trọng số tín chỉ) ────────────────────────────
        $totalScore            = 0;
        $totalAttemptedCredits = 0;
        foreach ($userGrades as $grade) {
            $credit = $grade->subject->credits ?? 0;
            if (is_numeric($grade->grade)) {
                $totalScore            += ($grade->grade * $credit);
                $totalAttemptedCredits += $credit;
            }
        }
        $currentGpa = $totalAttemptedCredits > 0
            ? round($totalScore / $totalAttemptedCredits, 2)
            : 0;

        // ── 4. Tích hợp SemesterHistory: GPA từng kỳ + xu hướng ───────────
        $semesterHistories = SemesterHistory::where('user_id', $userId)
            ->orderBy('semester_number')
            ->get();

        $completedSemesters = $semesterHistories->count();

        // GPA từng kỳ (dạng: [1 => 6.5, 2 => 7.2, ...])
        $semesterGpas = $semesterHistories->pluck('gpa', 'semester_number')->toArray();

        // TC trung bình mỗi kỳ thực tế (từ lịch sử)
        $avgCreditsPerSem = 0;
        if ($completedSemesters > 0) {
            $totalPassedInHistory = $semesterHistories->sum('passed_credits');
            $avgCreditsPerSem     = round($totalPassedInHistory / $completedSemesters, 1);
        }

        // Xu hướng GPA: so sánh nửa sau với nửa đầu
        $gpaTrend = 'stable';
        if ($completedSemesters >= 2) {
            $gpas    = array_values($semesterGpas);
            $mid     = (int) floor(count($gpas) / 2);
            $firstHalf  = count($gpas) > 0 ? array_sum(array_slice($gpas, 0, $mid ?: 1)) / ($mid ?: 1) : 0;
            $secondHalf = count($gpas) > $mid ? array_sum(array_slice($gpas, $mid)) / (count($gpas) - $mid) : $firstHalf;
            if ($secondHalf - $firstHalf > 0.3)       $gpaTrend = 'improving';
            elseif ($firstHalf - $secondHalf > 0.3)   $gpaTrend = 'declining';
        }

        // ── 5. Dự báo tốt nghiệp ───────────────────────────────────────────
        // Dựa trên kế hoạch active (nếu có) hoặc ước tính mặc định 8 kỳ
        $activePlan        = StudyPlan::where('user_id', $userId)->where('is_active', true)->first();
        $targetSemesters   = $activePlan?->target_semester_count ?? ($user->pref_graduation_semester ?? 8);
        $currentSem        = $completedSemesters + 1; // Học kỳ tiếp theo
        $remainingSemesters = max(1, $targetSemesters - $completedSemesters);

        // TC cần đạt mỗi kỳ từ giờ trở đi
        $creditsPerSemNeeded = round($remainingCredits / $remainingSemesters, 1);

        // Ngưỡng tín chỉ tối đa theo cường độ
        $maxCreditsByMode = [
            'fast'  => 22,
            'normal'=> 20,
            'slow'  => 14,
        ];
        $planMode         = $activePlan?->mode ?? 'normal';
        $maxCreditAllowed = $maxCreditsByMode[$planMode] ?? 20;

        // Đánh giá khả năng tốt nghiệp đúng hạn
        $canGraduateOnTime = $creditsPerSemNeeded <= $maxCreditAllowed;
        $graduationRisk    = match(true) {
            $creditsPerSemNeeded > 25  => 'critical',   // Gần như không thể
            $creditsPerSemNeeded > 22  => 'high',       // Rủi ro cao
            $creditsPerSemNeeded > 18  => 'moderate',   // Cần chú ý
            default                    => 'low',        // Ổn định
        };

        // Dự kiến số kỳ cần thêm nếu học theo pace hiện tại
        $estimatedExtraSems = 0;
        if ($avgCreditsPerSem > 0 && $remainingCredits > 0) {
            $semsNeededAtCurrentPace = ceil($remainingCredits / $avgCreditsPerSem);
            $estimatedExtraSems      = max(0, $semsNeededAtCurrentPace - $remainingSemesters);
        }

        return [
            // Kết quả cũ (giữ nguyên để không phá vỡ API hiện tại)
            'earned_credits'             => $earnedCredits,
            'total_required_credits'     => $totalRequiredCredits,
            'completion_percentage'      => $completionPercentage,
            'current_gpa'                => $currentGpa,
            'passed_subjects_count'      => $passedGrades->count(),
            'failed_subjects_count'      => $failedGrades->count(),
            'graded_credits_percentage'  => $gradedCreditsPercentage,

            // Dữ liệu mới từ SemesterHistory
            'completed_semesters'        => $completedSemesters,
            'semester_gpas'              => $semesterGpas,      // [1 => 6.5, 2 => 7.2, ...]
            'gpa_trend'                  => $gpaTrend,           // improving | declining | stable
            'avg_credits_per_sem'        => $avgCreditsPerSem,   // TC trung bình mỗi kỳ thực tế

            // Dự báo tốt nghiệp
            'remaining_credits'          => $remainingCredits,
            'remaining_semesters'        => $remainingSemesters,
            'credits_per_sem_needed'     => $creditsPerSemNeeded,
            'can_graduate_on_time'       => $canGraduateOnTime,
            'graduation_risk'            => $graduationRisk,     // low | moderate | high | critical
            'estimated_extra_sems'       => $estimatedExtraSems, // Số kỳ trễ dự kiến
            'target_semesters'           => $targetSemesters,
            'current_semester'           => $currentSem,
        ];
    }

    /**
     * Sinh cảnh báo học vụ dựa trên tiến độ học tập của sinh viên.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function generateWarnings(int $userId)
    {
        $progress = $this->evaluateProgress($userId);

        // 1. Cảnh báo GPA thấp (nguy cơ học vụ)
        if ($progress['current_gpa'] > 0 && $progress['current_gpa'] < 2.0) {
            Warning::firstOrCreate(
                ['user_id' => $userId, 'type' => 'low_gpa'],
                ['message' => 'Cảnh báo: GPA tích lũy dưới 2.0. Bạn có nguy cơ bị buộc thôi học. Liên hệ cố vấn học tập ngay!']
            );
        } elseif ($progress['current_gpa'] > 0 && $progress['current_gpa'] < 5.0) {
            Warning::firstOrCreate(
                ['user_id' => $userId, 'type' => 'critical_gpa'],
                ['message' => "Cảnh báo: GPA {$progress['current_gpa']} đang rất thấp. Cần cải thiện ngay để tránh bị cảnh báo học vụ."]
            );
        }

        // 2. Cảnh báo nợ môn
        if ($progress['failed_subjects_count'] >= 3) {
            Warning::firstOrCreate(
                ['user_id' => $userId, 'type' => 'debt'],
                ['message' => "Cảnh báo: Bạn đang nợ {$progress['failed_subjects_count']} môn. Hãy ưu tiên học lại để không ảnh hưởng tốt nghiệp."]
            );
        }

        // 3. Cảnh báo nguy cơ trễ tốt nghiệp
        if ($progress['graduation_risk'] === 'high' || $progress['graduation_risk'] === 'critical') {
            $extra = $progress['estimated_extra_sems'];
            Warning::firstOrCreate(
                ['user_id' => $userId, 'type' => 'graduation_delay'],
                ['message' => "Cảnh báo: Dựa trên tiến độ hiện tại, bạn có nguy cơ trễ tốt nghiệp khoảng {$extra} học kỳ. Xem xét điều chỉnh kế hoạch học tập."]
            );
        }

        // 4. Cảnh báo xu hướng GPA giảm
        if ($progress['gpa_trend'] === 'declining' && $progress['completed_semesters'] >= 2) {
            Warning::firstOrCreate(
                ['user_id' => $userId, 'type' => 'gpa_declining'],
                ['message' => 'Lưu ý: GPA của bạn đang có xu hướng giảm so với các kỳ trước. Hãy xem xét lại phương pháp học tập.']
            );
        }

        return Warning::where('user_id', $userId)->where('is_read', false)->get();
    }
}
