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
        Schema::create('program_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('program_group_id')->nullable()->constrained('program_groups')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            try {
                $table->dropForeign(['program_group_id']);
            } catch (\Exception $e) {
                // Ignore
            }
            try {
                $table->dropColumn('program_group_id');
            } catch (\Exception $e) {
                // Ignore
            }
        });

        Schema::dropIfExists('program_groups');
    }
};
