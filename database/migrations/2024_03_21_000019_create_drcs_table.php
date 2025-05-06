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
        Schema::create('drc', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('ten_quy_cach');
            $table->string('ct_dai');
            $table->string('ct_rong');
            $table->string('ct_cao');
            $table->text('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drc');
    }
}; 