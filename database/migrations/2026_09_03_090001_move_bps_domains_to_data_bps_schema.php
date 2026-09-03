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
        // Jaga-jaga kalau tabel sudah ada di data_bps (misal setelah db:wipe lalu migrate ulang).
        Schema::dropIfExists('data_bps.bps_domains');

        // Data lama sengaja tidak dipindahkan — aplikasi baru dipakai, isinya boleh hilang.
        Schema::dropIfExists('bps_domains');

        Schema::create('data_bps.bps_domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain_id')->unique();
            $table->string('domain_name');
            $table->string('domain_url')->nullable();
            $table->string('type');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_bps.bps_domains');

        Schema::create('bps_domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain_id')->unique();
            $table->string('domain_name');
            $table->string('domain_url')->nullable();
            $table->string('type');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }
};
