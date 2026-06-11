<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Xóa FK constraint trước
            try {
                $table->dropForeign(['semester_id']);
            } catch (\Exception $e) {}

            $table->dropColumn('semester_id');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('semester_id')
                  ->nullable()
                  ->after('program_group_id')
                  ->constrained('semesters')
                  ->cascadeOnDelete();
        });
    }
};
