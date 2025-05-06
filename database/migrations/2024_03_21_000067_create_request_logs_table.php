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
        Schema::create('request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->string('uri');
            $table->string('method');
            $table->string('controller_action');
            $table->string('middleware');
            $table->json('payload')->nullable();
            $table->integer('response_status');
            $table->integer('duration');
            $table->integer('memory');
            $table->string('requested_by')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
}; 