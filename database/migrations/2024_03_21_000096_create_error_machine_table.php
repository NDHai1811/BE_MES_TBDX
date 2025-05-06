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
        Schema::create('error_machine', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('code');
            $table->string('ten_su_co');
            $table->text('nguyen_nhan')->nullable();
            $table->text('cach_xu_ly')->nullable();
            $table->string('line_id');
            $table->timestamps();

            $table->foreign('line_id')->references('id')->on('lines')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_machine');
    }
}; 