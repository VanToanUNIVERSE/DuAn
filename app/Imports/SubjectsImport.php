<?php

namespace App\Imports;

use App\Models\Subject;
use App\Models\SkillGroup;
use App\Models\ProgramGroup;
use App\Models\Semester;
use App\Models\SubjectRelation;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SubjectsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private int $rowCount = 0;
    private array $errors = [];

    // Relations cần xử lý sau khi tất cả subjects đã được tạo
    private array $pendingRelations = [];

    public function collection(Collection $rows)
    {
        // Pass 1: Upsert tất cả subjects trước
        foreach ($rows as $index => $row) {
            $name = trim($row['subjects'] ?? $row['subject'] ?? '');
            if (empty($name)) continue;

            // Tìm hoặc tạo program group
            $programGroupId = null;
            $pgName = trim($row['program_groups'] ?? $row['program_group'] ?? '');
            if ($pgName) {
                $programGroupId = ProgramGroup::firstOrCreate(['name' => $pgName])->id;
            }

            // Tìm hoặc tạo skill group
            $skillGroupId = null;
            $sgName = trim($row['skill_groups'] ?? $row['skill_group'] ?? '');
            if ($sgName) {
                $skillGroupId = SkillGroup::firstOrCreate(['name' => $sgName])->id;
            }

            // Tìm semester theo tên (HK1, HK2, ...)
            $semesterId = null;
            $semName = trim($row['semester'] ?? '');
            if ($semName) {
                $semesterId = Semester::where('name', $semName)->value('id');
            }

            // Số tín chỉ
            $credits = null;
            $rawCredits = trim($row['credits'] ?? '');
            if ($rawCredits !== '' && is_numeric($rawCredits)) {
                $credits = (int) $rawCredits;
            }

            // Upsert theo tên môn
            Subject::updateOrCreate(
                ['name' => $name],
                [
                    'credits'          => $credits,
                    'skill_group_id'   => $skillGroupId,
                    'program_group_id' => $programGroupId,
                    'semester_id'      => $semesterId,
                ]
            );

            $this->rowCount++;

            // Lưu relations để xử lý sau
            $prereqName = trim($row['prerequisite'] ?? '');
            $coreqName  = trim($row['corequisite']  ?? '');
            if ($prereqName) {
                $this->pendingRelations[] = ['subject' => $name, 'related' => $prereqName, 'type' => 'prerequisite'];
            }
            if ($coreqName) {
                $this->pendingRelations[] = ['subject' => $name, 'related' => $coreqName, 'type' => 'corequisite'];
            }
        }

        // Pass 2: Xử lý relations sau khi subjects đã tạo xong
        foreach ($this->pendingRelations as $rel) {
            $subjectId = Subject::where('name', $rel['subject'])->value('id');
            $relatedId = Subject::where('name', $rel['related'])->value('id');

            if ($subjectId && $relatedId) {
                SubjectRelation::updateOrCreate(
                    [
                        'subject_id'         => $subjectId,
                        'related_subject_id' => $relatedId,
                        'type'               => $rel['type'],
                    ]
                );
            }
        }
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
