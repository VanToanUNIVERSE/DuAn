<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SubjectsTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    public function title(): string
    {
        return 'Danh sách môn học';
    }

    public function headings(): array
    {
        return [
            'subject_code',    // A — BẮT BUỘC, dùng làm khoá upsert
            'subjects',        // B — BẮT BUỘC, tên môn học
            'credits',         // C
            'program_groups',  // D
            'skill_groups',    // E
            'prerequisite',    // F
            'corequisite',     // G
            'requirement_type',// H
            'is_elective',     // I — required / elective
            'elective_group',  // J — Tên nhóm tự chọn (chỉ điền khi is_elective = elective)
            'required_credits',// K — Số TC cần tích lũy trong nhóm
        ];
    }

    public function array(): array
    {
        return [
            ['IT001', 'Nhập môn lập trình',          1,  'Đại cương',   'Lập trình',       '',      '',      'none',             'required', '',              ''],
            ['IT002', 'Lập trình căn bản',            3,  'Đại cương',   'Lập trình',       'IT001', '',      'completed_basic',  'required', '',              ''],
            ['IT002L','Lập trình căn bản - TH',       2,  'Đại cương',   'Lập trình',       'IT001', 'IT002', 'completed_basic',  'required', '',              ''],
            ['MATH01','Toán rời rạc 1',               3,  'Cơ sở ngành', 'Toán – Khoa học', '',      '',      'none',             'required', '',              ''],
            ['CN001', 'Lập trình Web',                2,  'Chuyên ngành','Lập trình',       'IT002', '',      'completed_major',  'elective', 'Web Electives',  4],
            ['CN002', 'Lập trình Di động',            2,  'Chuyên ngành','Lập trình',       'IT002', '',      'completed_major',  'elective', 'Web Electives',  ''],
            ['CN003', 'Thiết kế UI/UX',               2,  'Chuyên ngành','Lập trình',       '',      '',      'completed_major',  'elective', 'Web Electives',  ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Dòng tiêu đề — nền tối, chữ trắng, in đậm
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1A3A3A'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Cột A (subject_code) — highlight bắt buộc
            'A' => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF0CC']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'C' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'F' => ['font' => ['italic' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF3CD']]],
            'G' => ['font' => ['italic' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD1F5E0']]],
            'H' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            // Cột loai_mon — highlight xanh lam nhẹ
            'I' => [
                'font'      => ['bold' => true],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0F0FF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'J' => ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEFF6FF']]],
            'K' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 38,
            'C' => 9,
            'D' => 30,
            'E' => 28,
            'F' => 28,
            'G' => 28,
            'H' => 20,
            'I' => 14,
            'J' => 28,
            'K' => 14,
        ];
    }
}
