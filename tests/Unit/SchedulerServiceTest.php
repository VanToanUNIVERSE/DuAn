<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Plan\SchedulerService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class SchedulerServiceTest extends TestCase
{
    public function test_failed_subjects_are_rescheduled_even_if_they_appear_in_already_planned(): void
    {
        $subjects = collect([
            $this->subject(101, 2),
            $this->subject(102, 2),
        ]);

        $schedule = (new SchedulerService)->schedule(
            $subjects,
            alreadyPlanned: [101, 102],
            failedIds: [101, 102],
            startSem: 3,
            tcPerSem: 19,
            targetSemesters: 3,
            user: new User(['id' => 1]),
            groupIds: ['basic' => [], 'major' => [], 'specialized' => []],
        );

        $this->assertSame([3], array_keys($schedule));
        $this->assertEqualsCanonicalizing([101, 102], $schedule[3]);
    }

    public function test_late_unlocked_subject_never_pushes_a_semester_over_22_credits(): void
    {
        $subjects = collect([
            $this->subject(201, 19),
            $this->subject(202, 5, 'completed_all'),
        ]);

        $schedule = (new SchedulerService)->schedule(
            $subjects,
            alreadyPlanned: [],
            failedIds: [],
            startSem: 1,
            tcPerSem: 22,
            targetSemesters: 1,
            user: new User(['id' => 1]),
            groupIds: ['basic' => [], 'major' => [], 'specialized' => []],
        );

        $creditMap = [201 => 19, 202 => 5];
        $semesterCredits = collect($schedule)->map(
            fn (array $ids) => array_sum(array_map(fn (int $id) => $creditMap[$id], $ids))
        );

        $this->assertLessThanOrEqual(22, $semesterCredits->max());
        $this->assertSame([1, 2], array_keys($schedule));
    }

    public function test_schedule_respects_configured_credit_limit_when_target_is_too_short(): void
    {
        $subjects = collect([
            $this->subject(301, 3),
            $this->subject(302, 3),
            $this->subject(303, 3),
            $this->subject(304, 3),
            $this->subject(305, 3),
            $this->subject(306, 3),
        ]);

        $schedule = (new SchedulerService)->schedule(
            $subjects,
            alreadyPlanned: [],
            failedIds: [],
            startSem: 1,
            tcPerSem: 15,
            targetSemesters: 1,
            user: new User(['id' => 1]),
            groupIds: ['basic' => [], 'major' => [], 'specialized' => []],
        );

        $creditMap = array_fill_keys([301, 302, 303, 304, 305, 306], 3);
        $semesterCredits = collect($schedule)->map(
            fn (array $ids) => array_sum(array_map(fn (int $id) => $creditMap[$id], $ids))
        );

        $this->assertLessThanOrEqual(15, $semesterCredits->max());
        $this->assertGreaterThanOrEqual(2, count($schedule));
    }

    public function test_rescheduled_failed_prerequisite_unlocks_dependents_in_later_semester(): void
    {
        $prerequisite = $this->subject(400, 2);
        $dependent = $this->subject(401, 3);
        $dependent->prerequisites = collect([$prerequisite]);

        $schedule = (new SchedulerService)->schedule(
            collect([$dependent]),
            alreadyPlanned: [400],
            // Môn 400 đã được ghim ở kỳ trước nên không còn nằm trong failedIds
            // truyền cho phần lịch còn lại.
            failedIds: [],
            startSem: 4,
            tcPerSem: 16,
            targetSemesters: 8,
            user: new User(['id' => 1]),
            groupIds: ['basic' => [], 'major' => [], 'specialized' => []],
        );

        $this->assertSame([401], $schedule[4]);
    }

    public function test_selected_elective_group_is_moved_as_one_block(): void
    {
        $highPriority = $this->subject(500, 3);
        $highPriority->program_group_id = 99;

        $electiveA = $this->subject(501, 3);
        $electiveA->elective_group_id = 10;
        $electiveB = $this->subject(502, 3);
        $electiveB->elective_group_id = 10;

        $schedule = (new SchedulerService)->schedule(
            collect([$highPriority, $electiveA, $electiveB]),
            alreadyPlanned: [],
            failedIds: [],
            startSem: 1,
            tcPerSem: 6,
            targetSemesters: 2,
            user: new User(['id' => 1]),
            groupIds: ['basic' => [99], 'major' => [], 'specialized' => []],
        );

        $semesterOf = [];
        foreach ($schedule as $semester => $subjectIds) {
            foreach ($subjectIds as $subjectId) $semesterOf[$subjectId] = $semester;
        }

        $this->assertSame($semesterOf[501], $semesterOf[502]);
        $this->assertNotSame($semesterOf[500], $semesterOf[501]);
    }

    private function subject(int $id, int $credits, string $requirementType = 'none'): object
    {
        return (object) [
            'id' => $id,
            'name' => "Môn {$id}",
            'credits' => $credits,
            'offered_in' => 'both',
            'requirement_type' => $requirementType,
            'program_group_id' => null,
            'assigned_semester_index' => 3,
            'prerequisites' => new Collection,
            'corequisites' => new Collection,
            'relatedRelations' => new Collection,
            'skillGroup' => null,
        ];
    }
}
