<?php
// database/migrations/2026_09_06_130202_create_bps_vertical_variables_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_bps.bps_vertical_variables', function (Blueprint $table) {
            $table->id();
            $table->string('domain_id', 4);
            $table->integer('var_id')->nullable();
            $table->integer('vervar_id');
            $table->string('vervar');
            $table->integer('item_ver_id')->nullable();
            $table->integer('group_ver_id')->nullable();
            $table->string('name_group_ver_id')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'vervar_id']);
            $table->foreign('domain_id')->references('domain_id')->on('data_bps.bps_domains')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_bps.bps_vertical_variables');
    }
};
