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
        Schema::create('data_access_clients.api_request_logs', function (Blueprint $table) {
            $table->id();
            // Null kalau tokennya tidak dikenal.
            $table->unsignedBigInteger('api_client_id')->nullable();
            $table->string('method', 10)->default('GET');
            $table->string('path');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->integer('http_status')->nullable();
            $table->integer('duration_ms');
            $table->integer('response_bytes');
            $table->jsonb('request_header')->nullable();
            $table->jsonb('request_parameters')->nullable();
            $table->jsonb('response_header')->nullable();
            $table->jsonb('response_body')->nullable();
            $table->timestamp('created_at');

            $table->foreign('api_client_id')
                ->references('id')
                ->on('data_access_clients.api_clients')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_access_clients.api_request_logs');
    }
};
