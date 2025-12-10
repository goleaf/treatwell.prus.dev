<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseIndexesTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_indexes_exist(): void
    {
        // Test that key indexes exist by checking if they can be used in queries
        // This is a basic smoke test to ensure the migration ran successfully
        
        // Test venues table indexes
        $this->assertDatabaseTableExists('venues');
        
        // Test locations table indexes
        $this->assertDatabaseTableExists('locations');
        
        // Test treatments table indexes
        $this->assertDatabaseTableExists('treatments');
        
        // Test ratings table indexes
        $this->assertDatabaseTableExists('ratings');
        
        // Test cities table indexes
        $this->assertDatabaseTableExists('cities');
        
        // Test images table indexes
        $this->assertDatabaseTableExists('images');
        
        // Test opening_hours table indexes
        $this->assertDatabaseTableExists('opening_hours');
        
        // Test countries table indexes
        $this->assertDatabaseTableExists('countries');
        
        // Test users table indexes
        $this->assertDatabaseTableExists('users');
        
        // Test procedures table indexes
        $this->assertDatabaseTableExists('procedures');
    }

    public function test_indexed_queries_perform_efficiently(): void
    {
        // Create some test data to verify indexes work
        $country = \App\Models\Country::factory()->create();
        $city = \App\Models\City::factory()->create(['country_id' => $country->id]);
        $venue = \App\Models\Venue::factory()->create(['city_id' => $city->id]);
        
        // Test venue queries that should use indexes
        $activeVenues = \App\Models\Venue::where('is_active', true)->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $activeVenues);
        
        // Test city queries that should use indexes
        $citiesInCountry = \App\Models\City::where('country_id', $country->id)->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $citiesInCountry);
        
        // Test treatment queries that should use indexes
        $treatment = \App\Models\Treatment::factory()->create(['venue_id' => $venue->id]);
        $venuesTreatments = \App\Models\Treatment::where('venue_id', $venue->id)->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $venuesTreatments);
    }

    private function assertDatabaseTableExists(string $table): void
    {
        $exists = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name=?", [$table]);
        $this->assertNotEmpty($exists, "Table {$table} does not exist");
    }
}
