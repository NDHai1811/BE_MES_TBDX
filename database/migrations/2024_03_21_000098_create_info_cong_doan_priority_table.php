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
        Schema::create('info_cong_doan_priority', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('info_cong_doan_id');
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->foreign('info_cong_doan_id')->references('id')->on('info_cong_doan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('info_cong_doan_priority');
    }
}; 