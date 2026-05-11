<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm các cột lưu cấu hình chương trình của sinh viên vào bảng users.
     *
     * Các cột này ghi nhớ lựa chọn cuối cùng của user trên trang gợi ý:
     *   - pref_academic_year   : Niên khóa        (vd: '2022-2026')
     *   - pref_program_type    : Hệ đào tạo        (vd: 'Chính quy')
     *   - pref_current_semester: Học kỳ hiện tại   (vd: 3)
     *   - pref_target_years    : Mục tiêu tốt nghiệp (vd: 4 năm)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // nullable() → không bắt buộc, user mới chưa có giá trị thì dùng default JS
            $table->string('pref_academic_year')->nullable()->after('student_code');
            $table->string('pref_program_type')->nullable()->after('pref_academic_year');
            $table->unsignedTinyInteger('pref_current_semester')->nullable()->after('pref_program_type');
            $table->unsignedTinyInteger('pref_target_years')->nullable()->after('pref_current_semester');
        });
    }

    /**
     * Xóa các cột preference khi rollback.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'pref_academic_year',
                'pref_program_type',
                'pref_current_semester',
                'pref_target_years',
            ]);
        });
    }
};
