<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elective_group_subjects', function (Blueprint $table) {
            $table->foreignId('elective_group_id')
                  ->constrained('elective_groups')
                  ->cascadeOnDelete();
            $table->foreignId('subject_id')
                  ->constrained('subjects')
                  ->cascadeOnDelete();
            $table->primary(['elective_group_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elective_group_subjects');
    }
};
