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
        // Create city_procedure table if it doesn't exist
        if (! Schema::hasTable('city_procedure')) {
            Schema::create('city_procedure', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('city_id');
                $table->unsignedBigInteger('procedure_id');
                $table->unique(['city_id', 'procedure_id']);
                $table->timestamps();
            });
        }

        // Create venue_procedure table if it doesn't exist
        if (! Schema::hasTable('venue_procedure')) {
            Schema::create('venue_procedure', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('venue_id');
                $table->unsignedBigInteger('procedure_id');
                $table->unique(['venue_id', 'procedure_id']);
                $table->timestamps();
            });
        }

        // Create city_venue table if it doesn't exist
        if (! Schema::hasTable('city_venue')) {
            Schema::create('city_venue', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('city_id');
                $table->unsignedBigInteger('venue_id');
                $table->unique(['city_id', 'venue_id']);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop tables if they exist
        if (Schema::hasTable('city_venue')) {
            Schema::dropIfExists('city_venue');
        }

        if (Schema::hasTable('venue_procedure')) {
            Schema::dropIfExists('venue_procedure');
        }

        if (Schema::hasTable('city_procedure')) {
            Schema::dropIfExists('city_procedure');
        }
    }
};
