<?php

// database/migrations/2026_09_09_020000_require_var_id_on_bps_vertical_variables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // A vertical variable identity is domain + var + vervar (+ item). The old
    // unique index left out var_id, so syncing one variable overwrote rows that
    // belonged to another variable but shared the same vervar_id.
    public function up(): void
    {
        Schema::table('data_bps.bps_vertical_variables', function (Blueprint $table) {
            $table->dropUnique(['domain_id', 'vervar_id', 'item_ver_id']);
        });

        // The table only caches BPS data, so dropping rows without a variable is
        // safe. They can only exist from older "all variables" syncs and would
        // block the NOT NULL change below.
        DB::table('data_bps.bps_vertical_variables')->whereNull('var_id')->delete();

        Schema::table('data_bps.bps_vertical_variables', function (Blueprint $table) {
            $table->integer('var_id')->nullable(false)->change();
            $table->unique(['domain_id', 'var_id', 'vervar_id', 'item_ver_id']);
        });
    }

    public function down(): void
    {
        Schema::table('data_bps.bps_vertical_variables', function (Blueprint $table) {
            $table->dropUnique(['domain_id', 'var_id', 'vervar_id', 'item_ver_id']);
            $table->integer('var_id')->nullable()->change();
            $table->unique(['domain_id', 'vervar_id', 'item_ver_id']);
        });
    }
};
