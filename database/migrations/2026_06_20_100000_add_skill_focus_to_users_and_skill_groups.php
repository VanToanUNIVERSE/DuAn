<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pref_skill_focus', 50)->nullable()->after('pref_graduation_semester')
                  ->comment('backend|frontend|ai|data|mobile|devops|testing|security');
        });

        Schema::table('skill_groups', function (Blueprint $table) {
            $table->string('focus_area', 50)->nullable()->after('name')
                  ->comment('backend|frontend|ai|data|mobile|devops|testing|security|core');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pref_skill_focus');
        });

        Schema::table('skill_groups', function (Blueprint $table) {
            $table->dropColumn('focus_area');
        });
    }
};
