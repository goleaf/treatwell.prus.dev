<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, backup any existing data
        $locationData = [];
        if (Schema::hasTable('locations')) {
            try {
                $locationData = DB::table('locations')->get()->toArray();
            } catch (\Exception $e) {
                // Ignore if we can't get data
            }
            
            // Drop the existing locations table
            Schema::dropIfExists('locations');
        }
        
        // Create fresh locations table
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venue_id');
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            
            // Add foreign key constraint to venues table
            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('cascade');
            
            // Add foreign key constraint to cities table
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
        });
        
        // Restore any backed up data that has valid venue_id
        if (!empty($locationData)) {
            foreach ($locationData as $location) {
                // Check if the venue exists
                $venueExists = DB::table('venues')->where('id', $location->venue_id)->exists();
                if ($venueExists) {
                    $data = (array) $location;
                    unset($data['id']); // Remove id to let it auto-increment
                    
                    try {
                        DB::table('locations')->insert($data);
                    } catch (\Exception $e) {
                        // Ignore errors on restore
                    }
                }
            }
        }
        
        // Fix any other issues with venues_old references
        try {
            // Drop any triggers or foreign keys that might reference venues_old
            $foreignKeys = DB::select("PRAGMA foreign_key_list('locations')");
            foreach ($foreignKeys as $foreignKey) {
                if ($foreignKey->table === 'venues_old') {
                    DB::statement("DROP TRIGGER IF EXISTS fk_{$foreignKey->from}_{$foreignKey->table}_{$foreignKey->to}");
                }
            }
        } catch (\Exception $e) {
            // Ignore any errors
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback functionality as this is a fix
    }
};
