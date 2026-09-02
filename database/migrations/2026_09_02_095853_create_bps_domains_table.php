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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bps_domains');
    }
};
