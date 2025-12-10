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
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('external_id')->nullable()->unique();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('type_id')->nullable();
            $table->string('type_name')->nullable();
            $table->string('normalised_name')->nullable();
            $table->string('desktop_uri')->nullable();
            $table->string('mobile_uri')->nullable();
            $table->string('app_uri')->nullable();
            $table->boolean('is_new_venue')->default(false);
            $table->json('raw_data')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->json('opening_hours')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
