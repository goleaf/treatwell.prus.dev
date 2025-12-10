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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->decimal('weighted_average', 3, 2)->nullable();
            $table->integer('count')->default(0);
            $table->decimal('cleanliness_avg', 3, 2)->nullable();
            $table->integer('cleanliness_count')->default(0);
            $table->decimal('staff_avg', 3, 2)->nullable();
            $table->integer('staff_count')->default(0);
            $table->decimal('atmosphere_avg', 3, 2)->nullable();
            $table->integer('atmosphere_count')->default(0);
            $table->decimal('display_average', 3, 2)->nullable();
            $table->string('reviewer_name')->nullable();
            $table->string('reviewer_email')->nullable();
            $table->integer('rating')->nullable(); // 1-5
            $table->text('comment')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
