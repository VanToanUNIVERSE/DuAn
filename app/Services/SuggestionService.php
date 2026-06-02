<?php
namespace App\Services;

use App\Models\SubjectRelation;
use App\Models\UserGrade;
use App\Models\Subject;
use App\Models\TrainingProgram;

class SuggestionService
{
    public function suggestSubjects($userId = null, $currentSemester = 1, $academicYear = null, $programType = null, array $passedSubjectIds = null)
    {
        // 1. Nếu có đăng nhập và có truyền danh sách môn đã đỗ từ client, lưu thông tin vào database
        if ($userId && is_array($passedSubjectIds)) {
            // Xóa danh sách môn đã đỗ cũ
            UserGrade::where('user_id', $userId)
                ->where('status', 'pass')
                ->delete();

            // Lưu danh sách môn đã đỗ mới
            foreach ($passedSubjectIds as $subId) {
                UserGrade::updateOrCreate(
                    ['user_id' => $userId, 'subject_id' => $subId],
                    ['status' => 'pass']
                );
            }
        }

        // 2. Xác định danh sách môn học đã đỗ
        if ($userId) {
            // Nếu có đăng nhập, ưu tiên lấy từ database (đã được cập nhật ở bước 1)
            $passedSubjects = UserGrade::where('user_id', $userId)
                ->where('status', 'pass')
                ->pluck('subject_id')
                ->toArray();
        } else {
            // Nếu không đăng nhập, sử dụng danh sách truyền từ client lên
            $passedSubjects = $passedSubjectIds ?? [];
        }

        // 3. Xác định phạm vi môn học theo Chương trình đào tạo (nếu có)
        $semesterIds = null;
        if ($academicYear && $programType) {
            $program = TrainingProgram::where('academic_year', $academicYear)
                ->where('program_type', $programType)
                ->first();

            if ($program) {
                $framework = $program->curriculumFrameworks()->first();
                if ($framework) {
                    $semesterIds = $framework->semesters()->pluck('id')->toArray();
                }
            }
        }

        // 4. Truy vấn danh sách môn học thích hợp
        $query = Subject::with('semester');

        if ($semesterIds !== null) {
            $query->whereIn('semester_id', $semesterIds);
        }

        $subjects = $query->whereNotIn('id', $passedSubjects)->get();

        $suggestions = [];
        foreach ($subjects as $subject) {
            $prerequisites = SubjectRelation::where('subject_id', $subject->id)
                ->where('type', 'prerequisite')
                ->with('relatedSubject')
                ->get();

            $canStudy = true;
            $prereqDetails = [];
            foreach ($prerequisites as $prereq) {
                $isPassed = in_array($prereq->related_subject_id, $passedSubjects);
                if (!$isPassed) {
                    $canStudy = false;
                }
                if ($prereq->relatedSubject) {
                    $prereqDetails[] = [
                        'id' => $prereq->related_subject_id,
                        'name' => $prereq->relatedSubject->name,
                        'is_passed' => $isPassed
                    ];
                }
            }

            $subject->can_study = $canStudy;
            $subject->prerequisites_info = $prereqDetails;
            $suggestions[] = $subject;
        }

        // 6. Sắp xếp các môn học đề xuất: Môn đủ điều kiện trước, sau đó theo khoảng cách học kỳ gần nhất
        usort($suggestions, function ($a, $b) use ($currentSemester) {
            // Đủ điều kiện xếp trước
            if ($a->can_study && !$b->can_study) return -1;
            if (!$a->can_study && $b->can_study) return 1;

            // Nếu cùng trạng thái điều kiện, xếp theo khoảng cách học kỳ
            $distanceA = abs(($a->semester?->name ?? 1) - $currentSemester);
            $distanceB = abs(($b->semester?->name ?? 1) - $currentSemester);
            if ($distanceA == $distanceB) {
                return 0;
            }
            return ($distanceA < $distanceB) ? -1 : 1;
        });

        // Tùy chọn giới hạn số lượng gợi ý (ví dụ: 15 môn) để tránh danh sách quá dài
        $suggestions = array_slice($suggestions, 0, 15);

        return $suggestions;
    }
}