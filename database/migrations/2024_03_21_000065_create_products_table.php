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
        Schema::create('products', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('material_id');
            $table->integer('dinh_muc');
            $table->string('customer_id');
            $table->string('ver');
            $table->string('his');
            $table->decimal('nhiet_do_phong', 10, 2);
            $table->decimal('do_am_phong', 10, 2);
            $table->decimal('do_am_giay', 10, 2);
            $table->integer('thoi_gian_bao_on');
            $table->decimal('chieu_dai_thung', 10, 2);
            $table->decimal('chieu_rong_thung', 10, 2);
            $table->decimal('chieu_cao_thung', 10, 2);
            $table->decimal('the_tich_thung', 10, 2);
            $table->integer('dinh_muc_thung');
            $table->decimal('u_nhiet_do_phong', 10, 2);
            $table->decimal('u_do_am_phong', 10, 2);
            $table->decimal('u_do_am_giay', 10, 2);
            $table->integer('u_thoi_gian_u');
            $table->integer('number_of_bin');
            $table->decimal('kt_kho_dai', 10, 2)->nullable();
            $table->decimal('kt_kho_rong', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
}; 