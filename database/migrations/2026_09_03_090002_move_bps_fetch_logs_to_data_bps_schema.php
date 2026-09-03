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
        // Data lama sengaja tidak dipindahkan — aplikasi baru dipakai, isinya boleh hilang.
        Schema::dropIfExists('bps_fetch_logs');

        Schema::create('data_bps.bps_fetch_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('endpoint');
            $table->jsonb('params');
            $table->string('status');
            $table->integer('http_status')->nullable();
            $table->integer('records_count')->nullable();
            $table->integer('duration_ms');
            $table->text('error')->nullable();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_bps.bps_fetch_logs');

        Schema::create('bps_fetch_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('endpoint');
            $table->jsonb('params');
            $table->string('status');
            $table->integer('http_status')->nullable();
            $table->integer('records_count')->nullable();
            $table->integer('duration_ms');
            $table->text('error')->nullable();
            $table->timestamp('created_at');
        });
    }
};
