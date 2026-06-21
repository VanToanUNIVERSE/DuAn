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
        $skillAverages = [];

        if ($userId) {
            // \u0110\u1ecdc t\u1eeb UserGrade \u2014 single source of truth
            // \u0110\u01b0\u1ee3c c\u1eadp nh\u1eadt b\u1edfi: syncUserGrade() khi nh\u1eadp \u0111i\u1ec3m, SemesterHistoryController khi ho\u00e0n t\u1ea5t k\u1ef3
            $passedSubjects = UserGrade::where('user_id', $userId)
                ->where('status', 'pass')
                ->pluck('subject_id')
                ->toArray();

            $failedSubjects = UserGrade::where('user_id', $userId)
                ->where('status', 'fail')
                ->pluck('subject_id')
                ->toArray();

            // Truy v\u1ea5n trung b\u00ecnh \u0111i\u1ec3m (GPA) theo t\u1eebng nh\u00f3m k\u1ef9 n\u0103ng
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
            // Kh\u00f4ng \u0111\u0103ng nh\u1eadp: d\u00f9ng danh s\u00e1ch t\u1eeb client (ch\u1ebf \u0111\u1ed9 kh\u00e1ch)
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

            $isRetake = in_array($subject->id, $failedSubjects);

            // Môn rớt: sinh viên đã từng học qua (điều kiện tiên quyết đã đạt lúc đó)
            // → luôn cho phép học lại, không cần kiểm tra prerequisite lại
            if ($isRetake) {
                $canStudy = true;
            }

            $subject->can_study  = $canStudy;
            $subject->is_retake  = $isRetake;
            $subject->prerequisites_info = $prereqDetails;

            $corequisites = SubjectRelation::where('type', 'corequisite')
                ->where(function($query) use ($subject) {
                    $query->where('subject_id', $subject->id)
                          ->orWhere('related_subject_id', $subject->id);
                })
                ->with(['subject', 'relatedSubject'])
                ->get();
            $coreqDetails = [];
            foreach ($corequisites as $coreq) {
                $related = ($coreq->subject_id == $subject->id) ? $coreq->relatedSubject : $coreq->subject;
                if ($related) {
                    $coreqDetails[] = [
                        'id' => $related->id,
                        'code' => $related->subject_code,
                        'name' => $related->name,
                        'credits' => $related->credits,
                    ];
                }
            }
            $subject->corequisites_info = $coreqDetails;

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

            // Môn rớt: ưu tiên tối cao — phải học lại ngay
            if ($isRetake) {
                $score += 200;
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
        $suggestions = array_slice($suggestions, 0, 25);

        return $suggestions;
    }
}