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
        Schema::create('booking_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            // Policy scope
            $table->enum('policy_type', ['system', 'venue'])->default('venue');
            $table->foreignId('venue_id')->nullable()->constrained()->onDelete('cascade');

            // Policy settings
            $table->integer('cancellation_hours')->default(24); // Hours before booking
            $table->integer('advance_booking_days')->default(30); // Days in advance
            $table->integer('max_bookings_per_day')->default(5); // Per user
            $table->boolean('require_payment')->default(false);
            $table->boolean('allow_modifications')->default(true);
            $table->integer('modification_hours')->default(24); // Hours before booking

            // Notification settings
            $table->boolean('send_confirmation')->default(true);
            $table->boolean('send_reminders')->default(true);
            $table->integer('reminder_hours')->default(24); // Hours before booking

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['policy_type', 'is_active']);
            $table->index(['venue_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_policies');
    }
};
