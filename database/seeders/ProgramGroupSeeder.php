<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramGroupSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('program_groups')->insert([
            ['name' => 'Anh văn tăng cường',                     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Khối kiến thức cơ sở ngành',             'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Khối kiến thức Giáo dục đại cương',      'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Khối kiến thức chuyên ngành',            'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Khối kiến thức chuyên ngành chuyên sâu', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
