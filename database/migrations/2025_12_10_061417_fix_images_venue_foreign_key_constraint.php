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
        // Fix the images table venue_id foreign key constraint to use SET NULL instead of CASCADE
        // This is important because images can exist without a direct venue relationship (via morphable)
        Schema::table('images', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['venue_id']);

            // Add the corrected foreign key constraint with SET NULL on delete
            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            // Drop the SET NULL constraint
            $table->dropForeign(['venue_id']);

            // Restore the CASCADE constraint (previous state)
            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('cascade');
        });
    }
};
