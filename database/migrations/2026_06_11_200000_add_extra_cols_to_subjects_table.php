<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'subject_code')) {
                $table->string('subject_code')->nullable()->after('id');
            }
            if (!Schema::hasColumn('subjects', 'expected_semester')) {
                $table->integer('expected_semester')->nullable()->after('semester_id');
            }
            if (!Schema::hasColumn('subjects', 'note')) {
                $table->text('note')->nullable()->after('expected_semester');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['subject_code', 'expected_semester', 'note']);
        });
    }
};
