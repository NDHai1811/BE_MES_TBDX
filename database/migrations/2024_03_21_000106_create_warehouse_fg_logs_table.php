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
        Schema::create('warehouse_fg_logs', function (Blueprint $table) {
            $table->id();
            $table->string('locator_id');
            $table->string('pallet_id');
            $table->string('lo_sx');
            $table->integer('so_luong');
            $table->integer('type');
            $table->string('created_by');
            $table->string('order_id')->nullable();
            $table->string('delivery_note_id')->nullable();
            $table->boolean('nhap_du')->default(false);
            $table->timestamps();

            $table->foreign('locator_id')->references('id')->on('locator_fg')->onDelete('cascade');
            $table->foreign('pallet_id')->references('id')->on('pallet')->onDelete('cascade');
            $table->foreign('lo_sx')->references('id')->on('lo_sx')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('delivery_note_id')->references('id')->on('delivery_notes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_fg_logs');
    }
}; 