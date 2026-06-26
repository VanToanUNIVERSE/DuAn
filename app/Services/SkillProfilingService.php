<?php

namespace App\Services;

use App\Models\SkillGroup;
use App\Models\Subject;
use App\Models\UserGrade;

class SkillProfilingService
{
    /**
     * Phân tích mức độ hoàn thành và năng lực theo định hướng kỹ năng.
     */
    public function analyzeSkillProgress(int $userId, string $skillFocus): array
    {
        $focusGroups = SkillGroup::where('focus_area', $skillFocus)->pluck('id')->toArray();

        if (empty($focusGroups)) {
            return ['focus_area' => $skillFocus, 'total' => 0, 'passed' => 0, 'completion_pct' => 0, 'avg_grade' => 0];
        }

        $focusSubjectIds = Subject::whereIn('skill_group_id', $focusGroups)->pluck('id')->toArray();
        $total           = count($focusSubjectIds);

        if ($total === 0) {
            return ['focus_area' => $skillFocus, 'total' => 0, 'passed' => 0, 'completion_pct' => 0, 'avg_grade' => 0];
        }

        $grades   = UserGrade::where('user_id', $userId)->whereIn('subject_id', $focusSubjectIds)->get();
        $passed   = $grades->filter(fn($g) => in_array($g->status, ['pass', 'passed']) || ($g->grade !== null && $g->grade >= 5.0))->count();
        $avgGrade = $grades->whereNotNull('grade')->avg('grade') ?? 0;

        return [
            'focus_area'     => $skillFocus,
            'focus_label'    => SkillGroup::FOCUS_AREAS[$skillFocus] ?? $skillFocus,
            'total_subjects' => $total,
            'passed'         => $passed,
            'completion_pct' => round($passed / $total * 100),
            'avg_grade'      => round((float) $avgGrade, 2),
        ];
    }

    /**
     * Xây dựng thông điệp tư vấn dựa trên năng lực định hướng.
     */
    public function buildSkillMessage(array $skill): string
    {
        $label  = $skill['focus_label'] ?? $skill['focus_area'];
        $pct    = $skill['completion_pct'];
        $avg    = $skill['avg_grade'];
        $total  = $skill['total_subjects'];
        $passed = $skill['passed'];

        if ($total === 0) {
            return "Chưa có dữ liệu môn học cho định hướng {$label}.";
        }

        $strengthNote = '';
        if ($avg >= 7.5) {
            $strengthNote = " Năng lực {$label} của bạn rất tốt (TB {$avg}/10) — đây là thế mạnh cần phát huy.";
        } elseif ($avg >= 5.0 && $avg > 0) {
            $strengthNote = " Điểm TB {$label}: {$avg}/10 — cần cố gắng thêm để đạt thành thạo.";
        } elseif ($avg > 0) {
            $strengthNote = " Điểm TB {$label}: {$avg}/10 — đây là điểm yếu cần chú ý cải thiện.";
        }

        return "Định hướng {$label}: đã hoàn thành {$passed}/{$total} môn ({$pct}%).{$strengthNote}";
    }
}
