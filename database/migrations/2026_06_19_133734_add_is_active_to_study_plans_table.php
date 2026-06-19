<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    /**
     * Thêm cột is_active để đảm bảo mỗi sinh viên chỉ có 1 kế hoạch đang hoạt động.
     * Khi tạo kế hoạch mới, kế hoạch cũ sẽ bị set is_active = false (không xóa).
     */
    public function up(): void
    {
        Schema::table('study_plans', function (Blueprint $table) {
            // Chỉ 1 kế hoạch có is_active = true mỗi lúc cho mỗi user
            $table->boolean('is_active')->default(false)->after('is_saved')
                  ->comment('Kế hoạch đang hoạt động hiện tại. Mỗi user chỉ có 1 kế hoạch is_active = true.');
        });

        // Set is_active = true cho kế hoạch is_saved mới nhất của mỗi user (migration data)
        DB::statement("
            UPDATE study_plans sp
            INNER JOIN (
                SELECT user_id, MAX(id) as max_id
                FROM study_plans
                WHERE is_saved = 1 AND deleted_at IS NULL
                GROUP BY user_id
            ) latest ON sp.id = latest.max_id
            SET sp.is_active = 1
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_plans', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
