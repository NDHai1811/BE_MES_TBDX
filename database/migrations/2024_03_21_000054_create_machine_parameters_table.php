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
        Schema::create('machine_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('machine_id');
            $table->string('name');
            $table->string('parameter_id');
            $table->boolean('is_if')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_parameters');
    }
}; 