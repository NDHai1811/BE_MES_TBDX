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
        Schema::create('warehouse_mlt_logs', function (Blueprint $table) {
            $table->id();
            $table->string('locator_id');
            $table->string('material_id');
            $table->decimal('so_kg_nhap', 10, 2)->nullable();
            $table->decimal('so_kg_xuat', 10, 2)->nullable();
            $table->dateTime('tg_nhap')->nullable();
            $table->dateTime('tg_xuat')->nullable();
            $table->string('position_id')->nullable();
            $table->string('position_name')->nullable();
            $table->string('importer_id')->nullable();
            $table->string('exporter_id')->nullable();
            $table->timestamps();

            $table->foreign('locator_id')->references('id')->on('locator_mlt')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('material')->onDelete('cascade');
            $table->foreign('importer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('exporter_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_mlt_logs');
    }
}; 