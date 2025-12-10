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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('entity_id')->nullable()->unique();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->string('normalised_name')->nullable();
            $table->string('subregion')->nullable();
            $table->string('type')->nullable();
            $table->boolean('is_main_city')->default(true);
            $table->foreignId('main_city_id')->nullable()->constrained('cities')->onDelete('set null');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('radius_distance', 10, 2)->nullable();
            $table->string('radius_unit')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
