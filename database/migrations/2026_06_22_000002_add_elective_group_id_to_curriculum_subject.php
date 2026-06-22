<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_subject', function (Blueprint $table) {
            $table->foreignId('elective_group_id')
                  ->nullable()
                  ->after('subject_id')
                  ->constrained('elective_groups')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_subject', function (Blueprint $table) {
            $table->dropForeign(['elective_group_id']);
            $table->dropColumn('elective_group_id');
        });
    }
};
