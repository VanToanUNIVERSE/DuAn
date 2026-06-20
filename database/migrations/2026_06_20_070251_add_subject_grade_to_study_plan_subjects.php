<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_plan_subjects', function (Blueprint $table) {
            // Điểm riêng của từng row — độc lập hoàn toàn giữa bản gốc và bản retake
            // UserGrade = max(original.subject_grade, retake.subject_grade)
            $table->decimal('subject_grade', 4, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('study_plan_subjects', function (Blueprint $table) {
            $table->dropColumn('subject_grade');
        });
    }
};
