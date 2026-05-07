<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SubjectTypeSeeder::class,
            SubjectGroupSeeder::class,
            TrainingProgramSeeder::class,
            SubjectSeeder::class,
            SubjectRelationSeeder::class,
            UserSeeder::class,
            UserGradeSeeder::class,
        ]);
    }
}

