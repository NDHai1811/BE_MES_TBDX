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
        Schema::create('warehouse_mlt_import', function (Blueprint $table) {
            $table->id();
            $table->string('material_id');
            $table->string('ma_vat_tu');
            $table->string('ma_cuon_ncc');
            $table->decimal('so_kg', 10, 2);
            $table->string('loai_giay');
            $table->decimal('kho_giay', 10, 2);
            $table->decimal('dinh_luong', 10, 2);
            $table->string('iqc')->nullable();
            $table->string('fsc')->nullable();
            $table->json('log')->nullable();
            $table->string('goods_receipt_note_id')->nullable();
            $table->timestamps();

            $table->foreign('material_id')->references('id')->on('material')->onDelete('cascade');
            $table->foreign('loai_giay')->references('id')->on('suppliers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_mlt_import');
    }
}; 