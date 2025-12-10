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
        Schema::create('api_call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint', 500)->index();
            $table->string('city_slug')->nullable()->index();
            $table->integer('status_code')->nullable()->index();
            $table->integer('response_time')->nullable(); // milliseconds
            $table->integer('data_points_extracted')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('called_at');
            $table->timestamps();

            // Add composite indexes for efficient querying
            $table->index(['endpoint', 'city_slug']);
            $table->index(['city_slug', 'called_at']);
            $table->index(['status_code', 'called_at']);
            $table->index('called_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_call_logs');
    }
};
