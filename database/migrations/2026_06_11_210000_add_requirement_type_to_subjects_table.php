<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->enum('requirement_type', [
                'none',
                'completed_basic',
                'completed_major',
                'completed_all',
                'min_credits',
            ])->default('none')->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('requirement_type');
        });
    }
};
