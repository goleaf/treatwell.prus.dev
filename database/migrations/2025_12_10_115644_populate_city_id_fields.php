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
        // Populate city_id for services based on venue relationship
        DB::statement('
            UPDATE services 
            SET city_id = (
                SELECT venues.city_id 
                FROM venues 
                WHERE venues.id = services.venue_id
            )
            WHERE venue_id IS NOT NULL
        ');

        // Populate city_id for images based on venue relationship
        DB::statement('
            UPDATE images 
            SET city_id = (
                SELECT venues.city_id 
                FROM venues 
                WHERE venues.id = images.venue_id
            )
            WHERE venue_id IS NOT NULL
        ');

        // Populate city_id for ratings based on venue relationship
        DB::statement('
            UPDATE ratings 
            SET city_id = (
                SELECT venues.city_id 
                FROM venues 
                WHERE venues.id = ratings.venue_id
            )
            WHERE venue_id IS NOT NULL
        ');

        // Populate city_id for opening_hours based on venue relationship
        DB::statement('
            UPDATE opening_hours 
            SET city_id = (
                SELECT venues.city_id 
                FROM venues 
                WHERE venues.id = opening_hours.venue_id
            )
            WHERE venue_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset city_id fields to null
        DB::table('services')->update(['city_id' => null]);
        DB::table('images')->update(['city_id' => null]);
        DB::table('ratings')->update(['city_id' => null]);
        DB::table('opening_hours')->update(['city_id' => null]);
    }
};