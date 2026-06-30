<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();                  // class_id
            $table->string('name');        // Tên lớp (vd: CNTT2024A)
            $table->foreignId('major_id')->nullable()->constrained('majors')->nullOnDelete();
            // Niên khóa: dùng chuỗi academic_year (vd "2024-2028") khớp với training_programs
            $table->string('cohort')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
