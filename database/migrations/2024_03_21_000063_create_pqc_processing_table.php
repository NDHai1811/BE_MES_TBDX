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
        Schema::create('pqc_processing', function (Blueprint $table) {
            $table->id();
            $table->integer('number_of_pqc');
            $table->date('date');
            $table->integer('number_of_ok_pqc');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pqc_processing');
    }
}; 