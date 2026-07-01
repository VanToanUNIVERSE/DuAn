<?php

namespace App\Http\Controllers\Api\StudyPlan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\StudyPlan\Concerns\HandlesStudyPlanDisplay;
use App\Http\Requests\StudyPlan\AddRetakeRequest;
use App\Http\Requests\StudyPlan\ApplySuggestionsRequest;
use App\Http\Requests\StudyPlan\MoveSubjectRequest;
use App\Http\Requests\StudyPlan\ToggleElectiveRequest;
use App\Models\ElectiveGroup;
use App\Models\StudyPlan;
use App\Models\StudyPlanSemester;
use App\Models\StudyPlanSubject;
use App\Models\Subject;
use App\Models\SubjectRelation;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Models\UserGrade;
use App\Services\StudyPlanService;
use Illuminate\Support\Facades\Auth;

class StudyPlanSubjectController extends Controller
{
    use HandlesStudyPlanDisplay;

    public function __construct(protected StudyPlanService $planService) {}

    // POST /api/v1/study-plans/move-subject
    public function moveSubject(MoveSubjectRequest $request)
    {
        $userId      = Auth::id();
        $subjectId   = (int) $request->input('subject_id');
        $planSubjectId = $request->filled('plan_subject_id')
            ? (int) $request->input('plan_subject_id')
            : null;
        $targetSemIdx = (int) $request->input('target_semester_index');

        $plan = StudyPlan::with(['semesters.subjects.subject.prerequisites', 'semesters.subjects.subject.corequisites'])
            ->where('id', $request->input('study_plan_id'))
            ->where('user_id', $userId)
            ->firstOrFail();

        $sourcePlanSubject = null;
        $subjectSemMap     = [];
        $targetSemId       = null;

        foreach ($plan->semesters as $sem) {
            if ($sem->semester_index === $targetSemIdx) $targetSemId = $sem->id;
            foreach ($sem->subjects as $ss) {
                if (!isset($subjectSemMap[$ss->subject_id])
                    || $sem->semester_index < $subjectSemMap[$ss->subject_id]) {
                    $subjectSemMap[$ss->subject_id] = $sem->semester_index;
                }

                if ($planSubjectId && $ss->id === $planSubjectId) {
                    $sourcePlanSubject = $ss;
                } elseif (!$planSubjectId && $ss->subject_id === $subjectId) {
                    // Client cũ chưa gửi row id: ưu tiên đúng dòng học lại, sau đó
                    // mới tới dòng chưa có điểm; không mặc định lấy bản lịch sử đầu tiên.
                    $shouldPrefer = !$sourcePlanSubject
                        || ($ss->is_retake && !$sourcePlanSubject->is_retake)
                        || ($ss->subject_grade === null && $sourcePlanSubject->subject_grade !== null);
                    if ($shouldPrefer) $sourcePlanSubject = $ss;
                }
            }
        }

        if (!$sourcePlanSubject) {
            return response()->json(['error' => 'Môn không tồn tại trong kế hoạch.'], 404);
        }
        if ($sourcePlanSubject->subject_id !== $subjectId) {
            return response()->json(['error' => 'Dòng kế hoạch không khớp với môn được kéo.'], 422);
        }

        // Bản lịch sử gốc có điểm luôn bị khóa. Riêng một lần HỌC LẠI đã rớt
        // vẫn được phép đổi kỳ để sinh viên sắp xếp lần học tiếp theo.
        $isFailedRetake = $sourcePlanSubject->is_retake
            && $sourcePlanSubject->subject_grade !== null
            && $sourcePlanSubject->subject_grade < 5.0;
        if ($sourcePlanSubject->subject_grade !== null && !$isFailedRetake) {
            return response()->json([
                'error' => 'Không thể di chuyển môn đã có điểm — đây là lịch sử lần học. Hãy kéo dòng "Học lại" nếu muốn đổi học kỳ.'
            ], 422);
        }

        if ($sourcePlanSubject->is_retake
            && $sourcePlanSubject->original_attempt_sem
            && $targetSemIdx <= $sourcePlanSubject->original_attempt_sem) {
            return response()->json([
                'error' => "Học lại chỉ được xếp sau lần học gốc ở Học kỳ {$sourcePlanSubject->original_attempt_sem}."
            ], 422);
        }

        foreach ($sourcePlanSubject->is_retake ? [] : ($sourcePlanSubject->subject->prerequisites ?? []) as $prereq) {
            $prereqSem = $subjectSemMap[$prereq->id] ?? null;
            if ($prereqSem === null) {
                $passed = UserGrade::where('user_id', $userId)
                    ->where('subject_id', $prereq->id)
                    ->where('grade', '>=', 5.0)->exists();
                if (!$passed) {
                    return response()->json(['error' => "Chưa hoàn thành tiên quyết: «{$prereq->name}»."], 422);
                }
            } elseif ($prereqSem >= $targetSemIdx) {
                return response()->json([
                    'error' => "Tiên quyết «{$prereq->name}» đang ở Học kỳ {$prereqSem} — không thể kéo môn này lên Học kỳ {$targetSemIdx}."
                ], 422);
            }
        }

        if (!$targetSemId) {
            $targetSemId = StudyPlanSemester::create([
                'study_plan_id'    => $plan->id,
                'semester_index'   => $targetSemIdx,
                'expected_credits' => 0,
            ])->id;
        }

        $sourceSemIdx = (int) ($plan->semesters
            ->firstWhere('id', $sourcePlanSubject->study_plan_semester_id)
            ?->semester_index ?? 0);
        $sourcePlanSubject->update(['study_plan_semester_id' => $targetSemId]);

        // Kéo corequisites theo (BFS). Dùng quan hệ hai chiều và chọn đúng phiên
        // bản retake ở cùng kỳ nguồn, tránh kéo nhầm bản lịch sử gốc.
        $plan->load('semesters.subjects.subject');
        $planSubjectRowsBySubject = [];
        foreach ($plan->semesters as $sem) {
            foreach ($sem->subjects as $ss) {
                $planSubjectRowsBySubject[$ss->subject_id][] = [
                    'row' => $ss,
                    'semester_index' => (int) $sem->semester_index,
                ];
            }
        }

        $coreqAdjacency = [];
        $planSubjectIds = array_keys($planSubjectRowsBySubject);
        $coreqRelations = SubjectRelation::where('type', 'corequisite')
            ->where(function ($query) use ($planSubjectIds) {
                $query->whereIn('subject_id', $planSubjectIds)
                    ->orWhereIn('related_subject_id', $planSubjectIds);
            })
            ->get(['subject_id', 'related_subject_id']);
        foreach ($coreqRelations as $relation) {
            $left  = (int) $relation->subject_id;
            $right = (int) $relation->related_subject_id;
            $coreqAdjacency[$left][]  = $right;
            $coreqAdjacency[$right][] = $left;
        }
        $subjectNames = Subject::whereIn(
            'id',
            array_unique(array_merge($planSubjectIds, ...array_values($coreqAdjacency ?: [[]])))
        )->pluck('name', 'id');

        $movedCoreqNames = [];
        $coQueue  = [$subjectId];
        $coPulled = [$subjectId => true];

        while (!empty($coQueue)) {
            $checkId = (int) array_shift($coQueue);

            foreach ($coreqAdjacency[$checkId] ?? [] as $coreqId) {
                $coreqId = (int) $coreqId;
                if (isset($coPulled[$coreqId])) continue;
                $coPulled[$coreqId] = true;

                $candidates = collect($planSubjectRowsBySubject[$coreqId] ?? [])
                    ->filter(function ($candidate) use ($sourcePlanSubject) {
                        $row = $candidate['row'];
                        if ($row->is_completed) return false;

                        return $sourcePlanSubject->is_retake
                            ? (bool) $row->is_retake
                            : (!$row->is_retake && $row->subject_grade === null);
                    })
                    ->sortByDesc(function ($candidate) use ($sourceSemIdx) {
                        $row = $candidate['row'];

                        return ($candidate['semester_index'] === $sourceSemIdx ? 100 : 0)
                            + ($row->subject_grade === null ? 10 : 5);
                    });
                $coreqSs = $candidates->first()['row'] ?? null;
                if (!$coreqSs) continue;

                $coreqSs->update(['study_plan_semester_id' => $targetSemId]);
                $movedCoreqNames[] = $subjectNames[$coreqId] ?? "Môn #{$coreqId}";
                $coQueue[]         = $coreqId;
            }
        }

        $plan->load('semesters.subjects.subject');
        foreach ($plan->semesters as $sem) {
            $sem->update(['expected_credits' => $sem->subjects->sum(fn($ss) => $ss->subject?->credits ?? 0)]);
        }

        $message = 'Đã di chuyển môn học.';
        if (!empty($movedCoreqNames)) {
            $message .= ' Môn song hành đi theo: ' . implode(', ', $movedCoreqNames) . '.';
        }

        return response()->json([
            'success'      => true,
            'message'      => $message,
            'coreqs_moved' => $movedCoreqNames,
            'data'         => $this->attachGrades($plan->load('semesters.subjects.subject'), $userId),
        ]);
    }

    // POST /api/v1/study-plans/apply-suggestions
    public function applySuggestions(ApplySuggestionsRequest $request)
    {
        $userId  = Auth::id();
        $plan    = StudyPlan::where('id', $request->input('study_plan_id'))
            ->where('user_id', $userId)->firstOrFail();
        $creditCap = max(1, min(22, (int) ($plan->tc_per_sem ?? 18)));
        $selection = $this->limitSuggestionsToCreditCap(
            $request->input('subject_ids'),
            $creditCap,
            $userId
        );

        if (empty($selection['ids'])) {
            return response()->json([
                'success' => false,
                'message' => "Không có cụm môn phù hợp trong giới hạn {$creditCap} TC/kỳ.",
            ], 422);
        }

        $updated = $this->planService->redistributeFrom(
            $plan,
            (int) $request->input('target_semester_index'),
            $selection['ids']
        );

        $message = "Đã áp dụng {$selection['credits']}/{$creditCap} TC và rải lại lộ trình.";
        if ($selection['skipped_count'] > 0) {
            $message .= " {$selection['skipped_count']} môn được chuyển sang kỳ sau để không vượt giới hạn.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'applied_subject_ids' => $selection['ids'],
            'applied_credits' => $selection['credits'],
            'credit_limit' => $creditCap,
            'data'    => $this->attachGrades($updated, $userId),
        ]);
    }

    /**
     * Chọn môn theo đúng trần tín chỉ. Quan hệ song hành được gom thành thành phần
     * liên thông để không bao giờ ghim môn lý thuyết mà đẩy môn thực hành sang kỳ khác.
     *
     * @return array{ids: int[], credits: int, skipped_count: int}
     */
    private function limitSuggestionsToCreditCap(array $requestedIds, int $creditCap, int $userId): array
    {
        $requestedIds = array_values(array_unique(array_map('intval', $requestedIds)));
        $relations = SubjectRelation::where('type', 'corequisite')
            ->get(['subject_id', 'related_subject_id']);

        $adjacency = [];
        foreach ($relations as $relation) {
            $left  = (int) $relation->subject_id;
            $right = (int) $relation->related_subject_id;
            $adjacency[$left][]  = $right;
            $adjacency[$right][] = $left;
        }

        $allIds = array_values(array_unique(array_merge(
            $requestedIds,
            array_keys($adjacency),
            ...array_values($adjacency)
        )));
        $creditMap = Subject::whereIn('id', $allIds)->pluck('credits', 'id');
        $passedIds = UserGrade::where('user_id', $userId)
            ->get()
            ->filter(fn ($grade) =>
                ($grade->grade !== null && $grade->grade >= 5.0)
                || in_array($grade->status, ['passed', 'pass'], true)
            )
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $selected = [];
        $selectedSet = [];
        $considered = [];
        $usedCredits = 0;

        foreach ($requestedIds as $requestedId) {
            if (isset($considered[$requestedId])) continue;

            $component = [];
            $queue = [$requestedId];
            while ($queue) {
                $id = (int) array_shift($queue);
                if (isset($component[$id])) continue;
                $component[$id] = true;
                foreach ($adjacency[$id] ?? [] as $neighbor) {
                    $queue[] = (int) $neighbor;
                }
            }

            foreach (array_keys($component) as $id) {
                $considered[$id] = true;
            }

            $bundleIds = array_values(array_filter(
                array_keys($component),
                fn ($id) => !$passedIds->has((int) $id) && !isset($selectedSet[(int) $id])
            ));
            $bundleCredits = array_sum(array_map(
                fn ($id) => (int) ($creditMap[$id] ?? 3),
                $bundleIds
            ));

            if ($bundleIds && $usedCredits + $bundleCredits <= $creditCap) {
                foreach ($bundleIds as $id) {
                    $selected[] = (int) $id;
                    $selectedSet[(int) $id] = true;
                }
                $usedCredits += $bundleCredits;
            }
        }

        return [
            'ids' => $selected,
            'credits' => $usedCredits,
            'skipped_count' => count(array_diff($requestedIds, $selected)),
        ];
    }

    // POST /api/v1/study-plans/add-retake
    public function addRetake(AddRetakeRequest $request)
    {
        $userId    = Auth::id();
        $fromSem   = (int) $request->input('from_semester');
        $subjectId = (int) $request->input('subject_id');

        $plan = StudyPlan::with('semesters.subjects')
            ->where('id', $request->input('study_plan_id'))
            ->where('user_id', $userId)->firstOrFail();

        $targetSem = $plan->semesters->where('semester_index', '>', $fromSem)->sortBy('semester_index')->first()
            ?? StudyPlanSemester::create([
                'study_plan_id'  => $plan->id,
                'semester_index' => ($plan->semesters->max('semester_index') ?? $fromSem) + 1,
            ]);

        $exists = StudyPlanSubject::where('study_plan_semester_id', $targetSem->id)
            ->where('subject_id', $subjectId)->where('is_retake', true)->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Môn này đã có trong kế hoạch học lại.'], 422);
        }

        StudyPlanSubject::create([
            'study_plan_semester_id' => $targetSem->id,
            'subject_id'             => $subjectId,
            'is_completed'           => false,
            'is_retake'              => true,
            'original_attempt_sem'   => $fromSem,
            'original_grade'         => $request->input('original_grade'),
        ]);

        $plan->load('semesters.subjects.subject');

        return response()->json([
            'success'         => true,
            'message'         => "Đã thêm học lại vào Học kỳ {$targetSem->semester_index}.",
            'data'            => $this->attachGrades($plan, $userId),
            'retake_semester' => $targetSem->semester_index,
        ]);
    }

    // POST /api/v1/study-plans/toggle-elective
    public function toggleElective(ToggleElectiveRequest $request)
    {
        $userId      = Auth::id();
        $subjectId   = (int) $request->input('subject_id');
        $semesterIdx = (int) $request->input('semester_index');

        $plan = StudyPlan::with('semesters.subjects.subject')
            ->where('id', $request->input('study_plan_id'))
            ->where('user_id', $userId)->firstOrFail();

        $sem = $plan->semesters->firstWhere('semester_index', $semesterIdx);
        if (!$sem) {
            return response()->json(['error' => 'Không tìm thấy học kỳ.'], 404);
        }

        $subject = Subject::with('corequisites')->findOrFail($subjectId);

        // Nhóm tự chọn chứa môn này + danh sách id các môn trong nhóm
        $eg              = null;
        $groupSubjectIds = [];
        $frameworkId     = $this->resolveFrameworkId($userId);
        if ($frameworkId) {
            $eg = ElectiveGroup::where('curriculum_framework_id', $frameworkId)
                ->whereHas('subjects', fn($q) => $q->where('subjects.id', $subjectId))
                ->first();
            if ($eg) {
                $groupSubjectIds = $eg->subjects()->pluck('subjects.id')->toArray();
            }
        }

        // Môn song hành (corequisite) CÙNG NHÓM — luôn đi kèm với môn chính.
        // VD: "Khoa học dữ liệu" (2 TC) bắt buộc kèm "Khoa học dữ liệu - TH" (1 TC).
        $coreqInGroup = $subject->corequisites
            ->whereIn('id', $groupSubjectIds)
            ->pluck('id')->toArray();

        if ($request->input('action') === 'remove') {
            // Bỏ chọn môn chính → bỏ luôn môn song hành cùng nhóm (tránh để mồ côi)
            StudyPlanSubject::where('study_plan_semester_id', $sem->id)
                ->whereIn('subject_id', array_merge([$subjectId], $coreqInGroup))
                ->delete();
        } else {
            // Học kỳ ĐÍCH cho môn MỚI: nếu khung nhóm đang neo ở kỳ ĐÃ QUA (trước kỳ hiện
            // tại) thì môn mới (chưa học) phải xếp vào kỳ TƯƠNG LAI phù hợp — không thể nhét
            // vào kỳ đã có điểm. Khung ở kỳ hiện tại/tương lai thì giữ nguyên.
            $currentSem = $this->detectCurrentSemester($plan, $userId);
            $targetSem  = $sem->semester_index < $currentSem
                ? $this->planService->findOrCreateFutureSemester($plan, $currentSem, $subject->offered_in)
                : $sem;

            // Chọn môn chính → tự động thêm cả môn song hành cùng nhóm
            $existingIds = StudyPlanSubject::where('study_plan_semester_id', $targetSem->id)
                ->pluck('subject_id')->toArray();

            $addIds = array_values(array_diff(
                array_merge([$subjectId], $coreqInGroup),
                $existingIds
            ));

            if (empty($addIds)) {
                return response()->json(['error' => 'Môn này đã có trong học kỳ.'], 422);
            }

            // Giới hạn TC nhóm — đếm XUYÊN học kỳ: chỉ tính TC ĐẬU + ĐANG HỌC (lựa chọn mới);
            // bỏ qua môn RỚT và môn HỌC LẠI (rớt không chiếm slot → cho đổi sang môn khác).
            if ($eg) {
                $creditMap = Subject::whereIn('id', $groupSubjectIds)->pluck('credits', 'id');
                $rows = StudyPlanSubject::whereIn('study_plan_semester_id', $plan->semesters->pluck('id'))
                    ->whereIn('subject_id', $groupSubjectIds)
                    ->get(['subject_id', 'subject_grade', 'is_retake']);
                $effIds = [];
                foreach ($rows as $r) {
                    $g = $r->subject_grade;
                    if ($g !== null && $g >= 5.0)          $effIds[$r->subject_id] = true; // đậu
                    elseif ($g === null && !$r->is_retake) $effIds[$r->subject_id] = true; // đang học (mới)
                }
                $currentEffCr = array_sum(array_map(fn($id) => (int) ($creditMap[$id] ?? 0), array_keys($effIds)));
                $addCr        = Subject::whereIn('id', $addIds)->sum('credits');

                if ($currentEffCr + $addCr > $eg->required_credits) {
                    return response()->json([
                        'error' => "Nhóm tự chọn này chỉ cần {$eg->required_credits} TC. Bỏ chọn một môn trước khi thêm."
                    ], 422);
                }
            }

            foreach ($addIds as $sid) {
                StudyPlanSubject::create([
                    'study_plan_semester_id' => $targetSem->id,
                    'subject_id'             => $sid,
                    'is_completed'           => false,
                    'is_retake'              => false,
                ]);
            }

            // Chọn phương án MỚI để bù cho nhóm → tự gỡ các "học lại" CHƯA chấm của những
            // môn ĐÃ RỚT cùng nhóm (đổi môn: môn mới thay cho việc học lại môn rớt, tránh dư).
            if ($eg) {
                $allSemIds = $plan->semesters->pluck('id');
                $failedOptionIds = StudyPlanSubject::whereIn('study_plan_semester_id', $allSemIds)
                    ->whereIn('subject_id', $groupSubjectIds)
                    ->whereNotNull('subject_grade')->where('subject_grade', '<', 5.0)
                    ->pluck('subject_id')->toArray();
                if (!empty($failedOptionIds)) {
                    StudyPlanSubject::whereIn('study_plan_semester_id', $allSemIds)
                        ->whereIn('subject_id', $failedOptionIds)
                        ->where('is_retake', true)->whereNull('subject_grade')
                        ->delete();
                }
            }
        }

        $plan->load('semesters.subjects.subject');

        return response()->json([
            'success' => true,
            'data'    => $this->attachGrades($plan, $userId),
        ]);
    }

    // POST /api/v1/study-plans/{id}/dedup-retakes
    public function dedupRetakes($id)
    {
        $userId = Auth::id();
        $plan   = StudyPlan::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $this->planService->deduplicateRetakes($plan);
        $plan->load('semesters.subjects');

        return response()->json([
            'success' => true,
            'message' => 'Đã dọn sạch môn học trùng lặp.',
            'data'    => $this->attachGrades($plan, $userId),
        ]);
    }
}
