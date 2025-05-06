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
        Schema::create('lsx_pallet', function (Blueprint $table) {
            $table->string('lo_sx');
            $table->integer('so_luong');
            $table->string('pallet_id');
            $table->string('mdh')->nullable();
            $table->string('mql')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('order_id')->nullable();
            $table->integer('remain_quantity')->nullable();
            $table->timestamps();

            $table->primary(['lo_sx', 'pallet_id']);
            $table->foreign('lo_sx')->references('id')->on('lo_sx')->onDelete('cascade');
            $table->foreign('pallet_id')->references('id')->on('pallets')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lsx_pallet');
    }
}; 