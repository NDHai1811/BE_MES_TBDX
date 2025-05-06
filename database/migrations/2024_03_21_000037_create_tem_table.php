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
        Schema::create('tem', function (Blueprint $table) {
            $table->id();
            $table->integer('ordering');
            $table->string('lo_sx');
            $table->string('khach_hang');
            $table->string('mdh');
            $table->string('order_id');
            $table->string('mql');
            $table->string('quy_cach');
            $table->integer('so_luong');
            $table->string('gmo')->nullable();
            $table->string('po')->nullable();
            $table->string('style')->nullable();
            $table->string('style_no')->nullable();
            $table->string('color')->nullable();
            $table->text('note')->nullable();
            $table->string('machine_id');
            $table->string('nhan_vien_sx');
            $table->integer('sl_tem');
            $table->boolean('display')->default(true);
            $table->string('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tem');
    }
}; 