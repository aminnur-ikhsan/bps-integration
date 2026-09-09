<?php

// database/migrations/2026_09_06_130203_create_bps_derived_variables_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_bps.bps_derived_variables', function (Blueprint $table) {
            $table->id();
            $table->string('domain_id', 4);
            $table->integer('var_id')->nullable();
            $table->integer('turvar_id');
            $table->string('turvar');
            $table->integer('group_turvar_id')->nullable();
            $table->string('name_group_turvar')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'turvar_id']);
            $table->foreign('domain_id')->references('domain_id')->on('data_bps.bps_domains')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_bps.bps_derived_variables');
    }
};
