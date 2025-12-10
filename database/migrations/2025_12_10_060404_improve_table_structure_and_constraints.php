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
        // Add missing columns to tables that are referenced in models but might not exist
        Schema::table('images', function (Blueprint $table) {
            // Ensure sort_order column exists (referenced in model)
            if (! Schema::hasColumn('images', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('alt_text');
            }
        });

        // Ensure procedures table has proper structure
        Schema::table('procedures', function (Blueprint $table) {
            // Add missing columns that might be referenced
            if (! Schema::hasColumn('procedures', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
            if (! Schema::hasColumn('procedures', 'category')) {
                $table->string('category')->nullable()->after('description');
            }
        });

        // Add soft deletes to key tables (common CRUD pattern)
        Schema::table('venues', function (Blueprint $table) {
            if (! Schema::hasColumn('venues', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('treatments', function (Blueprint $table) {
            if (! Schema::hasColumn('treatments', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add additional indexes for better performance on common queries
        Schema::table('treatments', function (Blueprint $table) {
            // Add index on slug if it doesn't exist
            $indexes = collect(Schema::getConnection()->select('PRAGMA index_list(treatments)'))
                ->pluck('name')->toArray();

            if (! in_array('treatments_slug_index', $indexes)) {
                $table->index('slug');
            }
        });

        Schema::table('venues', function (Blueprint $table) {
            // Add index on slug if it doesn't exist
            $indexes = collect(Schema::getConnection()->select('PRAGMA index_list(venues)'))
                ->pluck('name')->toArray();

            if (! in_array('venues_slug_index', $indexes)) {
                $table->index('slug');
            }
        });

        Schema::table('cities', function (Blueprint $table) {
            // Add index on slug if it doesn't exist
            $indexes = collect(Schema::getConnection()->select('PRAGMA index_list(cities)'))
                ->pluck('name')->toArray();

            if (! in_array('cities_slug_index', $indexes)) {
                $table->index('slug');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove soft deletes
        Schema::table('venues', function (Blueprint $table) {
            if (Schema::hasColumn('venues', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('treatments', function (Blueprint $table) {
            if (Schema::hasColumn('treatments', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        // Remove added columns
        Schema::table('procedures', function (Blueprint $table) {
            if (Schema::hasColumn('procedures', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('procedures', 'category')) {
                $table->dropColumn('category');
            }
        });

        Schema::table('images', function (Blueprint $table) {
            if (Schema::hasColumn('images', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });

        // Remove added indexes
        Schema::table('treatments', function (Blueprint $table) {
            $table->dropIndex(['slug']);
        });

        Schema::table('venues', function (Blueprint $table) {
            $table->dropIndex(['slug']);
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropIndex(['slug']);
        });
    }
};
