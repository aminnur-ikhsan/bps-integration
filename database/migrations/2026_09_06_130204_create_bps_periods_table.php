<?php

// database/migrations/2026_09_06_130204_create_bps_periods_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_bps.bps_periods', function (Blueprint $table) {
            $table->id();
            $table->string('domain_id', 4);
            $table->integer('var_id')->nullable();
            $table->integer('th_id');
            $table->string('th'); // label periode, bukan angka murni (th_id ≠ tahun)
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'th_id']);
            $table->foreign('domain_id')->references('domain_id')->on('data_bps.bps_domains')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_bps.bps_periods');
    }
};
