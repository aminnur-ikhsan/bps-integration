<?php

// database/migrations/2026_09_11_154147_create_bps_connection_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_bps.bps_connection_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 10)->default('GET');
            $table->string('base_url');
            $table->string('path');
            $table->integer('http_status')->nullable();
            $table->integer('dns_ms');
            $table->integer('connect_ms');
            $table->integer('ttfb_ms');
            $table->integer('total_ms');
            $table->integer('response_bytes');
            $table->jsonb('request_header')->nullable();
            $table->jsonb('request_parameters')->nullable();
            $table->jsonb('response_header')->nullable();
            $table->jsonb('response_body')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_bps.bps_connection_logs');
    }
};
