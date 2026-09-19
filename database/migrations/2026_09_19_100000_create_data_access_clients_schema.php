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
        // Skema khusus untuk klien luar yang mengakses API aplikasi ini.
        DB::statement('CREATE SCHEMA IF NOT EXISTS data_access_clients');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS data_access_clients CASCADE');
    }
};
