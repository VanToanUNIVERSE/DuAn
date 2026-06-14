<?php
namespace App\Services;

use App\Models\SubjectRelation;
use App\Models\UserGrade;
use App\Models\Subject;
use App\Models\TrainingProgram;
use App\Models\ProgramGroup;
use Illuminate\Support\Facades\DB;

class SuggestionService
{
    private function translateReqType($type) {
        $map = [
            'completed_basic' => 'Hoàn thành khối Đại cương',
            'completed_major' => 'Hoàn thành khối Cơ sở ngành',
            'completed_specialized' => 'Hoàn thành khối Chuyên ngành',
            'completed_all' => 'Hoàn thành tất cả môn trước đó'
        ];
        return $map[$type] ?? 'Khác';
    }

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

        $skillAverages = [];

        if ($userId) {
            // Nếu có đăng nhập, ưu tiên lấy từ database (đã được cập nhật ở bước 1)
            $passedSubjects = UserGrade::where('user_id', $userId)
                ->where('status', 'pass')
                ->pluck('subject_id')
                ->toArray();
                
            $failedSubjects = UserGrade::where('user_id', $userId)
                ->where('status', 'fail')
                ->pluck('subject_id')
                ->toArray();
                
            // Truy vấn trung bình điểm (GPA) theo từng nhóm kỹ năng
            $skillAverages = DB::table('user_grades')
                ->join('subjects', 'user_grades.subject_id', '=', 'subjects.id')
                ->where('user_grades.user_id', $userId)
                ->whereNotNull('user_grades.grade')
                ->whereNotNull('subjects.skill_group_id')
                ->groupBy('subjects.skill_group_id')
                ->select('subjects.skill_group_id', DB::raw('AVG(user_grades.grade) as avg_grade'))
                ->pluck('avg_grade', 'skill_group_id')
                ->toArray();
        } else {
            // Nếu không đăng nhập, sử dụng danh sách truyền từ client lên
            $passedSubjects = $passedSubjectIds ?? [];
            $failedSubjects = [];
        }

        // 3. Xác định phạm vi môn học theo Chương trình đào tạo (nếu có)
        $frameworkId = null;
        if ($academicYear && $programType) {
            $program = TrainingProgram::where('academic_year', $academicYear)
                ->where('program_type', $programType)
                ->first();

            if ($program) {
                $framework = $program->curriculumFrameworks()->first();
                if ($framework) {
                    $frameworkId = $framework->id;
                }
            }
        }

        // 4. Lấy các nhóm môn học để ánh xạ điều kiện ngầm định
        $basicGroupIds = ProgramGroup::where('name', 'like', '%Đại cương%')
            ->orWhere('name', 'like', '%Anh văn%')
            ->pluck('id')->toArray();
            
        $majorGroupIds = ProgramGroup::where('name', 'like', '%Cơ sở ngành%')
            ->pluck('id')->toArray();
            
        $specializedGroupIds = ProgramGroup::where('name', 'like', '%Chuyên ngành%')
            ->pluck('id')->toArray();

        // 5. Truy vấn danh sách môn học thích hợp
        $subjects = collect();
        $allFrameworkSubjects = collect();

        if ($frameworkId) {
            $curriculumSubjects = \App\Models\CurriculumSubject::where('curriculum_framework_id', $frameworkId)
                ->with(['subject', 'semester'])
                ->get();
            
            $allFrameworkSubjects = $curriculumSubjects->pluck('subject')->filter();

            foreach ($curriculumSubjects as $cs) {
                if ($cs->subject && !in_array($cs->subject_id, $passedSubjects)) {
                    $subject = $cs->subject;
                    // Gán tạm học kỳ vào để tương thích với logic cũ
                    $subject->setRelation('semester', $cs->semester);
                    $subjects->push($subject);
                }
            }
        } else {
            // Fallback nếu không có CTĐT
            $allFrameworkSubjects = Subject::all();
            $subjects = Subject::whereNotIn('id', $passedSubjects)->get();
        }

        $basicSubjects = $allFrameworkSubjects->whereIn('program_group_id', $basicGroupIds);
        $majorSubjects = $allFrameworkSubjects->whereIn('program_group_id', $majorGroupIds);
        $specializedSubjects = $allFrameworkSubjects->whereIn('program_group_id', $specializedGroupIds);

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

            // Kiểm tra tiên quyết NGẦM ĐỊNH (dựa trên requirement_type)
            $reqType = $subject->requirement_type;
            if ($reqType && $reqType !== 'none') {
                $implicitPrereqSubjects = collect();
                
                if ($reqType === 'completed_basic') {
                    $implicitPrereqSubjects = $basicSubjects;
                } elseif ($reqType === 'completed_major') {
                    $implicitPrereqSubjects = $majorSubjects;
                } elseif ($reqType === 'completed_specialized') {
                    $implicitPrereqSubjects = $specializedSubjects;
                } elseif ($reqType === 'completed_all') {
                    $implicitPrereqSubjects = $allFrameworkSubjects->where('id', '!=', $subject->id);
                }

                foreach ($implicitPrereqSubjects as $impSub) {
                    // Nếu môn ẩn định này thuộc tiên quyết cứng rồi thì bỏ qua
                    if (collect($prereqDetails)->contains('id', $impSub->id)) continue;

                    $isPassed = in_array($impSub->id, $passedSubjects);
                    if (!$isPassed) {
                        $canStudy = false;
                    }
                    $prereqDetails[] = [
                        'id' => $impSub->id,
                        'name' => $impSub->name,
                        'is_passed' => $isPassed
                    ];
                }
            }

            $subject->can_study = $canStudy;
            $subject->prerequisites_info = $prereqDetails;

            // 5.5 Chấm điểm môn học (Scoring System)
            $score = 100; // Điểm cơ bản

            // Khoảng cách học kỳ (Mỗi kỳ chênh lệch -10đ)
            $distance = abs(($subject->semester?->name ?? 1) - $currentSemester);
            $score -= ($distance * 10);

            // Năng lực theo nhóm kỹ năng
            $skillDesc = null;
            if ($subject->skill_group_id && isset($skillAverages[$subject->skill_group_id])) {
                $avg = (float) $skillAverages[$subject->skill_group_id];
                if ($avg >= 8.0) {
                    $score += 15;
                    $skillDesc = 'Thế mạnh (+15đ)';
                } elseif ($avg >= 6.5) {
                    $score += 5;
                    $skillDesc = 'Khá tốt (+5đ)';
                } elseif ($avg >= 5.0) {
                    $score -= 5;
                    $skillDesc = 'Trung bình (-5đ)';
                } else {
                    $score -= 15;
                    $skillDesc = 'Điểm yếu (-15đ)';
                }
            }

            // Nếu là môn rớt, ưu tiên học lại
            if (in_array($subject->id, $failedSubjects)) {
                $score += 50;
            }

            $subject->suggestion_score = $score;
            $subject->skill_evaluation = $skillDesc;

            $suggestions[] = $subject;
        }

        // 6. Sắp xếp các môn học đề xuất: Môn đủ điều kiện trước, sau đó theo điểm số (suggestion_score) giảm dần
        usort($suggestions, function ($a, $b) {
            // Đủ điều kiện xếp trước
            if ($a->can_study && !$b->can_study) return -1;
            if (!$a->can_study && $b->can_study) return 1;

            // Nếu cùng trạng thái điều kiện, xếp theo điểm số
            return $b->suggestion_score <=> $a->suggestion_score;
        });

        // Tùy chọn giới hạn số lượng gợi ý (ví dụ: 15 môn) để tránh danh sách quá dài
        $suggestions = array_slice($suggestions, 0, 15);

        return $suggestions;
    }
}