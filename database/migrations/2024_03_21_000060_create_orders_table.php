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
        Schema::create('orders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->date('ngay_dat_hang');
            $table->string('customer_id');
            $table->string('nguoi_dat_hang');
            $table->string('mdh');
            $table->string('order');
            $table->string('mql');
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('dai', 10, 2)->nullable();
            $table->decimal('rong', 10, 2)->nullable();
            $table->decimal('cao', 10, 2)->nullable();
            $table->integer('sl')->nullable();
            $table->integer('slg')->nullable();
            $table->integer('slt')->nullable();
            $table->string('tmo')->nullable();
            $table->string('po')->nullable();
            $table->string('style')->nullable();
            $table->string('style_no')->nullable();
            $table->string('color')->nullable();
            $table->string('item')->nullable();
            $table->string('rm')->nullable();
            $table->string('size')->nullable();
            $table->string('note_1')->nullable();
            $table->date('han_giao')->nullable();
            $table->string('note_2')->nullable();
            $table->string('note_3')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('into_money', 10, 2)->nullable();
            $table->string('layout_type')->nullable();
            $table->string('dot')->nullable();
            $table->string('kho')->nullable();
            $table->string('layout_id')->nullable();
            $table->string('buyer_id')->nullable();
            $table->string('so_dao')->nullable();
            $table->decimal('dai_tam', 10, 2)->nullable();
            $table->decimal('so_met_toi', 10, 2)->nullable();
            $table->integer('tg_doi_model')->nullable();
            $table->integer('toc_do')->nullable();
            $table->integer('so_ra')->nullable();
            $table->string('kich_thuoc')->nullable();
            $table->string('unit')->nullable();
            $table->string('xuong_giao')->nullable();
            $table->string('kich_thuoc_chuan')->nullable();
            $table->string('phan_loai_1')->nullable();
            $table->string('phan_loai_2')->nullable();
            $table->string('kho_tong')->nullable();
            $table->string('quy_cach_drc')->nullable();
            $table->date('han_giao_sx')->nullable();
            $table->boolean('is_plan')->default(false);
            $table->string('short_name')->nullable();
            $table->string('xuat_tai_kho')->nullable();
            $table->string('khuon_id')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
}; 