<?php

// database/migrations/2026_09_06_130201_create_bps_variables_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_bps.bps_variables', function (Blueprint $table) {
            $table->id();
            $table->string('domain_id', 4);
            $table->integer('var_id');
            $table->string('title');
            $table->integer('sub_id')->nullable();
            $table->string('sub_name')->nullable();
            $table->text('def')->nullable();
            $table->text('notes')->nullable();
            $table->integer('vertical')->nullable();
            $table->string('unit')->nullable();
            $table->integer('graph_id')->nullable();
            $table->string('graph_name')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'var_id']);
            $table->foreign('domain_id')->references('domain_id')->on('data_bps.bps_domains')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_bps.bps_variables');
    }
};
