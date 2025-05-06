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
        Schema::create('lsx_pallet_clone', function (Blueprint $table) {
            $table->id();
            $table->string('lo_sx');
            $table->integer('so_luong');
            $table->string('pallet_id');
            $table->string('mdh');
            $table->string('mql');
            $table->string('customer_id');
            $table->string('order_id');
            $table->integer('remain_quantity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lsx_pallet_clone');
    }
}; 