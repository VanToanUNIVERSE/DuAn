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
            SubjectRelationSeeder::class,
            UserSeeder::class,
            UserGradeSeeder::class,
        ]);
    }
}

