<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SubjectTypeSeeder::class,
            SkillGroupSeeder::class,
            ProgramGroupSeeder::class,
            TrainingProgramSeeder::class,
            SubjectSeeder::class,
            SkillGroupFocusSeeder::class,
            SubjectRelationSeeder::class,
            UserSeeder::class,
            FacultyMajorClassSeeder::class,
            MajorStudentsSeeder::class,
            UserGradeSeeder::class,
        ]);
    }
}

