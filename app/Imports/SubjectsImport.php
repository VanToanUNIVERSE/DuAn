<?php

namespace App\Imports;

use App\Models\Subject;
use App\Models\SkillGroup;
use App\Models\ProgramGroup;
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

            $subjectCode = strtoupper(trim($row['subject_code'] ?? $row['id'] ?? ''));

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

            // Số tín chỉ
            $credits = null;
            $rawCredits = trim($row['credits'] ?? '');
            if ($rawCredits !== '' && is_numeric($rawCredits)) {
                $credits = (int) $rawCredits;
            }

            // Requirement type
            $validTypes = array_keys(\App\Models\Subject::REQUIREMENT_TYPES);
            $reqType = trim($row['requirement_type'] ?? 'none');
            if (!in_array($reqType, $validTypes)) $reqType = 'none';

            // Upsert: nếu có subject_code thì match theo code, không thì theo tên
            $matchKey = $subjectCode ? ['subject_code' => $subjectCode] : ['name' => $name];

            // Kiểm tra trùng code nếu upsert theo tên (tránh tạo trùng code)
            if (!$subjectCode) {
                // Không có code trong file → bỏ qua, không thể upsert an toàn
                $this->errors[] = "Dòng " . ($index + 2) . ": Môn \"{$name}\" thiếu mã môn, bỏ qua.";
                continue;
            }

            Subject::updateOrCreate(
                $matchKey,
                [
                    'name'             => $name,
                    'credits'          => $credits,
                    'skill_group_id'   => $skillGroupId,
                    'program_group_id' => $programGroupId,
                    'requirement_type' => $reqType,
                ]
            );

            $this->rowCount++;

            // Lưu relations để xử lý sau
            $prereqString = trim($row['prerequisite'] ?? '');
            $coreqString  = trim($row['corequisite']  ?? '');
            
            if ($prereqString) {
                $prereqCodes = array_map('trim', explode(',', $prereqString));
                foreach ($prereqCodes as $code) {
                    if ($code) {
                        $this->pendingRelations[] = ['subject' => $subjectCode, 'related' => strtoupper($code), 'type' => 'prerequisite'];
                    }
                }
            }
            if ($coreqString) {
                $coreqCodes = array_map('trim', explode(',', $coreqString));
                foreach ($coreqCodes as $code) {
                    if ($code) {
                        $this->pendingRelations[] = ['subject' => $subjectCode, 'related' => strtoupper($code), 'type' => 'corequisite'];
                    }
                }
            }
        }

        // Pass 2: Xử lý relations sau khi subjects đã tạo xong
        foreach ($this->pendingRelations as $rel) {
            $subjectId = Subject::where('subject_code', $rel['subject'])->value('id');
            $relatedId = Subject::where('subject_code', $rel['related'])->value('id');

            if ($subjectId && $relatedId && $subjectId != $relatedId) {
                SubjectRelation::updateOrCreate(
                    [
                        'subject_id'         => $subjectId,
                        'related_subject_id' => $relatedId,
                        'type'               => $rel['type'],
                    ]
                );
                
                // Nếu là song hành, tạo luôn chiều ngược lại
                if ($rel['type'] === 'corequisite') {
                    SubjectRelation::updateOrCreate(
                        [
                            'subject_id'         => $relatedId,
                            'related_subject_id' => $subjectId,
                            'type'               => 'corequisite',
                        ]
                    );
                }
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
