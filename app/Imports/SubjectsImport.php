<?php

namespace App\Imports;

use App\Models\Subject;
use App\Models\SkillGroup;
use App\Models\ProgramGroup;
use App\Models\SubjectRelation;
use App\Models\ElectiveGroup;
use App\Models\CurriculumSubject;
use App\Models\TrainingProgram;
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
    private array $pendingRelations      = [];
    private array $pendingElectiveGroups = [];

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

            // Loại môn: chấp nhận cả tiếng Anh lẫn tiếng Việt
            $loaiMon  = mb_strtolower(trim($row['is_elective'] ?? $row['loai_mon'] ?? $row['loại_môn'] ?? $row['type'] ?? ''));
            $isElective = in_array($loaiMon, ['elective', 'tự chọn', 'tu chon', 'tự_chọn', '1', 'true', 'yes', 'x', 'có', 'co']);

            // Upsert: nếu có subject_code thì match theo code, không thì theo tên
            $matchKey = $subjectCode ? ['subject_code' => $subjectCode] : ['name' => $name];

            if (!$subjectCode) {
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
                    'is_elective'      => $isElective,
                ]
            );

            // Xử lý nhóm tự chọn (elective_group): gắn vào curriculum_subject nếu có
            $electiveGroupName    = trim($row['elective_group'] ?? $row['nhom_tu_chon'] ?? '');
            $electiveRequiredCred = (int) ($row['required_credits'] ?? $row['tc_yeu_cau'] ?? 0);
            if ($isElective && $electiveGroupName) {
                $this->pendingElectiveGroups[] = [
                    'subject_code'     => $subjectCode,
                    'group_name'       => $electiveGroupName,
                    'required_credits' => $electiveRequiredCred ?: null,
                ];
            }

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

        // Pass 3: Tạo nhóm tự chọn cho tất cả framework
        if (!empty($this->pendingElectiveGroups)) {
            $frameworks = \App\Models\CurriculumFramework::all();

            // Build subject_id lookup
            $subjectIds = [];
            foreach ($this->pendingElectiveGroups as $eg) {
                $sid = Subject::where('subject_code', $eg['subject_code'])->value('id');
                $subjectIds[$eg['subject_code']] = $sid;
            }

            // Per-framework: firstOrCreate groups and link curriculum_subject
            foreach ($frameworks as $fw) {
                $createdGroups = []; // groupName => ElectiveGroup (for this framework)

                foreach ($this->pendingElectiveGroups as $eg) {
                    $subjectId = $subjectIds[$eg['subject_code']] ?? null;
                    if (!$subjectId) continue;

                    $groupName = $eg['group_name'];
                    if (!isset($createdGroups[$groupName])) {
                        $group = ElectiveGroup::firstOrCreate(
                            ['curriculum_framework_id' => $fw->id, 'name' => $groupName],
                            ['required_credits' => $eg['required_credits'] ?? 3]
                        );
                        if ($eg['required_credits'] && $eg['required_credits'] != $group->required_credits) {
                            $group->update(['required_credits' => $eg['required_credits']]);
                        }
                        $createdGroups[$groupName] = $group;
                    }

                    $group = $createdGroups[$groupName];
                    // Gắn vào pivot (framework-agnostic subject membership)
                    $group->subjects()->syncWithoutDetaching([$subjectId]);
                    // Cập nhật curriculum_subject nếu đã có trong framework này
                    CurriculumSubject::where('curriculum_framework_id', $fw->id)
                        ->where('subject_id', $subjectId)
                        ->update(['elective_group_id' => $group->id]);
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
