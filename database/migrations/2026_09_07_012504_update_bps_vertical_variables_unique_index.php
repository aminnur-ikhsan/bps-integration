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
        Schema::table('data_bps.bps_vertical_variables', function (Blueprint $table) {
            $table->dropUnique(['domain_id', 'vervar_id']);
            $table->unique(['domain_id', 'vervar_id', 'item_ver_id']);
        });
    }

    public function down(): void
    {
        Schema::table('data_bps.bps_vertical_variables', function (Blueprint $table) {
            $table->dropUnique(['domain_id', 'vervar_id', 'item_ver_id']);
            $table->unique(['domain_id', 'vervar_id']);
        });
    }
};
