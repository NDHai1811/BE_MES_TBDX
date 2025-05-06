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
        Schema::create('locator_mlt', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('capacity')->default(0);
            $table->string('warehouse_mlt_id');
            $table->timestamps();

            $table->foreign('warehouse_mlt_id')->references('id')->on('warehouse_mlt')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locator_mlt');
    }
}; 