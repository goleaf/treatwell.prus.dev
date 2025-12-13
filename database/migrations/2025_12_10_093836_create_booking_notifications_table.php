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
        Schema::create('booking_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Notification details
            $table->enum('type', ['confirmation', 'reminder', 'cancellation', 'modification', 'venue_update']);
            $table->enum('channel', ['email', 'sms', 'push'])->default('email');
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone')->nullable();

            // Content
            $table->string('subject');
            $table->text('message');
            $table->json('template_data')->nullable();

            // Status and tracking
            $table->enum('status', ['pending', 'sent', 'failed', 'delivered'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['booking_id', 'type']);
            $table->index(['status', 'sent_at']);
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_notifications');
    }
};
