<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_plan_subjects', function (Blueprint $table) {
            $table->boolean('is_retake')->default(false);
            $table->unsignedSmallInteger('original_attempt_sem')->nullable();
            $table->decimal('original_grade', 4, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('study_plan_subjects', function (Blueprint $table) {
            $table->dropColumn(['is_retake', 'original_attempt_sem', 'original_grade']);
        });
    }
};
