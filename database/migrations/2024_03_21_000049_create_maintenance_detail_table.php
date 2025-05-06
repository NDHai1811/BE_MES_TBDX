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
        Schema::create('maintenance_detail', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->date('start_date');
            $table->string('type_repeat');
            $table->string('period');
            $table->string('type_criteria');
            $table->string('maintenance_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_detail');
    }
}; 