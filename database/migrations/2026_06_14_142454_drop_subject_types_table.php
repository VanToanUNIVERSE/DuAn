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
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['subject_type_id']);
            $table->dropColumn('subject_type_id');
        });
        Schema::dropIfExists('subject_types');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('subject_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('subject_type_id')->nullable()->constrained('subject_types')->nullOnDelete();
        });
    }
};
