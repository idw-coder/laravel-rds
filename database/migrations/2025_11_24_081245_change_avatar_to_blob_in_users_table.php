<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 生SQLでLONGBLOBに変更
        DB::statement('ALTER TABLE users MODIFY avatar LONGBLOB NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 元のLONGTEXTに戻す
        DB::statement('ALTER TABLE users MODIFY avatar LONGTEXT NULL');
    }
};