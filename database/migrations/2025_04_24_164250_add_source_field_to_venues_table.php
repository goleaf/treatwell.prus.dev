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
            // Add source field if it doesn't exist
            if (!Schema::hasColumn('venues', 'source')) {
                $table->string('source')->nullable()->after('website')->comment('Source of the venue data');
            }
            
            // Add external_id field if it doesn't exist
            if (!Schema::hasColumn('venues', 'external_id')) {
                $table->string('external_id')->nullable()->after('id')->index();
            }
            
            // Add latitude/longitude fields if they don't exist
            if (!Schema::hasColumn('venues', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('source');
            }
            
            if (!Schema::hasColumn('venues', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
        
        // Ensure cities table has is_main_city field
        Schema::table('cities', function (Blueprint $table) {
            if (!Schema::hasColumn('cities', 'is_main_city')) {
                $table->boolean('is_main_city')->default(false)->after('slug');
            }
        });
        
        // Ensure treatments table has external_id field
        Schema::table('treatments', function (Blueprint $table) {
            if (!Schema::hasColumn('treatments', 'external_id')) {
                $table->string('external_id')->nullable()->after('id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            if (Schema::hasColumn('venues', 'source')) {
                $table->dropColumn('source');
            }
            
            if (Schema::hasColumn('venues', 'external_id')) {
                $table->dropColumn('external_id');
            }
            
            if (Schema::hasColumn('venues', 'latitude')) {
                $table->dropColumn('latitude');
            }
            
            if (Schema::hasColumn('venues', 'longitude')) {
                $table->dropColumn('longitude');
            }
        });
        
        Schema::table('cities', function (Blueprint $table) {
            if (Schema::hasColumn('cities', 'is_main_city')) {
                $table->dropColumn('is_main_city');
            }
        });
        
        Schema::table('treatments', function (Blueprint $table) {
            if (Schema::hasColumn('treatments', 'external_id')) {
                $table->dropColumn('external_id');
            }
        });
    }
};
