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
        Schema::create('locator_fg_map', function (Blueprint $table) {
            $table->string('locator_id');
            $table->string('pallet_id');

            $table->primary(['locator_id', 'pallet_id']);
            $table->foreign('locator_id')->references('id')->on('locator_fg')->onDelete('cascade');
            $table->foreign('pallet_id')->references('id')->on('pallet')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locator_fg_map');
    }
}; 