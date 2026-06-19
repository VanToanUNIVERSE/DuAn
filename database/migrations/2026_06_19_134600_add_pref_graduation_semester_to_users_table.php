<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm các trường lưu mục tiêu tốt nghiệp và cường độ học tập cho sinh viên.
     * Dùng để cá nhân hóa tư vấn kế hoạch học tập sâu hơn.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Học kỳ mục tiêu tốt nghiệp (1 = kỳ 1 năm 1, 8 = kỳ 2 năm 4, 10 = kỳ 2 năm 5)
            $table->unsignedTinyInteger('pref_graduation_semester')
                ->nullable()
                ->after('pref_target_years')
                ->comment('Học kỳ mục tiêu tốt nghiệp (số kỳ học, VD: 8 = 4 năm, 10 = 5 năm)');

            // Cường độ học tập ưa thích
            $table->enum('pref_study_intensity', ['light', 'balanced', 'intensive'])
                ->default('balanced')
                ->after('pref_graduation_semester')
                ->comment('Cường độ học tập: light=14TC/kỳ, balanced=18TC/kỳ, intensive=22TC/kỳ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pref_graduation_semester', 'pref_study_intensity']);
        });
    }
};
