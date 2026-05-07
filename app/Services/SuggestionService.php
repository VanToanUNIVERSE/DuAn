<?php
namespace App\Services;

use App\Models\SubjectRelation;
use App\Models\UserGrade;
use App\Models\Subject;

class SuggestionService
{
    public function suggestSubjects($userId, $currentSemester)
    {
        //lấy danh sách môn đã học
        $passedSubjects = UserGrade::where('user_id', $userId)
            ->where('status', 'passed')
            ->pluck('subject_id')
            ->toArray();

        $subjects = Subject::whereNotIn('id', $passedSubjects)->get();
        $suggestions = [];
        foreach ($subjects as $subject) {
            //lấy danh sách môn tiên quyết của môn học hiện tại
            $prerequisites = SubjectRelation::where('subject_id', $subject->id)
                ->where('type', 'prerequisite')
                ->pluck('related_subject_id')
                ->toArray();

            $canStudy = true;
            //xét xem đã học môn tiên quyết chưa
            foreach ($prerequisites as $prerequisite) {
                if (!in_array($prerequisite, $passedSubjects)) {
                    $canStudy = false;
                    break;
                }
            }

            //nếu đủ điều kiện thì thêm vào danh sách gợi ý
            if ($canStudy) {
                $suggestions[] = $subject;
            }
        }
        usort($suggestions, function ($a, $b) use ($currentSemester) {
            // 1. Tính khoảng cách học kỳ của từng môn so với học kỳ hiện tại
            $distanceA = abs($a->subject_semester_id - $currentSemester);
            $distanceB = abs($b->subject_semester_id - $currentSemester);
            // 2. So sánh khoảng cách bằng if - else
            if ($distanceA == $distanceB) {
                return 0; // Nếu khoảng cách bằng nhau, giữ nguyên vị trí
            }
            // Nếu khoảng cách của môn A nhỏ hơn môn B => môn A gần kỳ hiện tại hơn => đưa A lên trước (trả về -1)
            // Ngược lại => đưa B lên trước (trả về 1)
            return ($distanceA < $distanceB) ? -1 : 1;
        });

        return $suggestions;
    }
}