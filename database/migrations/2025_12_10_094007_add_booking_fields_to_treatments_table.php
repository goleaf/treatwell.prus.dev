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
        Schema::table('treatments', function (Blueprint $table) {
            $table->boolean('booking_enabled')->default(false);
            $table->integer('advance_booking_days')->nullable();
            $table->integer('cancellation_hours')->nullable();
            $table->integer('buffer_time_before')->default(0); // minutes
            $table->integer('buffer_time_after')->default(0); // minutes
            $table->text('booking_notes')->nullable();

            // Index for performance
            $table->index('booking_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->dropIndex(['booking_enabled']);
            $table->dropColumn([
                'booking_enabled',
                'advance_booking_days',
                'cancellation_hours',
                'buffer_time_before',
                'buffer_time_after',
                'booking_notes',
            ]);
        });
    }
};
