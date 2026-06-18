<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TrainingProgram;
use App\Models\CurriculumFramework;
use App\Models\Semester;

class TrainingProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 3 niên khóa bắt đầu từ 2022
        $cohorts = [
            2022 => '2022-2026',
            2023 => '2023-2027',
            2024 => '2024-2028',
        ];

        foreach ($cohorts as $startYear => $academicYear) {
            // 1. Chương trình đào tạo chính quy
            $regularProgram = TrainingProgram::create([
                'program_name' => "Chương trình đào tạo Công nghệ thông tin (Chính quy - Niên khóa $academicYear)",
                'education_level' => 'Đại học',
                'program_code' => "CNTT-CQ-$startYear",
                'program_type' => 'Chính quy',
                'program_duration' => 4,
                'academic_year' => $academicYear,
            ]);

            // Chương trình khung cho chính quy
            $regularFramework = CurriculumFramework::create([
                'training_program_id' => $regularProgram->id,
                'number_of_semesters' => 8, // 2 học kỳ / năm * 4 năm
                'total_credits' => 120,
            ]);

            // Học kỳ cho chính quy (8 học kỳ)
            for ($i = 1; $i <= 8; $i++) {
                Semester::create([
                    'curriculum_framework_id' => $regularFramework->id,
                    'name' => (string)$i,
                    'total_credits' => 10, // Trung bình 10 tín chỉ mỗi kỳ (để tổng = 120)
                ]);
            }

            // 2. Chương trình đào tạo tiên tiến
            $advancedProgram = TrainingProgram::create([
                'program_name' => "Chương trình đào tạo Công nghệ thông tin (Tiên tiến - Niên khóa $academicYear)",
                'education_level' => 'Đại học',
                'program_code' => "CNTT-TT-$startYear",
                'program_type' => 'Tiên tiến',
                'program_duration' => 4,
                'academic_year' => $academicYear,
            ]);

            // Chương trình khung cho tiên tiến
            $advancedFramework = CurriculumFramework::create([
                'training_program_id' => $advancedProgram->id,
                'number_of_semesters' => 8,
                'total_credits' => 135, 
            ]);

            // Học kỳ cho tiên tiến (8 học kỳ)
            for ($i = 1; $i <= 8; $i++) {
                Semester::create([
                    'curriculum_framework_id' => $advancedFramework->id,
                    'name' => (string)$i,
                    'total_credits' => 12, // Trung bình 11-12 tín chỉ mỗi kỳ
                ]);
            }
        }
    }
}
