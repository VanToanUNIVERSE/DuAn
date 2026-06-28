<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * 10 tài khoản được chỉ định: 1 admin + 9 sinh viên.
     * Dùng updateOrCreate (khóa theo email) nên chạy lại nhiều lần vẫn an toàn,
     * không gây lỗi trùng và không đụng tới các user khác trong DB.
     * Mật khẩu mặc định cho tất cả: "password".
     */
    public function run(): void
    {
        $accounts = [
            // Quản trị viên — vào được khu /admin
            ['admin@test.com',   'admin',     'Quản Trị Viên',    'ADMIN', true],

            // Sinh viên
            ['newbie@test.com',  'sv_newbie', 'Nguyễn Tân Sinh',  'SV001', false],
            ['midterm@test.com', 'sv_mid',    'Trần Trung Kỳ',    'SV002', false],
            ['senior@test.com',  'sv_senior', 'Lê Cuối Năm',      'SV003', false],
            ['sv004@test.com',   'sv004',     'Phạm Minh Quân',   'SV004', false],
            ['sv005@test.com',   'sv005',     'Hoàng Thị Lan',    'SV005', false],
            ['sv006@test.com',   'sv006',     'Vũ Đức Anh',       'SV006', false],
            ['sv007@test.com',   'sv007',     'Đặng Thu Hà',      'SV007', false],
            ['sv008@test.com',   'sv008',     'Bùi Gia Bảo',      'SV008', false],
            ['sv009@test.com',   'sv009',     'Ngô Khánh Linh',   'SV009', false],
        ];

        foreach ($accounts as [$email, $username, $fullName, $studentCode, $isAdmin]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'username'     => $username,
                    'fullName'     => $fullName,
                    'student_code' => $studentCode,
                    'is_admin'     => $isAdmin,
                    'password'     => 'password', // cast 'hashed' tự băm
                ]
            );
        }
    }
}
