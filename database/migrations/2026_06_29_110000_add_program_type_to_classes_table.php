<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (!Schema::hasColumn('classes', 'program_type')) {
                // Hệ đào tạo của lớp (Chính quy / Tiên tiến) — để suy ra chương trình của SV
                $table->string('program_type')->default('Chính quy')->after('cohort');
            }
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'program_type')) {
                $table->dropColumn('program_type');
            }
        });
    }
};
