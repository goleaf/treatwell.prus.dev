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
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->foreignId('treatment_id')->nullable()->constrained()->onDelete('cascade');

            // Time slot details
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration'); // in minutes

            // Availability and capacity
            $table->boolean('is_available')->default(true);
            $table->boolean('is_blocked')->default(false);
            $table->integer('capacity')->default(1);
            $table->integer('booked_count')->default(0);

            // Recurring slots
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_type', ['daily', 'weekly', 'monthly'])->nullable();
            $table->date('recurrence_end_date')->nullable();

            // Management
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['venue_id', 'date', 'is_available']);
            $table->index(['date', 'start_time']);
            $table->index(['treatment_id', 'date']);
            $table->index(['is_available', 'is_blocked']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
