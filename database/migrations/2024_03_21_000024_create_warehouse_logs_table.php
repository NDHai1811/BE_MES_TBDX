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
        Schema::create('warehouse_logs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('locator_id');
            $table->string('pallet_id');
            $table->string('lo_sx');
            $table->integer('so_luong');
            $table->tinyInteger('type');
            $table->string('created_by');
            $table->string('order_id')->nullable();
            $table->string('delivery_note_id')->nullable();
            $table->boolean('nhap_du')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_logs');
    }
}; 