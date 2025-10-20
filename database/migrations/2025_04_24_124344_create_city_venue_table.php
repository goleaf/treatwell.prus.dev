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
        if (! Schema::hasTable('city_venue')) {
            Schema::create('city_venue', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('city_id');
                $table->unsignedBigInteger('venue_id');
                $table->timestamps();

                // Add unique constraint to prevent duplicate entries
                $table->unique(['city_id', 'venue_id']);

                // Add foreign key constraints
                $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
                $table->foreign('venue_id')->references('id')->on('venues')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('city_venue');
    }
};
