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
        Schema::create('production_plans', function (Blueprint $table) {
            $table->id();
            $table->string('lo_sx');
            $table->string('machine_id');
            $table->integer('thu_tu_uu_tien')->nullable();
            $table->date('ngay_dat_hang');
            $table->integer('toc_do')->nullable();
            $table->integer('tg_doi_model')->nullable();
            $table->integer('sl_kh')->nullable();
            $table->decimal('so_m_toi', 10, 2)->nullable();
            $table->string('ghi_chu')->nullable();
            $table->dateTime('thoi_gian_bat_dau')->nullable();
            $table->dateTime('thoi_gian_ket_thuc')->nullable();
            $table->string('file')->nullable();
            $table->string('order_id');
            $table->date('ngay_sx');
            $table->integer('ordering')->nullable();
            $table->string('created_by')->nullable();
            $table->integer('loss_quantity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_plans');
    }
}; 