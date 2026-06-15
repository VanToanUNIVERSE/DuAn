<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurriculumFramework;
use App\Models\CurriculumSubject;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\SubjectRelation;
use App\Models\TrainingProgram;
use Illuminate\Http\Request;

class CurriculumSubjectController extends Controller
{
    /**
     * Danh sách chương trình đào tạo để chọn phân công môn học
     */
    public function index()
    {
        $trainingPrograms = TrainingProgram::with([
            'curriculumFrameworks.semesters'
        ])->orderBy('academic_year', 'desc')->get();

        return view('admin.curriculum.index', compact('trainingPrograms'));
    }

    /**
     * Trang phân công môn học cho một chương trình đào tạo
     * Hiển thị từng học kỳ với danh sách môn đã được gán và ô tìm kiếm thêm môn
     */
    public function show(CurriculumFramework $curriculumFramework)
    {
        $curriculumFramework->load(['trainingProgram', 'semesters']);

        // Với mỗi học kỳ, load môn học đã được phân công và sắp xếp bằng Natural Sort
        $semestersWithSubjects = $curriculumFramework->semesters->sortBy('name', SORT_NATURAL)->values()->map(function ($semester) use ($curriculumFramework) {
            $semester->assignedSubjects = CurriculumSubject::where('curriculum_framework_id', $curriculumFramework->id)
                ->where('semester_id', $semester->id)
                ->with('subject.skillGroup', 'subject.programGroup')
                ->get();
            return $semester;
        });

        // Tất cả môn học — map thành mảng phẳng để truyền JSON an toàn vào Blade
        $allSubjects = Subject::with(['skillGroup', 'programGroup'])
            ->orderBy('name')
            ->get(['id', 'subject_code', 'name', 'credits', 'skill_group_id', 'program_group_id']);

        $subjectsJson = $allSubjects->map(function ($s) {
            return [
                'id'      => $s->id,
                'name'    => $s->name,
                'code'    => $s->subject_code ?? '',
                'credits' => $s->credits ?? '',
                'sg'      => $s->skillGroup->name ?? '',
                'pg'      => $s->programGroup->name ?? '',
                'pg_id'   => $s->program_group_id ?? null,
            ];
        })->values()->toJson();

        $programGroups = \App\Models\ProgramGroup::orderBy('name')->get(['id', 'name']);

        return view('admin.curriculum.show', compact(
            'curriculumFramework',
            'semestersWithSubjects',
            'subjectsJson',
            'programGroups'
        ));
    }

    /**
     * Thêm nhiều môn học vào học kỳ của chương trình
     */
    public function assign(Request $request, CurriculumFramework $curriculumFramework)
    {
        $validated = $request->validate([
            'semester_id'   => 'required|exists:semesters,id',
            'subject_ids'   => 'required|array|min:1',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        // Kiểm tra semester thuộc đúng curriculum framework
        $semester = Semester::where('id', $validated['semester_id'])
            ->where('curriculum_framework_id', $curriculumFramework->id)
            ->first();

        if (!$semester) {
            return back()->with('error', 'Học kỳ không thuộc chương trình này.');
        }

        $added = 0;
        foreach ($validated['subject_ids'] as $subjectId) {
            $exists = CurriculumSubject::where([
                'curriculum_framework_id' => $curriculumFramework->id,
                'semester_id'             => $validated['semester_id'],
                'subject_id'              => $subjectId,
            ])->exists();

            if (!$exists) {
                CurriculumSubject::create([
                    'curriculum_framework_id' => $curriculumFramework->id,
                    'semester_id'             => $validated['semester_id'],
                    'subject_id'              => $subjectId,
                ]);
                $added++;
            }
        }

        $skipped = count($validated['subject_ids']) - $added;
        $msg = "✅ Đã thêm {$added} môn vào Học kỳ {$semester->name}.";
        if ($skipped > 0) $msg .= " ({$skipped} môn đã tồn tại, bỏ qua)";

        return back()->with('success', $msg);
    }

    /**
     * Xóa môn học khỏi học kỳ của chương trình
     */
    public function remove(CurriculumFramework $curriculumFramework, CurriculumSubject $assignment)
    {
        if ($assignment->curriculum_framework_id !== $curriculumFramework->id) {
            return back()->with('error', 'Không tìm thấy phân công này.');
        }

        $subjectName = $assignment->subject->name ?? 'Môn học';
        $assignment->delete();

        return back()->with('success', "Đã xóa \"{$subjectName}\" khỏi học kỳ.");
    }

    /**
     * Xóa tất cả môn học khỏi một học kỳ
     */
    public function clearSemester(CurriculumFramework $curriculumFramework, Semester $semester)
    {
        if ($semester->curriculum_framework_id !== $curriculumFramework->id) {
            return back()->with('error', 'Học kỳ không thuộc chương trình này.');
        }

        CurriculumSubject::where('curriculum_framework_id', $curriculumFramework->id)
            ->where('semester_id', $semester->id)
            ->delete();

        return back()->with('success', "Đã xóa tất cả môn học khỏi {$semester->name}.");
    }

    /**
     * Xóa tất cả môn học khỏi TẤT CẢ học kỳ của chương trình
     */
    public function clearAll(CurriculumFramework $curriculumFramework)
    {
        CurriculumSubject::where('curriculum_framework_id', $curriculumFramework->id)->delete();

        return back()->with('success', "Đã xóa toàn bộ môn học khỏi tất cả học kỳ của chương trình.");
    }

    /**
     * Phân công tự động nhiều môn học vào các học kỳ theo Topological Sort
     */
    public function autoAssign(Request $request, CurriculumFramework $curriculumFramework)
    {
        $validated = $request->validate([
            'subject_ids'   => 'required|array|min:1',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        $subjectIds = $validated['subject_ids'];

        // Lấy tất cả môn học được chọn và quan hệ
        $subjects = Subject::with('programGroup')->whereIn('id', $subjectIds)->get()->keyBy('id');
        $relations = SubjectRelation::whereIn('subject_id', $subjectIds)
                                    ->whereIn('related_subject_id', $subjectIds)
                                    ->get();

        // 0. Phân loại môn học theo Program Group để xử lý requirement_type
        $basicSubjects = [];
        $majorSubjects = [];
        $specializedSubjects = [];

        foreach ($subjects as $sub) {
            $pgName = mb_strtolower($sub->programGroup->name ?? '');
            if (mb_strpos($pgName, 'đại cương') !== false) {
                $basicSubjects[] = $sub->id;
            } elseif (mb_strpos($pgName, 'cơ sở') !== false) {
                $majorSubjects[] = $sub->id;
            } elseif (mb_strpos($pgName, 'chuyên ngành') !== false) {
                $specializedSubjects[] = $sub->id;
            }
        }

        // 1. Gom nhóm corequisite (song hành) bằng Union-Find
        $parent = [];
        foreach ($subjectIds as $id) {
            $parent[$id] = $id;
        }

        $find = function($i) use (&$parent, &$find) {
            if ($parent[$i] == $i) return $i;
            return $parent[$i] = $find($parent[$i]);
        };

        $union = function($i, $j) use (&$parent, &$find) {
            $rootI = $find($i);
            $rootJ = $find($j);
            if ($rootI != $rootJ) {
                $parent[$rootI] = $rootJ;
            }
        };

        foreach ($relations as $rel) {
            if ($rel->type === 'corequisite') {
                $union($rel->subject_id, $rel->related_subject_id);
            }
        }

        // Tạo macro-nodes
        $macroNodes = [];
        $subjectToMacro = [];
        foreach ($subjectIds as $id) {
            $root = $find($id);
            if (!isset($macroNodes[$root])) {
                $macroNodes[$root] = [
                    'id' => $root,
                    'subjects' => [],
                    'credits' => 0,
                    'in_degree' => 0,
                    'dependents' => [],
                    'prerequisites' => [], // Cần lưu lại để tính min_semester
                ];
            }
            $macroNodes[$root]['subjects'][] = $id;
            $subjectToMacro[$id] = $root;
            $macroNodes[$root]['credits'] += ($subjects[$id]->credits ?? 3);
        }

        // 2. Xây dựng đồ thị DAG với prerequisite
        foreach ($relations as $rel) {
            if ($rel->type === 'prerequisite') {
                $u = $subjectToMacro[$rel->related_subject_id]; // Tiên quyết
                $v = $subjectToMacro[$rel->subject_id];         // Phụ thuộc
                if ($u != $v && !in_array($v, $macroNodes[$u]['dependents'])) {
                    $macroNodes[$u]['dependents'][] = $v;
                    $macroNodes[$v]['prerequisites'][] = $u;
                    $macroNodes[$v]['in_degree']++;
                }
            }
        }

        // 2.5 Bổ sung Implicit Prerequisites từ requirement_type (sử dụng hệ thống Rank để chống vòng lặp)
        $reqRank = [
            'none' => 0,
            'completed_basic' => 1,
            'completed_major' => 2,
            'completed_specialized' => 3,
            'completed_all' => 4,
            'min_credits' => 0, // min_credits không dùng chung logic thứ tự tuyến tính này
        ];

        foreach ($subjects as $sub) {
            $v = $subjectToMacro[$sub->id];
            $reqType = $sub->requirement_type ?? 'none';
            $rankV = $reqRank[$reqType] ?? 0;
            if ($rankV == 0) continue; // Không có yêu cầu tuyến tính

            $implicitPrereqs = [];

            if ($reqType === 'completed_basic') {
                $implicitPrereqs = $basicSubjects;
            } elseif ($reqType === 'completed_major') {
                $implicitPrereqs = $majorSubjects; // Chỉ phụ thuộc nhóm cơ sở ngành, không chờ các môn đại cương râu ria (như Thể chất, Chính trị)
            } elseif ($reqType === 'completed_specialized') {
                $implicitPrereqs = $specializedSubjects; // Tương tự, chỉ phụ thuộc nhóm chuyên ngành
            } elseif ($reqType === 'completed_all') {
                $implicitPrereqs = array_diff($subjectIds, $macroNodes[$v]['subjects']);
            }

            foreach ($implicitPrereqs as $prereqId) {
                $uSub = $subjects[$prereqId];
                $uReqType = $uSub->requirement_type ?? 'none';
                $rankU = $reqRank[$uReqType] ?? 0;

                // Chỉ phụ thuộc ngầm vào các môn có Rank NHỎ HƠN để tuyệt đối chống vòng lặp
                if ($rankU < $rankV) {
                    $u = $subjectToMacro[$prereqId];
                    if ($u != $v && !in_array($v, $macroNodes[$u]['dependents'])) {
                        $macroNodes[$u]['dependents'][] = $v;
                        $macroNodes[$v]['prerequisites'][] = $u;
                        $macroNodes[$v]['in_degree']++;
                    }
                }
            }
        }

        // 3. Khử thứ tự tô-pô (Topological Sort - Kahn's Algorithm) với Priority Queue
        $queue = [];
        foreach ($macroNodes as $id => $node) {
            if ($node['in_degree'] == 0) {
                $queue[] = $id;
            }
        }

        $topoOrder = [];
        while (!empty($queue)) {
            // Sort queue để ưu tiên xử lý các môn Đại cương (Rank nhỏ) trước!
            // Tính rank trung bình của macro node (nếu macro node chứa nhiều môn)
            usort($queue, function($a, $b) use ($macroNodes, $subjects, $reqRank) {
                $rankA = 0;
                foreach ($macroNodes[$a]['subjects'] as $subId) {
                    $rankA += $reqRank[$subjects[$subId]->requirement_type ?? 'none'] ?? 0;
                }
                $rankB = 0;
                foreach ($macroNodes[$b]['subjects'] as $subId) {
                    $rankB += $reqRank[$subjects[$subId]->requirement_type ?? 'none'] ?? 0;
                }
                return $rankA <=> $rankB;
            });

            $u = array_shift($queue);
            $topoOrder[] = $u;
            foreach ($macroNodes[$u]['dependents'] as $v) {
                $macroNodes[$v]['in_degree']--;
                if ($macroNodes[$v]['in_degree'] == 0) {
                    $queue[] = $v;
                }
            }
        }

        // Kiểm tra chu trình
        if (count($topoOrder) < count($macroNodes)) {
            return back()->with('error', 'Phát hiện vòng lặp tiên quyết giữa các môn học được chọn!');
        }

        // 4. Rải vào các học kỳ
        $curriculumFramework->load('semesters');
        $semesters = $curriculumFramework->semesters->sortBy('name', SORT_NATURAL)->values();
        $numSemesters = $semesters->count();
        if ($numSemesters == 0) {
            return back()->with('error', 'Chương trình chưa có học kỳ nào.');
        }

        $totalCredits = array_sum(array_column($macroNodes, 'credits'));
        $targetCreditsPerSemester = ceil($totalCredits / $numSemesters);
        $buffer = 2; // Cho phép lố 2 tín chỉ để linh hoạt

        $semesterCredits = array_fill(0, $numSemesters, 0);
        $assignedSemester = []; // id => semester_index
        $assignments = [];

        foreach ($topoOrder as $u) {
            $node = $macroNodes[$u];
            
            // Tìm học kỳ nhỏ nhất có thể xếp môn này (phải sau tất cả các môn tiên quyết)
            $minSem = 0;
            foreach ($node['prerequisites'] as $p) {
                if (isset($assignedSemester[$p])) {
                    $minSem = max($minSem, $assignedSemester[$p] + 1);
                }
            }

            // Nếu vượt quá số học kỳ, buộc phải gán vào học kỳ cuối
            if ($minSem >= $numSemesters) {
                $minSem = $numSemesters - 1;
            }

            // Xếp vào học kỳ minSem, nếu đầy (vượt Target) thì đẩy xuống kỳ sau
            $sem = $minSem;
            // Cho phép vượt target 1 tín chỉ để khít khối lượng
            $allowedMax = $targetCreditsPerSemester + 1;
            
            while ($sem < $numSemesters - 1 && $semesterCredits[$sem] + $node['credits'] > $allowedMax) {
                $sem++;
            }

            $assignedSemester[$u] = $sem;
            $semesterCredits[$sem] += $node['credits'];

            foreach ($node['subjects'] as $subjectId) {
                $assignments[] = [
                    'curriculum_framework_id' => $curriculumFramework->id,
                    'semester_id'             => $semesters[$sem]->id,
                    'subject_id'              => $subjectId,
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ];
            }
        }

        // Xoá phân công cũ của CÁC MÔN ĐƯỢC CHỌN trong CTĐT này
        CurriculumSubject::where('curriculum_framework_id', $curriculumFramework->id)
                         ->whereIn('subject_id', $subjectIds)
                         ->delete();

        // Ghi vào DB
        CurriculumSubject::insert($assignments);

        return back()->with('success', '✨ Đã phân bổ tự động ' . count($subjectIds) . ' môn học (kèm ràng buộc song hành).');
    }
}
