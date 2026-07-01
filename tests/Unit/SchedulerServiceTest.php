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
