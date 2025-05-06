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
        Schema::create('machine_parameter_logs', function (Blueprint $table) {
            $table->id();
            $table->string('machine_id');
            $table->string('lo_sx');
            $table->string('user_id');
            $table->json('info');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_parameter_logs');
    }
}; 