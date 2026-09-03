<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skema khusus untuk semua data hasil tarikan BPS.
        DB::statement('CREATE SCHEMA IF NOT EXISTS data_bps');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS data_bps CASCADE');
    }
};
