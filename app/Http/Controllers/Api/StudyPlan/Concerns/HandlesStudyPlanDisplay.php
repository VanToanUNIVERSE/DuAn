<?php

namespace App\Http\Controllers\Api\StudyPlan\Concerns;

use App\Models\CurriculumSubject;
use App\Models\SemesterHistory;
use App\Models\StudyPlan;
use App\Models\Subject;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Models\UserGrade;
use Illuminate\Support\Collection;

trait HandlesStudyPlanDisplay
{
    private function attachGrades(StudyPlan $plan, int $userId): StudyPlan
    {
        // Tự sửa các kỳ bị trùng semester_index (bug cũ do race condition khi rải
        // học lại) ngay khi hiển thị, để kế hoạch đã bị lỗi từ trước cũng tự lành.
        $this->planService->mergeDuplicateSemesters($plan);

        $plan->loadMissing(
            'semesters.subjects.subject.prerequisites',
            'semesters.subjects.subject.corequisites',
            'semesters.subjects.subject.relatedRelations'
        );

        $userGrades    = UserGrade::where('user_id', $userId)->pluck('grade', 'subject_id')->toArray();
        $passedIds     = array_keys(array_filter($userGrades, fn($g) => $g !== null && $g >= 5.0));
        $allSubjectsMap = Subject::all()->keyBy('id');

        $electiveGroupMap  = $this->loadElectiveGroupMap($userId, $plan);
        $allElectiveGroups = $this->loadAllElectiveGroupSubjects($userId);
        $homeSemesters     = $this->loadElectiveGroupHomeSemesters($userId);
        $emittedGroupIds   = [];
        $renderedGroupIds  = []; // nhóm đã vẽ khung ở một học kỳ TRƯỚC → không vẽ lại

        // Bản đồ TẤT CẢ dòng môn theo subject_id (XUYÊN học kỳ) — để khung nhóm tự chọn
        // đếm được cả môn học lại nằm ở học kỳ khác ("đếm xuyên học kỳ").
        $planRowsBySubject = [];
        foreach ($plan->semesters as $sem) {
            foreach ($sem->subjects as $ss) {
                $planRowsBySubject[$ss->subject_id][] = [
                    'grade'     => $ss->subject_grade,
                    'is_retake' => (bool) $ss->is_retake,
                    'sem'       => (int) $sem->semester_index,
                ];
            }
        }

        // Nhóm tự chọn TỪNG có môn RỚT:
        //  • $groupHasFailure: môn ĐÃ CHẤM hiện thành "khung Đã học" theo từng kỳ (kể cả
        //    môn học lại đã đậu) → luôn giữ ngữ cảnh nhóm ở mỗi kỳ, không thành thẻ lẻ.
        //  • $groupShortfall: nhóm CÒN THIẾU TC (chưa đủ TC đậu) → thêm "khung Học lại" để chọn bù.
        $groupHasFailure = [];
        $groupShortfall  = [];
        foreach ($allElectiveGroups as $gid => $groupData) {
            $required = (int) $groupData['required_credits'];
            $passedCr = 0; $hasFailed = false; $nonPassed = [];
            foreach ($groupData['subjects'] as $m) {
                $passed = false; $failed = false;
                foreach ($planRowsBySubject[$m->id] ?? [] as $r) {
                    if ($r['grade'] !== null) { $r['grade'] >= 5.0 ? $passed = true : $failed = true; }
                }
                if ($passed) $passedCr += (int) ($m->credits ?? 0);
                else        $nonPassed[] = $m;
                if ($failed) $hasFailed = true;
            }
            if ($hasFailed) {
                $groupHasFailure[$gid] = $passedCr; // tổng TC ĐẬU của nhóm (cho counter khung "Đã học")
                if ($passedCr < $required) {
                    $groupShortfall[$gid] = [
                        'name'       => $groupData['name'],
                        'remaining'  => $required - $passedCr,
                        'passed_cr'  => $passedCr,
                        'non_passed' => $nonPassed,
                    ];
                }
            }
        }

        // Duyệt theo thứ tự học kỳ để "kỳ đầu tiên" của nhóm là kỳ sớm nhất.
        foreach ($plan->semesters->sortBy('semester_index') as $sem) {
            foreach ($sem->subjects as $ss) {
                if (!$ss->subject) continue;

                $ss->grade        = $ss->subject_grade;
                $ss->is_completed = $ss->grade !== null && $ss->grade >= 5.0;
                $ss->is_failed    = $ss->grade !== null && $ss->grade < 5.0;

                $ss->subject->prerequisites_info = $this->buildPrereqDetails($ss->subject, $passedIds, $allSubjectsMap);

                $dependentCount = $ss->subject->relatedRelations->where('type', 'prerequisite')->count();
                $ss->is_highly_recommended = $dependentCount >= 2
                    || in_array($ss->subject->requirement_type, ['completed_basic', 'completed_major']);

                $eg = $electiveGroupMap[$ss->subject_id] ?? null;
                $ss->subject->elective_group_id         = $eg?->elective_group_id;
                $ss->subject->elective_group_name       = $eg?->electiveGroup?->name;
                $ss->subject->elective_required_credits = $eg?->electiveGroup?->required_credits;
            }

            $semGroupPlanIds = [];
            $semHistoryIds   = []; // nhóm thiếu TC: môn ĐÃ CHẤM ĐIỂM ở kỳ này → khung "Đã học"
            foreach ($sem->subjects as $ss) {
                $eg = $electiveGroupMap[$ss->subject_id] ?? null;
                if ($eg?->elective_group_id) {
                    $gid = $eg->elective_group_id;
                    // Nhóm TỪNG có môn rớt: GIỮ khung "Đã học" ở mỗi kỳ (read-only, gồm môn
                    // đã chấm điểm — kể cả môn học lại đã đậu) để luôn thấy ngữ cảnh nhóm;
                    // phần còn thiếu (nếu có) gom vào "khung Học lại" ở kỳ tương lai.
                    if (isset($groupHasFailure[$gid])) {
                        if ($ss->subject_grade !== null) $semHistoryIds[$gid][] = $ss->subject_id;
                        $emittedGroupIds[$gid] = true;
                        continue;
                    }
                    // Nhóm đã vẽ khung ở kỳ trước → KHÔNG vẽ lại.
                    if (isset($renderedGroupIds[$gid])) continue;
                    $semGroupPlanIds[$gid][] = $ss->subject_id;
                    $emittedGroupIds[$gid]   = true;
                }
            }

            // Đánh dấu các nhóm vừa vẽ ở kỳ này để các kỳ sau không vẽ lại
            foreach (array_keys($semGroupPlanIds) as $gid) {
                $renderedGroupIds[$gid] = true;
            }

            $semElectiveGroups = [];

            // Khung "ĐÃ HỌC" (lịch sử, read-only) cho nhóm thiếu TC — chỉ các môn đã chấm ở kỳ này
            foreach ($semHistoryIds as $gid => $gradedIds) {
                $groupData = $allElectiveGroups[$gid] ?? null;
                if (!$groupData) continue;
                $frameIdx = (int) $sem->semester_index;
                $histOpts = [];
                foreach ($groupData['subjects'] as $m) {
                    if (in_array($m->id, $gradedIds)) {
                        $histOpts[] = $this->buildElectiveOption($m, $planRowsBySubject, $frameIdx);
                    }
                }
                $semElectiveGroups[] = [
                    'id'               => $gid,
                    'name'             => $groupData['name'],
                    'required_credits' => $groupData['required_credits'],
                    'is_history_group' => true,
                    'passed_credits'   => $groupHasFailure[$gid] ?? 0, // tổng TC đậu cả nhóm
                    'options'          => $histOpts,
                ];
            }

            foreach ($semGroupPlanIds as $gid => $planSubjectIds) {
                $groupData = $allElectiveGroups[$gid] ?? null;
                if (!$groupData) continue;

                $frameIdx = (int) $sem->semester_index;
                $semElectiveGroups[] = [
                    'id'               => $gid,
                    'name'             => $groupData['name'],
                    'required_credits' => $groupData['required_credits'],
                    'options'          => array_map(
                        fn($m) => $this->buildElectiveOption($m, $planRowsBySubject, $frameIdx),
                        $groupData['subjects']
                    ),
                ];
            }

            $sem->setAttribute('elective_groups', $semElectiveGroups);
        }

        // ── Nhóm tự chọn đã bị BỎ CHỌN HẾT (0 môn trong kế hoạch) ───────────────
        // Vẫn phát ra khung nhóm tại "học kỳ nhà" (theo khung chương trình) để nhóm
        // không biến mất khỏi lịch và sinh viên có thể chọn lại. Nhóm tự chọn là
        // yêu cầu tốt nghiệp bắt buộc nên không được phép ẩn đi khi trống.
        $currentSem = $this->detectCurrentSemester($plan, $userId);
        foreach ($allElectiveGroups as $gid => $groupData) {
            if (isset($emittedGroupIds[$gid])) continue;

            // Kẹp về ít nhất học kỳ hiện tại để khung nhóm không rơi vào kỳ đã qua
            // (kỳ đã qua bị khóa, không bấm chọn lại được).
            $homeSem   = max($homeSemesters[$gid] ?? $currentSem, $currentSem);
            $targetSem = $plan->semesters->firstWhere('semester_index', $homeSem)
                ?? $plan->semesters->sortByDesc('semester_index')->first();
            if (!$targetSem) continue;

            $groups   = $targetSem->elective_groups ?? [];
            $groups[] = [
                'id'               => $gid,
                'name'             => $groupData['name'],
                'required_credits' => $groupData['required_credits'],
                'options'          => array_map(fn($m) => [
                    'id'       => $m->id,
                    'name'     => $m->name,
                    'code'     => $m->subject_code ?? '',
                    'credits'  => (int) ($m->credits ?? 0),
                    'selected' => false,
                ], $groupData['subjects']),
            ];
            $targetSem->setAttribute('elective_groups', $groups);
        }

        // ── Khung "HỌC LẠI" cho nhóm thiếu TC (có môn rớt) ─────────────────────
        // Đặt ở kỳ tương lai (nơi đang chọn môn bù, hoặc kỳ hiện tại), gồm các môn
        // CHƯA ĐẬU, required = TC còn thiếu. Sinh viên chọn thoải mái trong khung này.
        foreach ($groupShortfall as $gid => $info) {
            $anchorIdx = $currentSem;
            foreach ($info['non_passed'] as $m) {
                foreach ($planRowsBySubject[$m->id] ?? [] as $r) {
                    if ($r['grade'] === null && !$r['is_retake']) {
                        $anchorIdx = max($anchorIdx, $r['sem']);
                    }
                }
            }
            $targetSem = $plan->semesters->firstWhere('semester_index', $anchorIdx)
                ?? $plan->semesters->sortByDesc('semester_index')->first();
            if (!$targetSem) continue;

            $frameIdx = (int) $targetSem->semester_index;
            $groups   = $targetSem->elective_groups ?? [];
            $groups[] = [
                'id'               => $gid,
                'name'             => $info['name'],
                'required_credits' => $info['remaining'],   // TC còn thiếu
                'is_retake_group'  => true,
                'passed_credits'   => $info['passed_cr'],
                'options'          => array_map(
                    fn($m) => $this->buildElectiveOption($m, $planRowsBySubject, $frameIdx),
                    $info['non_passed']
                ),
            ];
            $targetSem->setAttribute('elective_groups', $groups);
        }

        return $plan;
    }

    /**
     * Dựng dữ liệu một phương án trong nhóm tự chọn, tính trạng thái XUYÊN học kỳ.
     * $frameSemIdx = học kỳ đang vẽ khung (để biết môn có "đang chọn ở khung này" không).
     */
    private function buildElectiveOption($m, array $planRowsBySubject, int $frameSemIdx): array
    {
        $hasPassed = false; $hasPendingNew = false; $failed = false;
        $retakePendingSem = null; $selectedSem = null; $selectedHere = false;
        $failedSem = null; $failedGrade = null;

        foreach ($planRowsBySubject[$m->id] ?? [] as $r) {
            if ($r['grade'] !== null && $r['grade'] >= 5.0) {
                $hasPassed = true; $selectedSem = $r['sem'];
            } elseif ($r['grade'] !== null) {
                $failed = true; $failedSem = $r['sem']; $failedGrade = $r['grade']; // rớt
            } else {
                if ($r['is_retake']) $retakePendingSem = $r['sem'];
                else { $hasPendingNew = true; $selectedSem = $r['sem']; }
            }
            if ($r['sem'] === $frameSemIdx) $selectedHere = true;
        }

        return [
            'id'                 => $m->id,
            'name'               => $m->name,
            'code'               => $m->subject_code ?? '',
            'credits'            => (int) ($m->credits ?? 0),
            'selected'           => $selectedHere,         // có dòng ở chính kỳ vẽ khung
            'passed'             => $hasPassed,
            'failed'             => $failed,
            'failed_sem'         => $failedSem,            // kỳ đã rớt (để xếp học lại đúng chỗ)
            'failed_grade'       => $failedGrade,
            // Tính vào nhóm nếu: đậu, đang học (chọn mới), HOẶC đang học lại.
            // → bộ đếm phản ánh đúng tiến độ; muốn đổi sang môn khác thì bỏ chọn.
            'effective_selected' => $hasPassed || $hasPendingNew || $retakePendingSem !== null,
            'retake_pending_sem' => $retakePendingSem,
            'selected_sem'       => $selectedSem,
        ];
    }

    private function loadElectiveGroupMap(int $userId, StudyPlan $plan): array
    {
        $frameworkId = $this->resolveFrameworkId($userId);
        if (!$frameworkId) return [];

        $subjectIds = $plan->semesters
            ->flatMap(fn($s) => $s->subjects->pluck('subject_id'))
            ->unique()->toArray();

        return CurriculumSubject::where('curriculum_framework_id', $frameworkId)
            ->whereIn('subject_id', $subjectIds)
            ->whereNotNull('elective_group_id')
            ->with('electiveGroup')
            ->get()
            ->keyBy('subject_id')
            ->all();
    }

    private function loadAllElectiveGroupSubjects(int $userId): array
    {
        $frameworkId = $this->resolveFrameworkId($userId);
        if (!$frameworkId) return [];

        $groups = [];
        \App\Models\ElectiveGroup::where('curriculum_framework_id', $frameworkId)
            ->with('subjects:id,name,subject_code,credits')
            ->each(function ($eg) use (&$groups) {
                $groups[$eg->id] = [
                    'id'               => $eg->id,
                    'name'             => $eg->name,
                    'required_credits' => $eg->required_credits,
                    'subjects'         => $eg->subjects->all(),
                ];
            });

        return $groups;
    }

    /**
     * Học kỳ "nhà" của mỗi nhóm tự chọn theo khung chương trình (semester nhỏ nhất
     * mà nhóm được gán). Dùng để hiển thị lại nhóm khi nó đã bị bỏ chọn hết môn.
     *
     * @return array<int,int>  [elective_group_id => semester_index]
     */
    private function loadElectiveGroupHomeSemesters(int $userId): array
    {
        $frameworkId = $this->resolveFrameworkId($userId);
        if (!$frameworkId) return [];

        $home = [];
        CurriculumSubject::where('curriculum_framework_id', $frameworkId)
            ->whereNotNull('elective_group_id')
            ->with('semester')
            ->get()
            ->each(function ($cs) use (&$home) {
                $semIdx = (int) ($cs->semester?->name ?? 0);
                $gid    = $cs->elective_group_id;
                if ($semIdx > 0 && (!isset($home[$gid]) || $semIdx < $home[$gid])) {
                    $home[$gid] = $semIdx;
                }
            });

        return $home;
    }

    /**
     * Môn này có phải môn TỰ CHỌN (thuộc một nhóm tự chọn) trong khung của user không.
     * Dùng để phân biệt xử lý khi rớt: bắt buộc → tự học lại; tự chọn → để SV tự quyết.
     */
    private function isElectiveSubject(int $userId, int $subjectId): bool
    {
        $frameworkId = $this->resolveFrameworkId($userId);
        if (!$frameworkId) return false;

        return CurriculumSubject::where('curriculum_framework_id', $frameworkId)
            ->where('subject_id', $subjectId)
            ->whereNotNull('elective_group_id')
            ->exists();
    }

    private function resolveFrameworkId(int $userId): ?int
    {
        $user = User::find($userId);
        if (!$user?->pref_academic_year || !$user?->pref_program_type) return null;

        $program = TrainingProgram::where('academic_year', $user->pref_academic_year)
            ->where('program_type', $user->pref_program_type)
            ->first();

        return $program?->curriculumFrameworks()->first()?->id;
    }

    private function buildPrereqDetails(object $subject, array $passedIds, Collection $allSubjectsMap): array
    {
        $details = [];

        foreach ($subject->prerequisites ?? [] as $prereq) {
            $details[$prereq->id] = [
                'id'        => $prereq->id,
                'name'      => $prereq->name,
                'is_passed' => in_array($prereq->id, $passedIds),
                'type'      => 'explicit',
            ];
        }

        foreach ($subject->corequisites ?? [] as $coreq) {
            $details['co_' . $coreq->id] = [
                'id'        => $coreq->id,
                'name'      => $coreq->name,
                'is_passed' => in_array($coreq->id, $passedIds),
                'type'      => 'corequisite',
            ];
        }

        $req = $subject->requirement_type ?? null;
        if ($req && $req !== 'none') {
            $groupLabel = match ($req) {
                'completed_basic'       => 'Đại cương',
                'completed_major'       => 'Cơ sở ngành',
                'completed_specialized' => 'Chuyên ngành',
                'completed_all'         => 'Toàn bộ',
                default                 => $req,
            };
            $details["_group_{$req}"] = [
                'id'        => null,
                'name'      => "Hoàn thành khối {$groupLabel}",
                'is_passed' => false,
                'type'      => 'group',
            ];
        }

        return array_values($details);
    }

    private function syncUserGrade(int $userId, int $subjectId, StudyPlan $plan): void
    {
        $grade = null;
        foreach ($plan->semesters as $sem) {
            foreach ($sem->subjects as $ss) {
                if ($ss->subject_id === $subjectId && $ss->subject_grade !== null) {
                    $grade = $ss->subject_grade;
                    break 2;
                }
            }
        }

        if ($grade === null) {
            UserGrade::where('user_id', $userId)->where('subject_id', $subjectId)->delete();
            return;
        }

        UserGrade::updateOrCreate(
            ['user_id' => $userId, 'subject_id' => $subjectId],
            ['grade' => $grade, 'status' => $grade >= 5.0 ? 'pass' : 'fail']
        );
    }

    private function detectCurrentSemester(StudyPlan $plan, int $userId): int
    {
        $lastHistory = SemesterHistory::where('user_id', $userId)->max('semester_number');
        if ($lastHistory) return (int) $lastHistory + 1;

        foreach ($plan->semesters->sortBy('semester_index') as $sem) {
            if ($sem->subjects->some(fn($ss) => !$ss->is_completed && !$ss->is_retake)) {
                return (int) $sem->semester_index;
            }
        }

        return 1;
    }
}
