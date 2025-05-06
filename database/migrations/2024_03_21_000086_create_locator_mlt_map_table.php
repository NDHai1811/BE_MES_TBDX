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
        Schema::create('locator_mlt_map', function (Blueprint $table) {
            $table->string('locator_mlt_id');
            $table->string('material_id');
            $table->timestamps();

            $table->primary('material_id');
            $table->foreign('locator_mlt_id')->references('id')->on('locator_mlt')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('material')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locator_mlt_map');
    }
}; 