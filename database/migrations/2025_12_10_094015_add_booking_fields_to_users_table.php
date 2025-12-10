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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable();
            $table->boolean('is_venue_owner')->default(false);
            $table->json('notification_preferences')->nullable();
            $table->timestamp('last_booking_at')->nullable();

            // Index for performance
            $table->index('is_venue_owner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_venue_owner']);
            $table->dropColumn([
                'phone',
                'is_venue_owner',
                'notification_preferences',
                'last_booking_at',
            ]);
        });
    }
};
