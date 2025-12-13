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
        Schema::table('ratings', function (Blueprint $table) {
            if (! Schema::hasColumn('ratings', 'weighted_average')) {
                $table->decimal('weighted_average', 3, 2)->nullable();
            }
            if (! Schema::hasColumn('ratings', 'count')) {
                $table->integer('count')->default(0);
            }
            if (! Schema::hasColumn('ratings', 'cleanliness_avg')) {
                $table->decimal('cleanliness_avg', 3, 2)->nullable();
            }
            if (! Schema::hasColumn('ratings', 'cleanliness_count')) {
                $table->integer('cleanliness_count')->default(0);
            }
            if (! Schema::hasColumn('ratings', 'staff_avg')) {
                $table->decimal('staff_avg', 3, 2)->nullable();
            }
            if (! Schema::hasColumn('ratings', 'staff_count')) {
                $table->integer('staff_count')->default(0);
            }
            if (! Schema::hasColumn('ratings', 'atmosphere_avg')) {
                $table->decimal('atmosphere_avg', 3, 2)->nullable();
            }
            if (! Schema::hasColumn('ratings', 'atmosphere_count')) {
                $table->integer('atmosphere_count')->default(0);
            }
            if (! Schema::hasColumn('ratings', 'display_average')) {
                $table->decimal('display_average', 3, 2)->nullable();
            }
        });

        Schema::table('images', function (Blueprint $table) {
            if (! Schema::hasColumn('images', 'imageable_type')) {
                $table->string('imageable_type')->nullable();
            }
            if (! Schema::hasColumn('images', 'imageable_id')) {
                $table->unsignedBigInteger('imageable_id')->nullable();
            }
            if (! Schema::hasColumn('images', 'external_id')) {
                $table->string('external_id')->nullable();
            }
            if (! Schema::hasColumn('images', 'uri_small')) {
                $table->string('uri_small')->nullable();
            }
            if (! Schema::hasColumn('images', 'uri_medium')) {
                $table->string('uri_medium')->nullable();
            }
            if (! Schema::hasColumn('images', 'uri_large')) {
                $table->string('uri_large')->nullable();
            }
            if (! Schema::hasColumn('images', 'uri_xlarge')) {
                $table->string('uri_xlarge')->nullable();
            }
            if (! Schema::hasColumn('images', 'is_primary')) {
                $table->boolean('is_primary')->default(false);
            }
        });

        Schema::table('opening_hours', function (Blueprint $table) {
            if (! Schema::hasColumn('opening_hours', 'day_of_week')) {
                $table->string('day_of_week')->nullable();
            }
            if (! Schema::hasColumn('opening_hours', 'opening_time')) {
                $table->time('opening_time')->nullable();
            }
            if (! Schema::hasColumn('opening_hours', 'closing_time')) {
                $table->time('closing_time')->nullable();
            }
            if (! Schema::hasColumn('opening_hours', 'is_open')) {
                $table->boolean('is_open')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            //
        });
    }
};
