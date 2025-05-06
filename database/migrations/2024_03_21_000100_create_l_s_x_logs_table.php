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
        Schema::create('l_s_x_logs', function (Blueprint $table) {
            $table->id();
            $table->string('lo_sx');
            $table->string('machine_id');
            $table->string('mapping')->nullable();
            $table->json('params')->nullable();
            $table->integer('thu_tu_uu_tien')->nullable();
            $table->json('info')->nullable();
            $table->dateTime('map_time')->nullable();
            $table->timestamps();

            $table->foreign('lo_sx')->references('id')->on('lo_sx')->onDelete('cascade');
            $table->foreign('machine_id')->references('id')->on('machines')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('l_s_x_logs');
    }
}; 