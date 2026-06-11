<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE subjects MODIFY COLUMN requirement_type ENUM('none', 'completed_basic', 'completed_major', 'completed_specialized', 'completed_all', 'min_credits') DEFAULT 'none'");
    }

    public function down()
    {
        // Reverting enum changes using modify can lose data if reverting to an enum that doesn't have the value.
        // It's safer to leave the column as is, or revert only if no rows use the new value.
        // For now, we will just change the enum back, but MySQL will truncate 'completed_specialized' values.
        DB::statement("ALTER TABLE subjects MODIFY COLUMN requirement_type ENUM('none', 'completed_basic', 'completed_major', 'completed_all', 'min_credits') DEFAULT 'none'");
    }
};
