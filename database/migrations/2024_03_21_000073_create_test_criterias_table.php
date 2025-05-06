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
        Schema::create('test_criterias', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('line_id');
            $table->string('tieu_chuan')->nullable();
            $table->string('nguyen_tac')->nullable();
            $table->string('frequency')->nullable();
            $table->string('chi_tieu')->nullable();
            $table->string('ghi_chu')->nullable();
            $table->string('hang_muc')->nullable();
            $table->string('phan_dinh')->nullable();
            $table->timestamps();

            $table->foreign('line_id')->references('id')->on('lines')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_criterias');
    }
}; 