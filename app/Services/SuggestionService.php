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
                ->where('status', 'passed')
                ->delete();

            // Lưu danh sách môn đã đỗ mới
            foreach ($passedSubjectIds as $subId) {
                UserGrade::create([
                    'user_id' => $userId,
                    'subject_id' => $subId,
                    'status' => 'passed',
                ]);
            }
        }

        // 2. Xác định danh sách môn học đã đỗ
        if ($userId) {
            // Nếu có đăng nhập, ưu tiên lấy từ database (đã được cập nhật ở bước 1)
            $passedSubjects = UserGrade::where('user_id', $userId)
                ->where('status', 'passed')
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

        // 5. Lọc danh sách môn học theo điều kiện tiên quyết
        $suggestions = [];
        foreach ($subjects as $subject) {
            $prerequisites = SubjectRelation::where('subject_id', $subject->id)
                ->where('type', 'prerequisite')
                ->pluck('related_subject_id')
                ->toArray();

            $canStudy = true;
            foreach ($prerequisites as $prerequisite) {
                if (!in_array($prerequisite, $passedSubjects)) {
                    $canStudy = false;
                    break;
                }
            }

            if ($canStudy) {
                $suggestions[] = $subject;
            }
        }

        // 6. Sắp xếp các môn học đề xuất theo khoảng cách học kỳ gần nhất
        usort($suggestions, function ($a, $b) use ($currentSemester) {
            $distanceA = abs(($a->semester?->name ?? 1) - $currentSemester);
            $distanceB = abs(($b->semester?->name ?? 1) - $currentSemester);
            if ($distanceA == $distanceB) {
                return 0;
            }
            return ($distanceA < $distanceB) ? -1 : 1;
        });

        return $suggestions;
    }
}