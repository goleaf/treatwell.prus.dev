<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the bookings table for the booking system.
     * Stores booking records with customer information, timing, pricing,
     * and status tracking throughout the booking lifecycle.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->foreignId('treatment_id')->constrained()->onDelete('cascade');
            $table->foreignId('time_slot_id')->constrained()->onDelete('cascade');

            // Booking details
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration'); // in minutes
            $table->decimal('price', 10, 2);
            $table->char('currency', 3)->default('EUR'); // char(3) is more appropriate for ISO currency codes

            // Status and tracking
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');

            // Customer information
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->text('special_requests')->nullable();

            // Policy and timing
            $table->timestamp('cancellation_deadline')->nullable();
            $table->integer('advance_booking_days')->nullable();

            // Status timestamps
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['venue_id', 'booking_date']);
            $table->index(['booking_date', 'status']);
            $table->index('booking_reference');
            $table->index(['status', 'booking_date']); // For dashboard queries
            $table->index(['venue_id', 'status', 'booking_date']); // For venue owner dashboard
            $table->index(['treatment_id', 'booking_date']); // For treatment popularity analysis
            $table->index(['customer_email']); // For customer lookup
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
