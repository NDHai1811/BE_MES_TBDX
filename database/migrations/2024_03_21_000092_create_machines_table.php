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
        Schema::create('machines', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('parent_id')->nullable();
            $table->boolean('is_iot')->default(false);
            $table->string('line_id');
            $table->string('kieu_loai')->nullable();
            $table->string('device_id')->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('machines')->onDelete('cascade');
            $table->foreign('line_id')->references('id')->on('lines')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
}; 