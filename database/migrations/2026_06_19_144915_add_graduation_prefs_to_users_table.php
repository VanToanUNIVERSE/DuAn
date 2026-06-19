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
        if (!Schema::hasColumn('users', 'pref_graduation_semester')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('pref_graduation_semester')->nullable()->after('pref_target_years')
                      ->comment('Học kỳ mục tiêu tốt nghiệp (vd: 7, 8, 9)');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pref_graduation_semester');
        });
    }
};
