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
        // Add additional indexes to pivot tables for better performance
        Schema::table('city_venue', function (Blueprint $table) {
            $table->index('city_id'); // For finding venues by city
            $table->index('venue_id'); // For finding cities by venue
            $table->index('created_at'); // For ordering by creation date
        });

        Schema::table('procedure_venue', function (Blueprint $table) {
            $table->index('procedure_id'); // For finding venues by procedure
            $table->index('venue_id'); // For finding procedures by venue
            $table->index('created_at'); // For ordering by creation date
        });

        Schema::table('city_procedure', function (Blueprint $table) {
            $table->index('city_id'); // For finding procedures by city
            $table->index('procedure_id'); // For finding cities by procedure
            $table->index('created_at'); // For ordering by creation date
        });

        // Add additional indexes to services table for better performance
        Schema::table('services', function (Blueprint $table) {
            $table->index(['price', 'is_active']); // For price-based filtering with active status
            $table->index(['min_price', 'max_price', 'is_active']); // For price range queries with active status
            $table->index(['duration', 'is_active']); // For duration-based filtering
            $table->index(['sort_order', 'is_active']); // For ordered listings
            $table->index('created_at'); // For ordering by creation date
        });

        // Add additional indexes to jobs table for better queue performance
        Schema::table('jobs', function (Blueprint $table) {
            $table->index(['available_at', 'queue']); // For finding available jobs by queue
            $table->index(['reserved_at', 'queue']); // For finding reserved jobs by queue
            $table->index('created_at'); // For ordering by creation date
        });

        // Add index to cache table for expiration cleanup
        Schema::table('cache', function (Blueprint $table) {
            $table->index('expiration'); // For cache cleanup operations
        });

        Schema::table('cache_locks', function (Blueprint $table) {
            $table->index('expiration'); // For lock cleanup operations
            $table->index('owner'); // For finding locks by owner
        });

        // Add additional composite indexes for common query patterns
        Schema::table('venues', function (Blueprint $table) {
            $table->index(['is_active', 'city_id', 'rating']); // For filtered venue listings with rating sort
            $table->index(['is_active', 'type_id', 'rating']); // For venue type filtering with rating
        });

        Schema::table('treatments', function (Blueprint $table) {
            $table->index(['is_active', 'venue_id', 'category']); // For venue treatment listings by category
            $table->index(['is_active', 'category', 'price']); // For category filtering with price sort
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->index(['is_active', 'venue_id', 'city_id']); // For venue location queries
        });

        Schema::table('images', function (Blueprint $table) {
            $table->index(['imageable_type', 'imageable_id', 'is_primary', 'sort_order']); // For ordered image queries with primary flag
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->index(['venue_id', 'is_verified', 'created_at']); // For verified ratings with date ordering
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove additional indexes from pivot tables
        Schema::table('city_venue', function (Blueprint $table) {
            $table->dropIndex(['city_id']);
            $table->dropIndex(['venue_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('procedure_venue', function (Blueprint $table) {
            $table->dropIndex(['procedure_id']);
            $table->dropIndex(['venue_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('city_procedure', function (Blueprint $table) {
            $table->dropIndex(['city_id']);
            $table->dropIndex(['procedure_id']);
            $table->dropIndex(['created_at']);
        });

        // Remove additional indexes from services table
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['price', 'is_active']);
            $table->dropIndex(['min_price', 'max_price', 'is_active']);
            $table->dropIndex(['duration', 'is_active']);
            $table->dropIndex(['sort_order', 'is_active']);
            $table->dropIndex(['created_at']);
        });

        // Remove additional indexes from jobs table
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['available_at', 'queue']);
            $table->dropIndex(['reserved_at', 'queue']);
            $table->dropIndex(['created_at']);
        });

        // Remove indexes from cache tables
        Schema::table('cache', function (Blueprint $table) {
            $table->dropIndex(['expiration']);
        });

        Schema::table('cache_locks', function (Blueprint $table) {
            $table->dropIndex(['expiration']);
            $table->dropIndex(['owner']);
        });

        // Remove additional composite indexes
        Schema::table('venues', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'city_id', 'rating']);
            $table->dropIndex(['is_active', 'type_id', 'rating']);
        });

        Schema::table('treatments', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'venue_id', 'category']);
            $table->dropIndex(['is_active', 'category', 'price']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'venue_id', 'city_id']);
        });

        Schema::table('images', function (Blueprint $table) {
            $table->dropIndex(['imageable_type', 'imageable_id', 'is_primary', 'sort_order']);
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->dropIndex(['venue_id', 'is_verified', 'created_at']);
        });
    }
};
