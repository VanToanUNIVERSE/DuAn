<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserGradeSeeder extends Seeder
{
    public function run(): void
    {
        // Helper: lấy user_id và subject_id theo tên
        $uid = fn(string $code) => DB::table('users')->where('student_code', $code)->value('id');
        $sid = fn(string $name) => DB::table('subjects')->where('name', $name)->value('id');

        $now = now();

        // ────────────────────────────────────────────────────────────────
        // SV001 — Tân Sinh: chưa học môn nào → để trống (không insert)
        // Kết quả mong đợi: chỉ gợi ý các môn KHÔNG có tiên quyết
        //   (Giải tích, Đại số TT, Xác suất TK, Toán rời rạc,
        //    Lập trình căn bản, Kiến trúc MT)
        // ────────────────────────────────────────────────────────────────

        // ────────────────────────────────────────────────────────────────
        // SV002 — Trung Kỳ: đã qua các môn căn bản học kỳ 1 & 2
        // Kết quả mong đợi: gợi ý OOP, CTDL, Hệ điều hành, Cơ sở dữ liệu
        // ────────────────────────────────────────────────────────────────
        $sv2 = $uid('SV002');
        $grades_sv2 = [
            ['subject' => 'Giải tích',             'grade' => 7.5],
            ['subject' => 'Đại số tuyến tính',      'grade' => 6.5],
            ['subject' => 'Xác suất – Thống kê',    'grade' => 7.0],
            ['subject' => 'Toán rời rạc',           'grade' => 8.0],
            ['subject' => 'Lập trình căn bản',      'grade' => 8.5],
            ['subject' => 'Kiến trúc máy tính',     'grade' => 7.0],
        ];
        foreach ($grades_sv2 as $g) {
            DB::table('user_grades')->insert([
                'user_id'    => $sv2,
                'subject_id' => $sid($g['subject']),
                'grade'      => $g['grade'],
                'status'     => 'passed',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ────────────────────────────────────────────────────────────────
        // SV003 — Cuối Năm: đã qua OOP, CTDL, Hệ điều hành, CSDL, Công nghệ PM
        // Kết quả mong đợi: gợi ý Web, Mobile, Mạng MT, CSDL NC,
        //                   Phân tích TK HT, Kiểm thử PM, Trí tuệ NT
        // ────────────────────────────────────────────────────────────────
        $sv3 = $uid('SV003');
        $grades_sv3 = [
            ['subject' => 'Giải tích',                        'grade' => 8.0],
            ['subject' => 'Đại số tuyến tính',                 'grade' => 7.5],
            ['subject' => 'Xác suất – Thống kê',              'grade' => 7.0],
            ['subject' => 'Toán rời rạc',                     'grade' => 8.5],
            ['subject' => 'Lập trình căn bản',                'grade' => 9.0],
            ['subject' => 'Lập trình hướng đối tượng',        'grade' => 8.5],
            ['subject' => 'Cấu trúc dữ liệu và giải thuật',   'grade' => 8.0],
            ['subject' => 'Kiến trúc máy tính',               'grade' => 7.5],
            ['subject' => 'Hệ điều hành',                     'grade' => 7.0],
            ['subject' => 'Cơ sở dữ liệu',                   'grade' => 8.5],
            ['subject' => 'Công nghệ phần mềm',               'grade' => 8.0],
        ];
        foreach ($grades_sv3 as $g) {
            DB::table('user_grades')->insert([
                'user_id'    => $sv3,
                'subject_id' => $sid($g['subject']),
                'grade'      => $g['grade'],
                'status'     => 'passed',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
