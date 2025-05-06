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
        Schema::create('mapping', function (Blueprint $table) {
            $table->id();
            $table->string('machine_id');
            $table->string('lo_sx');
            $table->string('position')->nullable();
            $table->string('user_id')->nullable();
            $table->json('info')->nullable();
            $table->dateTime('map_time')->nullable();
            $table->timestamps();

            $table->foreign('machine_id')->references('id')->on('machines')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mapping');
    }
}; 