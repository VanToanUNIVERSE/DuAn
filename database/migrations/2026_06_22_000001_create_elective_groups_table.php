<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elective_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_framework_id')
                  ->constrained('curriculum_frameworks')
                  ->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('required_credits');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elective_groups');
    }
};
