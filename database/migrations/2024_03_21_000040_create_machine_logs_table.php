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
        Schema::create('machine_logs', function (Blueprint $table) {
            $table->id();
            $table->string('machine_id');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->string('error_machine_id')->nullable();
            $table->string('user_id');
            $table->string('lo_sx');
            $table->integer('handle_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_logs');
    }
}; 