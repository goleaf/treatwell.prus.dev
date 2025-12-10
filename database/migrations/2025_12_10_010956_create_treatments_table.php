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
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->integer('duration')->nullable(); // in minutes
            $table->decimal('price', 8, 2)->nullable();
            $table->decimal('min_price', 8, 2)->nullable();
            $table->decimal('max_price', 8, 2)->nullable();
            $table->integer('min_duration')->nullable();
            $table->integer('max_duration')->nullable();
            $table->string('category_id')->nullable();
            $table->string('category_name')->nullable();
            $table->string('category')->nullable();
            $table->json('options')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['venue_id', 'slug']);
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
