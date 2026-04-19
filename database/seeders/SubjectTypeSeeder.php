<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('subject_types')->insert([
            ['name' => 'Đại cương',   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cơ sở ngành', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Chuyên ngành','created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tự chọn',     'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
