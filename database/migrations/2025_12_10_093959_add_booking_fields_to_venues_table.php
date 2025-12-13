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
        Schema::table('venues', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('booking_enabled')->default(false);
            $table->integer('booking_advance_days')->default(30);
            $table->integer('default_cancellation_hours')->default(24);
            $table->text('booking_instructions')->nullable();

            // Index for performance
            $table->index(['owner_id', 'booking_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropIndex(['owner_id', 'booking_enabled']);
            $table->dropColumn([
                'owner_id',
                'booking_enabled',
                'booking_advance_days',
                'default_cancellation_hours',
                'booking_instructions',
            ]);
        });
    }
};
