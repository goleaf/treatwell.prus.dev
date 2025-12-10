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
        // The images table has a venue_id column that was added later but doesn't have a proper foreign key constraint
        // This column seems to be used alongside the morphable relationship for direct venue image queries
        Schema::table('images', function (Blueprint $table) {
            // Check if the foreign key constraint doesn't already exist
            $foreignKeys = collect(DB::select('PRAGMA foreign_key_list(images)'))
                ->pluck('from')->toArray();

            if (! in_array('venue_id', $foreignKeys)) {
                // Add foreign key constraint for venue_id with SET NULL on delete
                // Using SET NULL because images can exist without a direct venue relationship (via morphable)
                $table->foreign('venue_id')->references('id')->on('venues')->onDelete('set null');
            }
        });

        // Ensure all pivot tables have proper unique constraints and foreign keys
        // The city_venue table should have proper constraints (already exists, but let's verify)
        Schema::table('city_venue', function (Blueprint $table) {
            // Check if unique constraint exists
            $indexes = collect(DB::select('PRAGMA index_list(city_venue)'))
                ->pluck('name')->toArray();

            if (! in_array('city_venue_city_id_venue_id_unique', $indexes)) {
                $table->unique(['city_id', 'venue_id']);
            }
        });

        // The procedure_venue table should have proper constraints (already exists, but let's verify)
        Schema::table('procedure_venue', function (Blueprint $table) {
            // Check if unique constraint exists
            $indexes = collect(DB::select('PRAGMA index_list(procedure_venue)'))
                ->pluck('name')->toArray();

            if (! in_array('procedure_venue_procedure_id_venue_id_unique', $indexes)) {
                $table->unique(['procedure_id', 'venue_id']);
            }
        });

        // The city_procedure table should have proper constraints (already exists, but let's verify)
        Schema::table('city_procedure', function (Blueprint $table) {
            // Check if unique constraint exists
            $indexes = collect(DB::select('PRAGMA index_list(city_procedure)'))
                ->pluck('name')->toArray();

            if (! in_array('city_procedure_city_id_procedure_id_unique', $indexes)) {
                $table->unique(['city_id', 'procedure_id']);
            }
        });

        // Add missing indexes for foreign key columns to improve performance
        Schema::table('treatments', function (Blueprint $table) {
            $indexes = collect(DB::select('PRAGMA index_list(treatments)'))
                ->pluck('name')->toArray();

            // Add index on venue_id if it doesn't exist (for better join performance)
            if (! in_array('treatments_venue_id_index', $indexes)) {
                $table->index('venue_id');
            }
        });

        Schema::table('locations', function (Blueprint $table) {
            $indexes = collect(DB::select('PRAGMA index_list(locations)'))
                ->pluck('name')->toArray();

            // Add indexes on foreign key columns if they don't exist
            if (! in_array('locations_venue_id_index', $indexes)) {
                $table->index('venue_id');
            }
            if (! in_array('locations_city_id_index', $indexes)) {
                $table->index('city_id');
            }
        });

        Schema::table('ratings', function (Blueprint $table) {
            $indexes = collect(DB::select('PRAGMA index_list(ratings)'))
                ->pluck('name')->toArray();

            // Add index on venue_id if it doesn't exist
            if (! in_array('ratings_venue_id_index', $indexes)) {
                $table->index('venue_id');
            }
        });

        Schema::table('opening_hours', function (Blueprint $table) {
            $indexes = collect(DB::select('PRAGMA index_list(opening_hours)'))
                ->pluck('name')->toArray();

            // Add index on venue_id if it doesn't exist
            if (! in_array('opening_hours_venue_id_index', $indexes)) {
                $table->index('venue_id');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            $indexes = collect(DB::select('PRAGMA index_list(services)'))
                ->pluck('name')->toArray();

            // Add index on venue_id if it doesn't exist (might already exist from creation)
            if (! in_array('services_venue_id_index', $indexes)) {
                $table->index('venue_id');
            }
        });

        // Add indexes on morphable columns for better polymorphic query performance
        Schema::table('images', function (Blueprint $table) {
            $indexes = collect(DB::select('PRAGMA index_list(images)'))
                ->pluck('name')->toArray();

            // Add composite index on morphable columns if it doesn't exist
            if (! in_array('images_imageable_type_imageable_id_index', $indexes)) {
                $table->index(['imageable_type', 'imageable_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the foreign key constraint from images table
        Schema::table('images', function (Blueprint $table) {
            $table->dropForeign(['venue_id']);
        });

        // Remove unique constraints from pivot tables
        Schema::table('city_venue', function (Blueprint $table) {
            $table->dropUnique(['city_id', 'venue_id']);
        });

        Schema::table('procedure_venue', function (Blueprint $table) {
            $table->dropUnique(['procedure_id', 'venue_id']);
        });

        Schema::table('city_procedure', function (Blueprint $table) {
            $table->dropUnique(['city_id', 'procedure_id']);
        });

        // Remove added indexes
        Schema::table('treatments', function (Blueprint $table) {
            $table->dropIndex(['venue_id']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex(['venue_id']);
            $table->dropIndex(['city_id']);
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->dropIndex(['venue_id']);
        });

        Schema::table('opening_hours', function (Blueprint $table) {
            $table->dropIndex(['venue_id']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['venue_id']);
        });

        Schema::table('images', function (Blueprint $table) {
            $table->dropIndex(['imageable_type', 'imageable_id']);
        });
    }
};
