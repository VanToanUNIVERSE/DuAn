<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('target_semesters')->default(8)->after('target_semester_count');
            $table->unsignedSmallInteger('tc_per_sem')->default(18)->after('target_semesters');
        });

        // Migrate existing data: map mode → target_semesters + tc_per_sem
        DB::table('study_plans')->where('mode', 'fast')->update(['target_semesters' => 6,  'tc_per_sem' => 22]);
        DB::table('study_plans')->where('mode', 'normal')->update(['target_semesters' => 8,  'tc_per_sem' => 18]);
        DB::table('study_plans')->where('mode', 'slow')->update(['target_semesters' => 10, 'tc_per_sem' => 14]);
    }

    public function down(): void
    {
        Schema::table('study_plans', function (Blueprint $table) {
            $table->dropColumn(['target_semesters', 'tc_per_sem']);
        });
    }
};
