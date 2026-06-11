<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        // subject_type_id: 1=Đại cương | 2=Cơ sở ngành | 3=Chuyên ngành | 4=Tự chọn
        // skill_group_id: 1=Toán-KHCB | 2=Lập trình | 3=Hệ thống | 4=Mạng | 5=CSDL | 6=Kỹ nghệ PM | 7=AI
        // program_group_id: 1=Đại cương | 2=Cơ sở ngành | 3=Chuyên ngành | 4=Anh văn tăng cường | 5=Tự chọn

        $subjects = [
            // ── Toán – Khoa học cơ bản ──────────────────────────────
            ['name' => 'Giải tích',                        'credits' => 3, 'subject_type_id' => 1, 'skill_group_id' => 1, 'program_group_id' => 1, 'semester_id' => 1],
            ['name' => 'Đại số tuyến tính',                'credits' => 3, 'subject_type_id' => 1, 'skill_group_id' => 1, 'program_group_id' => 1, 'semester_id' => 1],
            ['name' => 'Xác suất – Thống kê',              'credits' => 3, 'subject_type_id' => 1, 'skill_group_id' => 1, 'program_group_id' => 1, 'semester_id' => 2],
            ['name' => 'Toán rời rạc',                     'credits' => 3, 'subject_type_id' => 2, 'skill_group_id' => 1, 'program_group_id' => 2, 'semester_id' => 2],

            // ── Lập trình ────────────────────────────────────────────
            ['name' => 'Lập trình căn bản',                'credits' => 3, 'subject_type_id' => 2, 'skill_group_id' => 2, 'program_group_id' => 2, 'semester_id' => 1], // id=5
            ['name' => 'Lập trình hướng đối tượng',        'credits' => 3, 'subject_type_id' => 2, 'skill_group_id' => 2, 'program_group_id' => 2, 'semester_id' => 2], // id=6  (tiên quyết: 5)
            ['name' => 'Cấu trúc dữ liệu và giải thuật',   'credits' => 4, 'subject_type_id' => 2, 'skill_group_id' => 2, 'program_group_id' => 2, 'semester_id' => 3], // id=7  (tiên quyết: 5)
            ['name' => 'Lập trình Web',                     'credits' => 3, 'subject_type_id' => 3, 'skill_group_id' => 2, 'program_group_id' => 3, 'semester_id' => 4], // id=8  (tiên quyết: 6)
            ['name' => 'Phát triển ứng dụng di động',      'credits' => 3, 'subject_type_id' => 4, 'skill_group_id' => 2, 'program_group_id' => 5, 'semester_id' => 5], // id=9  (tiên quyết: 6)

            // ── Hệ thống máy tính ────────────────────────────────────
            ['name' => 'Kiến trúc máy tính',               'credits' => 3, 'subject_type_id' => 2, 'skill_group_id' => 3, 'program_group_id' => 2, 'semester_id' => 2], // id=10
            ['name' => 'Hệ điều hành',                     'credits' => 3, 'subject_type_id' => 2, 'skill_group_id' => 3, 'program_group_id' => 2, 'semester_id' => 3], // id=11 (tiên quyết: 10)

            // ── Mạng máy tính ─────────────────────────────────────────
            ['name' => 'Mạng máy tính',                    'credits' => 3, 'subject_type_id' => 2, 'skill_group_id' => 4, 'program_group_id' => 2, 'semester_id' => 4], // id=12 (tiên quyết: 11)
            ['name' => 'An toàn thông tin',                'credits' => 3, 'subject_type_id' => 3, 'skill_group_id' => 4, 'program_group_id' => 3, 'semester_id' => 5], // id=13 (tiên quyết: 12)

            // ── Cơ sở dữ liệu ─────────────────────────────────────────
            ['name' => 'Cơ sở dữ liệu',                   'credits' => 4, 'subject_type_id' => 2, 'skill_group_id' => 5, 'program_group_id' => 2, 'semester_id' => 3], // id=14
            ['name' => 'Cơ sở dữ liệu nâng cao',          'credits' => 3, 'subject_type_id' => 3, 'skill_group_id' => 5, 'program_group_id' => 3, 'semester_id' => 4], // id=15 (tiên quyết: 14)

            // ── Kỹ nghệ phần mềm ──────────────────────────────────────
            ['name' => 'Công nghệ phần mềm',               'credits' => 3, 'subject_type_id' => 2, 'skill_group_id' => 6, 'program_group_id' => 2, 'semester_id' => 4], // id=16 (tiên quyết: 6)
            ['name' => 'Phân tích và thiết kế hệ thống',   'credits' => 3, 'subject_type_id' => 3, 'skill_group_id' => 6, 'program_group_id' => 3, 'semester_id' => 5], // id=17 (tiên quyết: 16)
            ['name' => 'Kiểm thử phần mềm',                'credits' => 3, 'subject_type_id' => 3, 'skill_group_id' => 6, 'program_group_id' => 3, 'semester_id' => 5], // id=18 (tiên quyết: 16)

            // ── Trí tuệ nhân tạo ──────────────────────────────────────
            ['name' => 'Trí tuệ nhân tạo',                 'credits' => 3, 'subject_type_id' => 4, 'skill_group_id' => 7, 'program_group_id' => 5, 'semester_id' => 6], // id=19 (tiên quyết: 7)
            ['name' => 'Học máy',                           'credits' => 3, 'subject_type_id' => 4, 'skill_group_id' => 7, 'program_group_id' => 5, 'semester_id' => 7], // id=20 (tiên quyết: 19)

            // ── Anh văn tăng cường ────────────────────────────────────
            ['name' => 'Anh văn tăng cường 1',             'credits' => 3, 'subject_type_id' => 1, 'skill_group_id' => 1, 'program_group_id' => 4, 'semester_id' => 1], // id=21
            ['name' => 'Anh văn tăng cường 2',             'credits' => 3, 'subject_type_id' => 1, 'skill_group_id' => 1, 'program_group_id' => 4, 'semester_id' => 2], // id=22
        ];

        foreach ($subjects as $subject) {
            DB::table('subjects')->insert(array_merge($subject, [
                'created_at'  => now(),
                'updated_at'  => now(),
            ]));
        }
    }
}
