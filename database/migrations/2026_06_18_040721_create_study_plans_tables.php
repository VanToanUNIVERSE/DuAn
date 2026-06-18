<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('study_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('mode')->default('normal'); // normal, fast, slow
            $table->integer('target_semester_count')->nullable();
            $table->timestamps();
        });

        Schema::create('study_plan_semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_id')->constrained('study_plans')->onDelete('cascade');
            $table->integer('semester_index');
            $table->integer('expected_credits')->default(0);
            $table->timestamps();
        });

        Schema::create('study_plan_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_semester_id')->constrained('study_plan_semesters')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_plan_subjects');
        Schema::dropIfExists('study_plan_semesters');
        Schema::dropIfExists('study_plans');
    }
};
