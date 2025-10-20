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
        // Drop the table if it exists
        Schema::dropIfExists('treatments');
        
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venue_id');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->decimal('min_price', 10, 2)->nullable();
            $table->decimal('max_price', 10, 2)->nullable();
            $table->integer('min_duration')->nullable();
            $table->integer('max_duration')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('cascade');
            
            // Add a unique constraint for venue_id and name
            $table->unique(['venue_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
