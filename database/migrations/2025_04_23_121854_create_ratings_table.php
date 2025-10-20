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
            $table->unsignedBigInteger('venue_id');
            $table->decimal('weighted_average', 3, 2)->nullable();
            $table->integer('count')->default(0);
            $table->decimal('cleanliness_avg', 3, 2)->nullable();
            $table->integer('cleanliness_count')->default(0);
            $table->decimal('staff_avg', 3, 2)->nullable();
            $table->integer('staff_count')->default(0);
            $table->decimal('atmosphere_avg', 3, 2)->nullable();
            $table->integer('atmosphere_count')->default(0);
            $table->string('display_average')->nullable();
            $table->timestamps();
            
            $table->foreign('venue_id')->references('id')->on('venues');
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
