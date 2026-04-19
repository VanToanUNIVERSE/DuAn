<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectGroupSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('subject_groups')->insert([
            ['name' => 'Toán – Khoa học cơ bản', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Lập trình',               'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Hệ thống máy tính',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mạng máy tính',            'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cơ sở dữ liệu',           'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kỹ nghệ phần mềm',        'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Trí tuệ nhân tạo',        'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
