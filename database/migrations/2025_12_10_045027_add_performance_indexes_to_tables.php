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
        // Add indexes to venues table for performance
        Schema::table('venues', function (Blueprint $table) {
            $table->index(['city_id', 'is_active']); // For filtering venues by city and active status
            $table->index(['is_active', 'rating']); // For filtering active venues by rating
            $table->index(['latitude', 'longitude']); // For geospatial queries
            $table->index('normalised_name'); // For search functionality
            $table->index('type_id'); // For filtering by venue type
            $table->index('created_at'); // For ordering by creation date
        });

        // Add indexes to locations table for performance
        Schema::table('locations', function (Blueprint $table) {
            $table->index(['venue_id', 'is_active']); // For filtering locations by venue and active status
            $table->index(['city_id', 'is_active']); // For filtering locations by city and active status
            $table->index(['latitude', 'longitude']); // For geospatial queries
            $table->index('postal_code'); // For postal code searches
        });

        // Add indexes to treatments table for performance
        Schema::table('treatments', function (Blueprint $table) {
            $table->index(['venue_id', 'is_active']); // For filtering treatments by venue and active status
            $table->index(['category_id', 'is_active']); // For filtering by category
            $table->index(['price', 'is_active']); // For price-based filtering
            $table->index(['min_price', 'max_price']); // For price range queries
            $table->index('external_id'); // For external system lookups
            $table->index('created_at'); // For ordering by creation date
        });

        // Add indexes to ratings table for performance
        Schema::table('ratings', function (Blueprint $table) {
            $table->index(['venue_id', 'is_verified']); // For filtering verified ratings by venue
            $table->index(['venue_id', 'rating']); // For filtering ratings by venue and score
            $table->index('created_at'); // For ordering by creation date
            $table->index('reviewer_email'); // For finding ratings by reviewer
        });

        // Add indexes to cities table for performance
        Schema::table('cities', function (Blueprint $table) {
            $table->index(['country_id', 'is_main_city']); // For filtering cities by country and main city status
            $table->index('normalised_name'); // For search functionality
            $table->index(['latitude', 'longitude']); // For geospatial queries
            $table->index('type'); // For filtering by city type
        });

        // Add indexes to images table for performance
        Schema::table('images', function (Blueprint $table) {
            $table->index(['imageable_type', 'imageable_id', 'is_primary']); // For finding primary images
            $table->index(['imageable_type', 'imageable_id', 'sort_order']); // For ordered image queries
            $table->index('external_id'); // For external system lookups
        });

        // Add indexes to opening_hours table for performance
        Schema::table('opening_hours', function (Blueprint $table) {
            $table->index(['venue_id', 'day_of_week']); // For finding opening hours by venue and day
            $table->index(['venue_id', 'is_open']); // For filtering open venues
        });

        // Add indexes to countries table for performance
        Schema::table('countries', function (Blueprint $table) {
            $table->index('name'); // For country name searches
        });

        // Add indexes to users table for performance
        Schema::table('users', function (Blueprint $table) {
            $table->index('created_at'); // For ordering users by registration date
            $table->index('email_verified_at'); // For filtering verified users
        });

        // Add indexes to procedures table for performance
        Schema::table('procedures', function (Blueprint $table) {
            $table->index(['category', 'is_active']); // For filtering procedures by category and active status
            $table->index('name'); // For procedure name searches
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from venues table
        Schema::table('venues', function (Blueprint $table) {
            $table->dropIndex(['city_id', 'is_active']);
            $table->dropIndex(['is_active', 'rating']);
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex(['normalised_name']);
            $table->dropIndex(['type_id']);
            $table->dropIndex(['created_at']);
        });

        // Remove indexes from locations table
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex(['venue_id', 'is_active']);
            $table->dropIndex(['city_id', 'is_active']);
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex(['postal_code']);
        });

        // Remove indexes from treatments table
        Schema::table('treatments', function (Blueprint $table) {
            $table->dropIndex(['venue_id', 'is_active']);
            $table->dropIndex(['category_id', 'is_active']);
            $table->dropIndex(['price', 'is_active']);
            $table->dropIndex(['min_price', 'max_price']);
            $table->dropIndex(['external_id']);
            $table->dropIndex(['created_at']);
        });

        // Remove indexes from ratings table
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropIndex(['venue_id', 'is_verified']);
            $table->dropIndex(['venue_id', 'rating']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['reviewer_email']);
        });

        // Remove indexes from cities table
        Schema::table('cities', function (Blueprint $table) {
            $table->dropIndex(['country_id', 'is_main_city']);
            $table->dropIndex(['normalised_name']);
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex(['type']);
        });

        // Remove indexes from images table
        Schema::table('images', function (Blueprint $table) {
            $table->dropIndex(['imageable_type', 'imageable_id', 'is_primary']);
            $table->dropIndex(['imageable_type', 'imageable_id', 'sort_order']);
            $table->dropIndex(['external_id']);
        });

        // Remove indexes from opening_hours table
        Schema::table('opening_hours', function (Blueprint $table) {
            $table->dropIndex(['venue_id', 'day_of_week']);
            $table->dropIndex(['venue_id', 'is_open']);
        });

        // Remove indexes from countries table
        Schema::table('countries', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        // Remove indexes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['email_verified_at']);
        });

        // Remove indexes from procedures table
        Schema::table('procedures', function (Blueprint $table) {
            $table->dropIndex(['category', 'is_active']);
            $table->dropIndex(['name']);
        });
    }
};
