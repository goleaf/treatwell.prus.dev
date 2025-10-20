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
        Schema::create('sitemap_urls', function (Blueprint $table) {
            $table->id();
            $table->string('original_url')->unique();
            $table->string('path')->nullable();
            $table->string('browse_uri')->nullable();
            $table->string('treatment_slug')->nullable();
            $table->string('treatment_name')->nullable();
            $table->string('offer_type_slug')->nullable();
            $table->string('offer_type_name')->nullable();
            $table->string('location_slug')->nullable();
            $table->string('location_name')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->boolean('is_valid')->default(true);
            $table->integer('venues_found')->default(0);
            $table->integer('api_requests')->default(0);
            $table->integer('pages_processed')->default(0);
            $table->timestamp('last_processed_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->text('api_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index('is_processed');
            $table->index('is_valid');
            $table->index(['treatment_slug', 'location_slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sitemap_urls');
    }
};
