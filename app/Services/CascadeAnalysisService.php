<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\SubjectRelation;
use App\Models\UserGrade;
use Illuminate\Support\Collection;

/**
 * Phân tích hiệu ứng dây chuyền khi sinh viên rớt một môn học.
 *
 * Khi môn X bị fail:
 *   - Các môn cần X làm tiên quyết → bị khoá (không học được ngay)
 *   - Các môn cần những môn đó → bị khoá tiếp (indirect)
 *   - Ước tính số học kỳ bị trễ
 */
class CascadeAnalysisService
{
    /**
     * Phân tích ảnh hưởng khi sinh viên rớt môn $subjectId.
     *
     * @return array{
     *   failed_subject: array,
     *   direct_blocked: array,
     *   indirect_blocked: array,
     *   total_blocked: int,
     *   estimated_delay_sems: int,
     *   total_blocked_credits: int,
     *   summary: string
     * }
     */
    public function analyze(int $userId, int $subjectId): array
    {
        $failedSubject = Subject::with(['prerequisites', 'relatedRelations'])->find($subjectId);
        if (!$failedSubject) {
            return $this->emptyResult();
        }

        // Danh sách môn đã pass của sinh viên (để loại trừ những môn không bị ảnh hưởng)
        $passedIds = UserGrade::where('user_id', $userId)
            ->where('subject_id', '!=', $subjectId)
            ->get()
            ->filter(fn($g) => in_array($g->status, ['pass', 'passed']) || ($g->grade !== null && $g->grade >= 5.0))
            ->pluck('subject_id')
            ->toArray();

        // Xây dựng map: subject_id → danh sách môn phụ thuộc vào nó
        $dependencyMap = $this->buildDependencyMap();

        // BFS để tìm tất cả môn bị ảnh hưởng (chưa pass)
        $visited  = [];
        $direct   = [];   // Cấp 1: cần trực tiếp môn này
        $indirect = [];   // Cấp 2+: gián tiếp

        $queue = $dependencyMap[$subjectId] ?? [];
        foreach ($queue as $depId) {
            if (!in_array($depId, $passedIds) && !in_array($depId, $visited)) {
                $direct[]   = $depId;
                $visited[]  = $depId;
            }
        }

        // Tiếp tục tìm các cấp sau
        $nextLevel = $direct;
        while (!empty($nextLevel)) {
            $newLevel = [];
            foreach ($nextLevel as $depId) {
                $children = $dependencyMap[$depId] ?? [];
                foreach ($children as $childId) {
                    if (!in_array($childId, $passedIds) && !in_array($childId, $visited)) {
                        $indirect[] = $childId;
                        $visited[]  = $childId;
                        $newLevel[] = $childId;
                    }
                }
            }
            $nextLevel = $newLevel;
        }

        // Load chi tiết các môn bị ảnh hưởng
        $directSubjects   = $this->loadSubjectDetails($direct);
        $indirectSubjects = $this->loadSubjectDetails($indirect);

        $totalBlocked  = count($direct) + count($indirect);
        $totalCredits  = collect($directSubjects)->sum('credits') + collect($indirectSubjects)->sum('credits');

        // Ước tính trễ: mỗi "tầng" tiên quyết thêm ít nhất 1 học kỳ
        $maxDepth = $this->calcMaxDepth($subjectId, $dependencyMap, 0, []);
        $estimatedDelay = min($maxDepth, 4); // Tối đa 4 kỳ để hiển thị

        $summary = $this->buildSummary($failedSubject, $totalBlocked, $totalCredits, $estimatedDelay);

        return [
            'failed_subject'       => [
                'id'      => $failedSubject->id,
                'name'    => $failedSubject->name,
                'credits' => $failedSubject->credits,
                'code'    => $failedSubject->subject_code,
            ],
            'direct_blocked'       => $directSubjects,
            'indirect_blocked'     => $indirectSubjects,
            'total_blocked'        => $totalBlocked,
            'estimated_delay_sems' => $estimatedDelay,
            'total_blocked_credits'=> (int) $totalCredits,
            'summary'              => $summary,
        ];
    }

    /**
     * Phân tích hàng loạt: nhiều môn bị fail cùng lúc.
     * Trả về tổng hợp ảnh hưởng (không trùng lặp môn bị block).
     */
    public function analyzeMultiple(int $userId, array $failedSubjectIds): array
    {
        if (empty($failedSubjectIds)) {
            return $this->emptyResult();
        }

        $allDirect   = [];
        $allIndirect = [];
        $dependencyMap = $this->buildDependencyMap();

        $passedIds = UserGrade::where('user_id', $userId)
            ->whereNotIn('subject_id', $failedSubjectIds)
            ->get()
            ->filter(fn($g) => in_array($g->status, ['pass', 'passed']) || ($g->grade !== null && $g->grade >= 5.0))
            ->pluck('subject_id')
            ->toArray();

        foreach ($failedSubjectIds as $subjectId) {
            $visited = array_merge($failedSubjectIds, $allDirect, $allIndirect);
            $direct  = [];

            foreach ($dependencyMap[$subjectId] ?? [] as $depId) {
                if (!in_array($depId, $passedIds) && !in_array($depId, $visited)) {
                    $direct[]  = $depId;
                    $visited[] = $depId;
                }
            }
            $allDirect = array_unique(array_merge($allDirect, $direct));

            $nextLevel = $direct;
            while (!empty($nextLevel)) {
                $newLevel = [];
                foreach ($nextLevel as $depId) {
                    foreach ($dependencyMap[$depId] ?? [] as $childId) {
                        if (!in_array($childId, $passedIds) && !in_array($childId, array_merge($visited, $newLevel))) {
                            $allIndirect[] = $childId;
                            $visited[]     = $childId;
                            $newLevel[]    = $childId;
                        }
                    }
                }
                $nextLevel = $newLevel;
            }
        }

        $allDirect   = array_unique($allDirect);
        $allIndirect = array_unique(array_diff($allIndirect, $allDirect));

        $directSubjects   = $this->loadSubjectDetails($allDirect);
        $indirectSubjects = $this->loadSubjectDetails($allIndirect);
        $totalBlocked     = count($allDirect) + count($allIndirect);
        $totalCredits     = collect($directSubjects)->sum('credits') + collect($indirectSubjects)->sum('credits');

        $failedNames = Subject::whereIn('id', $failedSubjectIds)->pluck('name')->toArray();
        $summary = count($failedSubjectIds) > 1
            ? "Rớt " . count($failedSubjectIds) . " môn ảnh hưởng đến {$totalBlocked} môn học khác ({$totalCredits} TC)."
            : "Rớt \"{$failedNames[0]}\" ảnh hưởng đến {$totalBlocked} môn học khác ({$totalCredits} TC).";

        return [
            'failed_subjects'      => $failedNames,
            'direct_blocked'       => $directSubjects,
            'indirect_blocked'     => $indirectSubjects,
            'total_blocked'        => $totalBlocked,
            'estimated_delay_sems' => min(4, $totalBlocked > 0 ? (int) ceil($totalBlocked / 3) : 0),
            'total_blocked_credits'=> (int) $totalCredits,
            'summary'              => $summary,
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Xây dựng map: subject_id → [list of subject_ids that depend on it]
     * Tức là: nếu A là tiên quyết của B, thì map[A] = [..., B]
     */
    private function buildDependencyMap(): array
    {
        $relations = SubjectRelation::where('type', 'prerequisite')->get();
        $map = [];
        foreach ($relations as $rel) {
            // related_subject_id là tiên quyết, subject_id là môn phụ thuộc
            $prereqId  = $rel->related_subject_id;
            $dependsId = $rel->subject_id;
            if (!isset($map[$prereqId])) {
                $map[$prereqId] = [];
            }
            $map[$prereqId][] = $dependsId;
        }
        return $map;
    }

    private function loadSubjectDetails(array $ids): array
    {
        if (empty($ids)) return [];
        return Subject::whereIn('id', $ids)
            ->with('skillGroup', 'programGroup')
            ->get()
            ->map(fn($s) => [
                'id'               => $s->id,
                'name'             => $s->name,
                'credits'          => $s->credits,
                'code'             => $s->subject_code,
                'skill_group'      => $s->skillGroup?->name,
                'program_group'    => $s->programGroup?->name,
            ])
            ->values()
            ->toArray();
    }

    private function calcMaxDepth(int $subjectId, array $map, int $depth, array $visited): int
    {
        if ($depth > 6 || in_array($subjectId, $visited)) return $depth;
        $visited[] = $subjectId;
        $children  = $map[$subjectId] ?? [];
        if (empty($children)) return $depth;
        $maxChild = $depth;
        foreach ($children as $child) {
            $d = $this->calcMaxDepth($child, $map, $depth + 1, $visited);
            if ($d > $maxChild) $maxChild = $d;
        }
        return $maxChild;
    }

    private function buildSummary(Subject $failed, int $total, int $credits, int $delaySems): string
    {
        if ($total === 0) {
            return "Môn \"{$failed->name}\" không là tiên quyết của môn nào khác. Không có ảnh hưởng dây chuyền.";
        }
        $delayNote = $delaySems > 0 ? " Có thể trễ tốt nghiệp ~{$delaySems} học kỳ." : '';
        return "Rớt \"{$failed->name}\" khoá {$total} môn học ({$credits} TC) vì chưa đáp ứng tiên quyết.{$delayNote}";
    }

    private function emptyResult(): array
    {
        return [
            'failed_subject'       => null,
            'direct_blocked'       => [],
            'indirect_blocked'     => [],
            'total_blocked'        => 0,
            'estimated_delay_sems' => 0,
            'total_blocked_credits'=> 0,
            'summary'              => 'Không có dữ liệu.',
        ];
    }
}
