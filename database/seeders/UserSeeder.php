<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                // Sinh viên mới — chưa học môn nào
                'username'     => 'sv_newbie',
                'email'        => 'newbie@test.com',
                'password'     => Hash::make('password'),
                'fullName'     => 'Nguyễn Tân Sinh',
                'student_code' => 'SV001',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                // Sinh viên giữa kỳ — đã qua các môn căn bản
                'username'     => 'sv_mid',
                'email'        => 'midterm@test.com',
                'password'     => Hash::make('password'),
                'fullName'     => 'Trần Trung Kỳ',
                'student_code' => 'SV002',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                // Sinh viên năm cuối — đã qua hầu hết môn
                'username'     => 'sv_senior',
                'email'        => 'senior@test.com',
                'password'     => Hash::make('password'),
                'fullName'     => 'Lê Cuối Năm',
                'student_code' => 'SV003',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);
    }
}
