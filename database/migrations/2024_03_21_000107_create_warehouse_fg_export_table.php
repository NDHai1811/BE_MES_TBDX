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
        Schema::create('warehouse_fg_export', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id');
            $table->date('ngay_xuat');
            $table->string('mdh');
            $table->string('mql');
            $table->integer('so_luong');
            $table->string('tai_xe');
            $table->string('so_xe');
            $table->string('nguoi_xuat');
            $table->string('order_id');
            $table->string('delivery_note_id');
            $table->string('created_by');
            $table->string('xuong_giao');
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('delivery_note_id')->references('id')->on('delivery_notes')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_fg_export');
    }
}; 