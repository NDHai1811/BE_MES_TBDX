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
        Schema::create('info_cong_doan', function (Blueprint $table) {
            $table->id();
            $table->string('lot_id');
            $table->string('machine_id');
            $table->dateTime('thoi_gian_bat_dau')->nullable();
            $table->dateTime('thoi_gian_bam_may')->nullable();
            $table->dateTime('thoi_gian_ket_thuc')->nullable();
            $table->integer('sl_dau_vao_chay_thu')->nullable();
            $table->integer('sl_dau_ra_chay_thu')->nullable();
            $table->integer('sl_dau_vao_hang_loat')->nullable();
            $table->integer('sl_dau_ra_hang_loat')->nullable();
            $table->integer('sl_ng_sx')->nullable();
            $table->integer('sl_ng_qc')->nullable();
            $table->integer('sl_loi')->nullable();
            $table->string('phan_dinh')->nullable();
            $table->string('loi_tinh_nang')->nullable();
            $table->string('loi_ngoai_quan')->nullable();
            $table->string('dinh_muc')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('lo_sx');
            $table->string('nhan_vien_sx')->nullable();
            $table->string('status')->nullable();
            $table->string('step')->nullable();
            $table->integer('thu_tu_uu_tien')->nullable();
            $table->date('ngay_sx')->nullable();
            $table->string('plan_id')->nullable();
            $table->string('order_id')->nullable();
            $table->string('so_ra')->nullable();
            $table->string('so_dao')->nullable();
            $table->string('so_du')->nullable()->default(0);
            $table->timestamps();

            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('cascade');
            $table->foreign('machine_id')->references('id')->on('machines')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('info_cong_doan')->onDelete('cascade');
            $table->foreign('nhan_vien_sx')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('production_plans')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('info_cong_doan');
    }
}; 