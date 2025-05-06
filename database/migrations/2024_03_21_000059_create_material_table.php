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
        Schema::create('material', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('ma_vat_tu');
            $table->string('ma_cuon_ncc');
            $table->decimal('so_kg', 10, 2);
            $table->decimal('so_kg_dau', 10, 2)->nullable();
            $table->string('loai_giay');
            $table->decimal('kho_giay', 10, 2);
            $table->decimal('dinh_luong', 10, 2);
            $table->string('parent_id')->nullable();
            $table->decimal('so_m_toi', 10, 2)->nullable();
            $table->string('fsc')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material');
    }
}; 