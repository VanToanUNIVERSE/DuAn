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
            'ID',
            'subjects',
            'prerequisite',
            'corequisite',
            'program_groups',
            'skill_groups',
            'semester',
            'credits',
        ];
    }

    public function array(): array
    {
        // Dữ liệu mẫu minh hoạ
        return [
            [1, 'Nhập môn máy học và CNTT', '',                          'Lập trình căn bản - TH', 'Khối kiến thức cơ sở ngành',       'Hệ thống máy tính', 'HK1', 1],
            [2, 'Lập trình căn bản',         'Nhập môn máy học và CNTT', 'Lập trình Giáo dục đại cương', 'Khối kiến thức Giáo dục đại cương', 'Lập trình',           'HK2', 2],
            [3, 'Lập trình căn bản - TH',    '',                          'Lập trình căn bản',             'Khối kiến thức Giáo dục đại cương', 'Lập trình',           'HK2', 2],
            [4, 'Toán rời rạc 1',             '',                          '',                              'Khối kiến thức cơ sở ngành',       'Toán – Khoa học cơ bản','HK1', 3],
            [5, 'Toán rời rạc 2',             'Toán rời rạc 1',           '',                              'Khối kiến thức cơ sở ngành',       'Toán – Khoa học cơ bản','HK2', 3],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Dòng tiêu đề — nền xanh đậm, chữ trắng, in đậm
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1A3A3A'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Cột ID — căn giữa
            'A' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'C' => ['font' => ['italic' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF3CD']]],
            'D' => ['font' => ['italic' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD1F5E0']]],
            'G' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'H' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 35,
            'C' => 30,
            'D' => 30,
            'E' => 35,
            'F' => 30,
            'G' => 10,
            'H' => 10,
        ];
    }
}
