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
        Schema::create('opening_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->string('day_of_week');
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            // Note: field name is opening_time/closing_time, but ScrapeTreatwellAll uses 'from'/'to'
            // We'll handle the mapping in the model/command
            $table->boolean('is_open')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opening_hours');
    }
};
