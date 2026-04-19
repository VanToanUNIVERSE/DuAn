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
        Schema::create('curriculum_frameworks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_program_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('number_of_semesters')->nullable();
            $table->integer('total_credits')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_frameworks');
    }
};
