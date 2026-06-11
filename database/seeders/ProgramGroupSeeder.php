<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramGroupSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('program_groups')->insert([
            ['name' => 'Đại cương',          'created_at' => now(), 'updated_at' => now()], // id=1
            ['name' => 'Cơ sở ngành',        'created_at' => now(), 'updated_at' => now()], // id=2
            ['name' => 'Chuyên ngành',       'created_at' => now(), 'updated_at' => now()], // id=3
            ['name' => 'Anh văn tăng cường', 'created_at' => now(), 'updated_at' => now()], // id=4
            ['name' => 'Tự chọn',            'created_at' => now(), 'updated_at' => now()], // id=5
        ]);
    }
}
