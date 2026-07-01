<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Gộp các kỳ trùng semester_index còn sót lại (bug race condition cũ) trước khi
        // thêm ràng buộc unique, nếu không migration sẽ lỗi vì dữ liệu đang vi phạm.
        $duplicates = DB::table('study_plan_semesters')
            ->select('study_plan_id', 'semester_index')
            ->groupBy('study_plan_id', 'semester_index')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $rows = DB::table('study_plan_semesters')
                ->where('study_plan_id', $dup->study_plan_id)
                ->where('semester_index', $dup->semester_index)
                ->orderBy('id')
                ->get(['id']);

            $keeperId = $rows->first()->id;
            $dupeIds  = $rows->slice(1)->pluck('id');

            DB::table('study_plan_subjects')
                ->whereIn('study_plan_semester_id', $dupeIds)
                ->update(['study_plan_semester_id' => $keeperId]);

            DB::table('study_plan_semesters')->whereIn('id', $dupeIds)->delete();
        }

        Schema::table('study_plan_semesters', function (Blueprint $table) {
            $table->unique(['study_plan_id', 'semester_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_plan_semesters', function (Blueprint $table) {
            $table->dropUnique(['study_plan_id', 'semester_index']);
        });
    }
};
