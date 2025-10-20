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
            // Add missing contact fields
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            
            // Add location fields
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 10, 8)->nullable();
            
            // Add relationship fields
            $table->unsignedBigInteger('location_id')->nullable();
            
            // Add foreign key constraints
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['location_id']);
            
            // Drop columns
            $table->dropColumn([
                'address', 
                'phone', 
                'email', 
                'website', 
                'latitude', 
                'longitude', 
                'location_id'
            ]);
        });
    }
};
