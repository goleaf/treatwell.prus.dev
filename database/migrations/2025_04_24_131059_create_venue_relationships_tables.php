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
        // Create procedure_venue pivot table if it doesn't exist
        if (!Schema::hasTable('procedure_venue')) {
            Schema::create('procedure_venue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('procedure_id')->constrained()->onDelete('cascade');
                $table->foreignId('venue_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->unique(['procedure_id', 'venue_id']);
            });
        }

        // Create city_venue pivot table if it doesn't exist
        if (!Schema::hasTable('city_venue')) {
            Schema::create('city_venue', function (Blueprint $table) {
                $table->id();
                $table->foreignId('city_id')->constrained()->onDelete('cascade');
                $table->foreignId('venue_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->unique(['city_id', 'venue_id']);
            });
        }

        // Create city_procedure pivot table if it doesn't exist
        if (!Schema::hasTable('city_procedure')) {
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
        Schema::dropIfExists('city_procedure');
        Schema::dropIfExists('city_venue');
        Schema::dropIfExists('procedure_venue');
    }
};
