<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng semester_histories: mỗi bản ghi = 1 học kỳ user đã hoàn tất.
     * Bảng semester_history_items: chi tiết từng môn trong học kỳ đó.
     */
    public function up(): void
    {
        // ── Bảng chính: mỗi học kỳ đã hoàn tất ────────────────────────────
        Schema::create('semester_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('semester_number'); // học kỳ số mấy (1-8)
            $table->string('academic_year')->nullable();    // niên khóa, vd "2022-2026"
            $table->string('program_type')->nullable();     // hệ đào tạo
            $table->unsignedSmallInteger('total_credits')->default(0); // tổng tín chỉ HK này
            $table->unsignedSmallInteger('passed_credits')->default(0); // tín chỉ đã pass
            $table->decimal('gpa', 4, 2)->nullable();      // điểm TB học kỳ
            $table->timestamp('completed_at')->useCurrent();
            $table->timestamps();
        });

        // ── Bảng chi tiết: từng môn trong học kỳ ──────────────────────────
        Schema::create('semester_history_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_history_id')
                  ->constrained('semester_histories')
                  ->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->float('grade')->nullable();
            $table->string('status')->nullable();  // 'pass' | 'fail'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semester_history_items');
        Schema::dropIfExists('semester_histories');
    }
};
