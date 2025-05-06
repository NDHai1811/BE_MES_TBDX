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
        Schema::create('khuon_link', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('customer_id');
            $table->string('dai')->nullable();
            $table->string('rong')->nullable();
            $table->string('cao')->nullable();
            $table->string('kich_thuoc')->nullable();
            $table->string('phan_loai_1');
            $table->string('buyer_id');
            $table->string('kho_khuon')->nullable();
            $table->string('dai_khuon')->nullable();
            $table->string('so_con')->nullable();
            $table->string('so_manh_ghep')->nullable();
            $table->string('khuon_id');
            $table->string('sl_khuon')->nullable();
            $table->string('machine_id')->nullable();
            $table->text('buyer_note')->nullable();
            $table->text('note')->nullable();
            $table->string('layout')->nullable();
            $table->string('supplier')->nullable();
            $table->date('ngay_dat_khuon')->nullable();
            $table->string('pad_xe_ranh')->nullable();
            $table->string('designer_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('khuon_link');
    }
}; 