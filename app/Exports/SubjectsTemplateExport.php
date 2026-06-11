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
            'subject_code',   // A — BẮT BUỘC, dùng làm khoá upsert
            'subjects',       // B — BẮT BUỘC, tên môn học
            'credits',        // C
            'program_groups', // D
            'skill_groups',   // E
            'prerequisite',   // F
            'corequisite',    // G
            'requirement_type', // H
        ];
    }

    public function array(): array
    {
        // Dữ liệu mẫu minh hoạ
        return [
            ['IT001', 'Nhập môn lập trình',         1,  'Đại cương',     'Lập trình',            '',      '',       'none'],
            ['IT002', 'Lập trình căn bản',           3,  'Đại cương',     'Lập trình',            'IT001', '',       'completed_basic'],
            ['IT002L','Lập trình căn bản - Thực hành',2, 'Đại cương',     'Lập trình',            'IT001', 'IT002',  'completed_basic'],
            ['MATH01','Toán rời rạc 1',              3,  'Cơ sở ngành',   'Toán – Khoa học',      '',      '',       'none'],
            ['MATH02','Toán rời rạc 2',              3,  'Cơ sở ngành',   'Toán – Khoa học',      'MATH01','',       'none'],
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
        ];
    }
}
