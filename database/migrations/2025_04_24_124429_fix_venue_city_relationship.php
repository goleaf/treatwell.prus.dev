<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop and recreate the city_venue table to ensure it has the correct structure
        Schema::dropIfExists('city_venue');
        
        Schema::create('city_venue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('venue_id');
            $table->timestamps();
            
            // Add unique constraint to prevent duplicate entries
            $table->unique(['city_id', 'venue_id']);
        });
        
        // Clean up any reference to venues_old which might be causing issues
        try {
            Schema::dropIfExists('venues_old');
        } catch (\Exception $e) {
            // Table might not exist, so just ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to implement down migration as this is a fix
    }
};
