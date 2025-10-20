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
        // Create countries table
        if (! Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 3)->unique();
                $table->timestamps();
            });
        }

        // Create cities table
        if (! Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('normalised_name')->nullable();
                $table->string('subregion')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->boolean('is_main_city')->default(false);
                $table->foreignId('main_city_id')->nullable()->references('id')->on('cities')->nullOnDelete();
                $table->timestamps();
            });
        }

        // Create venues table if not exists
        if (! Schema::hasTable('venues')) {
            Schema::create('venues', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('url');
                $table->string('source')->default('treatwell_api');
                $table->string('external_id')->nullable();
                $table->text('description')->nullable();
                $table->string('type_id')->nullable();
                $table->string('type_name')->nullable();
                $table->string('normalised_name')->nullable();
                $table->string('desktop_uri')->nullable();
                $table->string('mobile_uri')->nullable();
                $table->string('app_uri')->nullable();
                $table->boolean('is_new_venue')->default(false);
                $table->json('raw_data')->nullable();
                $table->timestamps();
            });
        }

        // Create locations table
        if (! Schema::hasTable('locations')) {
            Schema::create('locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venue_id')->constrained()->onDelete('cascade');
                $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
                $table->string('postal_code')->nullable();
                $table->text('address_line1')->nullable();
                $table->text('address_line2')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->integer('map_zoom')->nullable();
                $table->timestamps();
            });
        }

        // Create ratings table
        if (! Schema::hasTable('ratings')) {
            Schema::create('ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venue_id')->constrained()->onDelete('cascade');
                $table->decimal('average', 3, 2)->nullable();
                $table->integer('count')->nullable();
                $table->json('dimensions')->nullable();
                $table->timestamps();
            });
        }

        // Create opening_hours table
        if (! Schema::hasTable('opening_hours')) {
            Schema::create('opening_hours', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venue_id')->constrained()->onDelete('cascade');
                $table->integer('day_of_week');
                $table->boolean('is_open')->default(true);
                $table->time('open_time');
                $table->time('close_time');
                $table->timestamps();
            });
        }

        // Create images table
        if (! Schema::hasTable('images')) {
            Schema::create('images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venue_id')->constrained()->onDelete('cascade');
                $table->string('external_id')->nullable();
                $table->string('url');
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
            });
        }

        // Create procedures (treatments categories) table
        if (! Schema::hasTable('procedures')) {
            Schema::create('procedures', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // Create procedure_venue pivot table
        if (! Schema::hasTable('procedure_venue')) {
            Schema::create('procedure_venue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('procedure_id')->constrained()->onDelete('cascade');
                $table->foreignId('venue_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->unique(['procedure_id', 'venue_id']);
            });
        }

        // Create city_venue pivot table
        if (! Schema::hasTable('city_venue')) {
            Schema::create('city_venue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('city_id')->constrained()->onDelete('cascade');
                $table->foreignId('venue_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->unique(['city_id', 'venue_id']);
            });
        }

        // Create treatments table
        if (! Schema::hasTable('treatments')) {
            Schema::create('treatments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venue_id')->constrained()->onDelete('cascade');
                $table->foreignId('procedure_id')->nullable()->constrained()->nullOnDelete();
                $table->string('external_id')->nullable();
                $table->string('name');
                $table->decimal('min_price', 10, 2)->nullable();
                $table->decimal('max_price', 10, 2)->nullable();
                $table->integer('min_duration')->nullable();
                $table->integer('max_duration')->nullable();
                $table->string('category_id')->nullable();
                $table->string('category_name')->nullable();
                $table->json('options')->nullable();
                $table->timestamps();
            });
        }

        // Create city_procedure pivot table
        if (! Schema::hasTable('city_procedure')) {
            Schema::create('city_procedure', function (Blueprint $table) {
                $table->id();
                $table->foreignId('city_id')->constrained()->onDelete('cascade');
                $table->foreignId('procedure_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->unique(['city_id', 'procedure_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order to avoid foreign key constraints
        Schema::dropIfExists('city_procedure');
        Schema::dropIfExists('treatments');
        Schema::dropIfExists('city_venue');
        Schema::dropIfExists('procedure_venue');
        Schema::dropIfExists('procedures');
        Schema::dropIfExists('images');
        Schema::dropIfExists('opening_hours');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('venues');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('countries');
    }
};
