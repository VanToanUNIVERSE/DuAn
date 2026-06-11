<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop foreign key constraint on subjects table first if it exists
        try {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropForeign(['subject_group_id']);
            });
        } catch (\Exception $e) {
            // Foreign key does not exist, ignore
        }

        // 2. Rename subject_groups table to skill_groups if it exists
        if (Schema::hasTable('subject_groups')) {
            Schema::rename('subject_groups', 'skill_groups');
        }

        // 3. Rename subject_group_id to skill_group_id on subjects table if it exists
        if (Schema::hasColumn('subjects', 'subject_group_id')) {
            DB::statement('ALTER TABLE subjects CHANGE subject_group_id skill_group_id BIGINT UNSIGNED NULL');
        }

        // 4. Create foreign key constraint pointing to the new table
        try {
            Schema::table('subjects', function (Blueprint $table) {
                $table->foreign('skill_group_id')->references('id')->on('skill_groups')->cascadeOnDelete();
            });
        } catch (\Exception $e) {
            // Foreign key already exists or failed to create, ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Drop the new foreign key
        try {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropForeign(['skill_group_id']);
            });
        } catch (\Exception $e) {
            // Foreign key does not exist, ignore
        }

        // 2. Rename the column back if it exists
        if (Schema::hasColumn('subjects', 'skill_group_id')) {
            DB::statement('ALTER TABLE subjects CHANGE skill_group_id subject_group_id BIGINT UNSIGNED NULL');
        }

        // 3. Rename the table back if it exists
        if (Schema::hasTable('skill_groups')) {
            Schema::rename('skill_groups', 'subject_groups');
        }

        // 4. Re-add the old foreign key constraint
        try {
            Schema::table('subjects', function (Blueprint $table) {
                $table->foreign('subject_group_id')->references('id')->on('subject_groups')->cascadeOnDelete();
            });
        } catch (\Exception $e) {
            // Failed to recreate old foreign key constraint, ignore
        }
    }
};
