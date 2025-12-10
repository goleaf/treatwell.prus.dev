<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Procedure;
use App\Models\User;
use App\Models\Venue;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SampleDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_data_seeder_creates_comprehensive_data(): void
    {
        // Run the seeder (which includes CountrySeeder)
        $this->seed(SampleDataSeeder::class);

        // Verify basic data was created
        $this->assertGreaterThan(0, User::count());
        $this->assertGreaterThan(0, Country::count());
        $this->assertGreaterThan(0, City::count());
        $this->assertGreaterThan(0, Venue::count());
        $this->assertGreaterThan(0, Procedure::count());

        // Verify relationships are working
        $venue = Venue::with(['city', 'location', 'ratingDetails', 'treatments', 'services', 'images', 'openingHours', 'procedures'])->first();

        $this->assertNotNull($venue);

        // Check if venue has city_id set
        $this->assertNotNull($venue->city_id, 'Venue should have city_id set');
        $this->assertNotNull($venue->city, 'Venue should have city relationship loaded');

        // Check that venues have related data
        $venuesWithLocations = Venue::has('location')->count();
        $venuesWithRatings = Venue::has('ratingDetails')->count();
        $venuesWithTreatments = Venue::has('treatments')->count();
        $venuesWithImages = Venue::has('images')->count();
        $venuesWithOpeningHours = Venue::has('openingHours')->count();

        $this->assertGreaterThan(0, $venuesWithLocations);
        $this->assertGreaterThan(0, $venuesWithRatings);
        $this->assertGreaterThan(0, $venuesWithTreatments);
        $this->assertGreaterThan(0, $venuesWithImages);
        $this->assertGreaterThan(0, $venuesWithOpeningHours);
    }

    public function test_sample_data_seeder_creates_test_users(): void
    {
        $this->seed(SampleDataSeeder::class);

        // Check that test users were created
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'name' => 'Admin User',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    public function test_sample_data_seeder_can_run_multiple_times(): void
    {
        // Run seeder first time
        $this->seed(SampleDataSeeder::class);
        $initialUserCount = User::count();
        $initialVenueCount = Venue::count();

        // Run seeder second time
        $this->seed(SampleDataSeeder::class);

        // User count should not increase significantly (test users should be created once)
        $this->assertLessThanOrEqual($initialUserCount + 10, User::count());

        // Venue count should not increase (existing venues should be used)
        $this->assertEquals($initialVenueCount, Venue::count());
    }

    public function test_venues_have_complete_opening_hours(): void
    {
        $this->seed(SampleDataSeeder::class);

        $venue = Venue::has('openingHours')->first();
        $this->assertNotNull($venue);

        $openingHours = $venue->openingHours;
        $this->assertCount(7, $openingHours); // Should have all 7 days

        $days = $openingHours->pluck('day_of_week')->toArray();
        $expectedDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($expectedDays as $day) {
            $this->assertContains($day, $days);
        }
    }

    public function test_venues_have_procedures_attached(): void
    {
        $this->seed(SampleDataSeeder::class);

        $venuesWithProcedures = Venue::has('procedures')->count();
        $this->assertGreaterThan(0, $venuesWithProcedures);

        $venue = Venue::has('procedures')->first();
        $this->assertGreaterThan(0, $venue->procedures->count());
    }
}
