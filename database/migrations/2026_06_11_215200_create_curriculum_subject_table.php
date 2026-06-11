<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_framework_id')
                  ->constrained('curriculum_frameworks')
                  ->cascadeOnDelete();
            $table->foreignId('semester_id')
                  ->constrained('semesters')
                  ->cascadeOnDelete();
            $table->foreignId('subject_id')
                  ->constrained('subjects')
                  ->cascadeOnDelete();
            $table->timestamps();

            // Một môn chỉ xuất hiện 1 lần trong 1 học kỳ của 1 chương trình
            $table->unique(['curriculum_framework_id', 'semester_id', 'subject_id'], 'cs_fw_sem_subj_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_subject');
    }
};
