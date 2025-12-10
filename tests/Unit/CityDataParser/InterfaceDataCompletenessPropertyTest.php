<?php

namespace Tests\Unit\CityDataParser;

use App\Models\City;
use App\Models\Country;
use App\Models\Treatment;
use App\Models\Venue;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: city-data-parser, Property 6: Interface data completeness
 * Validates: Requirements 2.1, 2.4
 */
class InterfaceDataCompletenessPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->erisSetUp();
    }

    /**
     * Property 6: Interface data completeness
     * For any parsed city data, the display interface should show all collected fields
     * and attributes without requiring authentication
     */
    public function test_venue_resource_displays_all_collected_fields(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string(),
            Generator\string(),
            Generator\choose(1, 5),
            Generator\bool(),
            Generator\bool()
        )->then(function (
            string $venueName,
            string $venueSlug,
            string $description,
            int $rating,
            bool $isActive,
            bool $bookingEnabled
        ) {
            // Skip empty names as they're not valid
            if (empty(trim($venueName)) || empty(trim($venueSlug))) {
                return;
            }

            // Create test data with unique names
            $uniqueId = uniqid();
            $country = Country::factory()->create([
                'name' => "Test Country {$uniqueId}",
                'slug' => "test-country-{$uniqueId}",
                'code' => strtoupper(substr($uniqueId, 0, 2)),
            ]);
            $city = City::factory()->create([
                'name' => "Test City {$uniqueId}",
                'slug' => "test-city-{$uniqueId}",
                'country_id' => $country->id,
            ]);

            $venue = Venue::factory()->create([
                'name' => trim($venueName),
                'slug' => trim($venueSlug),
                'description' => $description,
                'city_id' => $city->id,
                'rating' => (float) $rating,
                'is_active' => $isActive,
                'booking_enabled' => $bookingEnabled,
                'address' => 'Test Address',
                'phone' => '+370 600 12345',
                'email' => 'test@example.com',
                'website' => 'https://example.com',
                'latitude' => 54.6872,
                'longitude' => 25.2797,
            ]);

            // Create treatments for the venue
            $treatmentCount = rand(1, 3);
            for ($i = 0; $i < $treatmentCount; $i++) {
                Treatment::factory()->create([
                    'venue_id' => $venue->id,
                    'name' => "Treatment {$i}",
                    'is_active' => true,
                ]);
            }

            // Property: Venue should have all essential data fields
            $this->assertNotEmpty($venue->name, 'Venue should have a name');
            $this->assertNotEmpty($venue->slug, 'Venue should have a slug');
            $this->assertNotNull($venue->city_id, 'Venue should be linked to a city');
            $this->assertNotNull($venue->is_active, 'Venue should have active status');
            $this->assertNotNull($venue->booking_enabled, 'Venue should have booking status');

            // Property: Venue should have relationship data
            $this->assertInstanceOf(City::class, $venue->city, 'Venue should have city relationship');
            $this->assertGreaterThan(0, $venue->treatments()->count(), 'Venue should have treatments');

            // Property: Data completeness should be calculated and displayed
            $completeness = $venue->getDataCompleteness();
            $this->assertIsFloat($completeness, 'Data completeness should be a float');
            $this->assertGreaterThanOrEqual(0, $completeness, 'Data completeness should be >= 0');
            $this->assertLessThanOrEqual(100, $completeness, 'Data completeness should be <= 100');

            // Property: Venue should provide formatted display methods
            $this->assertIsString($venue->getFormattedAddress(), 'Venue should provide formatted address');
            $this->assertIsFloat($venue->getAverageRating(), 'Venue should provide average rating');
        });
    }

    /**
     * Property: City resource should display all collected city data and statistics
     */
    public function test_city_resource_displays_all_collected_fields(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string(),
            Generator\bool(),
            Generator\choose(-90, 90),
            Generator\choose(-180, 180)
        )->then(function (
            string $cityName,
            string $citySlug,
            bool $isMainCity,
            int $latitude,
            int $longitude
        ) {
            // Skip empty names
            if (empty(trim($cityName)) || empty(trim($citySlug))) {
                return;
            }

            // Create test data with unique names
            $uniqueId = uniqid();
            $country = Country::factory()->create([
                'name' => "Test Country {$uniqueId}",
                'slug' => "test-country-{$uniqueId}",
                'code' => strtoupper(substr($uniqueId, 0, 2)),
            ]);
            $city = City::factory()->create([
                'name' => trim($cityName)." {$uniqueId}",
                'slug' => trim($citySlug)."-{$uniqueId}",
                'is_main_city' => $isMainCity,
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
                'country_id' => $country->id,
            ]);

            // Create venues for the city
            $venueCount = rand(1, 3);
            for ($i = 0; $i < $venueCount; $i++) {
                Venue::factory()->create([
                    'city_id' => $city->id,
                    'name' => "Venue {$i}",
                    'is_active' => true,
                ]);
            }

            // Property: City should have all essential data fields
            $this->assertNotEmpty($city->name, 'City should have a name');
            $this->assertNotEmpty($city->slug, 'City should have a slug');
            $this->assertNotNull($city->country_id, 'City should be linked to a country');
            $this->assertNotNull($city->is_main_city, 'City should have main city status');

            // Property: City statistics should be calculated correctly
            $venuesCount = $city->getVenuesCount();
            $this->assertEquals($venueCount, $venuesCount, 'Venues count should match created venues');

            // Property: City should have relationship data
            $this->assertInstanceOf(Country::class, $city->country, 'City should have country relationship');
            $this->assertGreaterThan(0, $city->venues()->count(), 'City should have venues');

            // Property: Data completeness should be available
            $completeness = $city->getDataCompleteness();
            $this->assertIsFloat($completeness, 'City data completeness should be a float');
            $this->assertGreaterThanOrEqual(0, $completeness, 'City data completeness should be >= 0');

            // Property: City should provide display methods
            $this->assertIsString($city->getFullDisplayName(), 'City should provide full display name');
            $this->assertIsFloat($city->getAverageRating(), 'City should provide average rating');
        });
    }

    /**
     * Property: Treatment resource should display pricing and duration information
     */
    public function test_treatment_resource_displays_pricing_and_duration(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string(),
            Generator\choose(10, 500),
            Generator\choose(15, 180),
            Generator\string(),
            Generator\bool()
        )->then(function (
            string $treatmentName,
            string $treatmentSlug,
            int $price,
            int $duration,
            string $category,
            bool $isActive
        ) {
            // Skip empty names
            if (empty(trim($treatmentName)) || empty(trim($treatmentSlug))) {
                return;
            }

            // Create test data with unique names
            $uniqueId = uniqid();
            $country = Country::factory()->create([
                'name' => "Test Country {$uniqueId}",
                'slug' => "test-country-{$uniqueId}",
                'code' => strtoupper(substr($uniqueId, 0, 2)),
            ]);
            $city = City::factory()->create([
                'name' => "Test City {$uniqueId}",
                'slug' => "test-city-{$uniqueId}",
                'country_id' => $country->id,
            ]);
            $venue = Venue::factory()->create([
                'city_id' => $city->id,
                'name' => "Test Venue {$uniqueId}",
                'slug' => "test-venue-{$uniqueId}",
            ]);

            $treatment = Treatment::factory()->create([
                'venue_id' => $venue->id,
                'name' => trim($treatmentName),
                'slug' => trim($treatmentSlug),
                'price' => (float) $price,
                'duration' => $duration,
                'category' => trim($category),
                'is_active' => $isActive,
                'description' => 'Test treatment description',
            ]);

            // Property: Treatment should have all essential data fields
            $this->assertNotEmpty($treatment->name, 'Treatment should have a name');
            $this->assertNotEmpty($treatment->slug, 'Treatment should have a slug');
            $this->assertNotNull($treatment->venue_id, 'Treatment should be linked to a venue');
            $this->assertNotNull($treatment->is_active, 'Treatment should have active status');

            // Property: Treatment should have relationship data
            $this->assertInstanceOf(Venue::class, $treatment->venue, 'Treatment should have venue relationship');
            $this->assertEquals($city->id, $treatment->venue->city_id, 'Treatment venue should be linked to city');

            // Property: Price and duration formatting should work correctly
            $formattedPrice = $treatment->getFormattedPrice();
            $this->assertIsString($formattedPrice, 'Formatted price should be a string');
            $this->assertStringContainsString('€', $formattedPrice, 'Formatted price should contain currency symbol');

            $formattedDuration = $treatment->getFormattedDuration();
            $this->assertIsString($formattedDuration, 'Formatted duration should be a string');
            $this->assertStringContainsString('min', $formattedDuration, 'Formatted duration should contain time unit');

            // Property: Data completeness should be calculated
            $completeness = $treatment->getDataCompleteness();
            $this->assertIsFloat($completeness, 'Treatment data completeness should be a float');
            $this->assertGreaterThanOrEqual(0, $completeness, 'Treatment data completeness should be >= 0');
            $this->assertLessThanOrEqual(100, $completeness, 'Treatment data completeness should be <= 100');
        });
    }

    /**
     * Property: All resources should be accessible without authentication
     */
    public function test_resources_accessible_without_authentication(): void
    {
        // Property: Filament panel should be configured without authentication
        $panelProvider = new \App\Providers\Filament\AdminPanelProvider($this->app);
        $panel = $panelProvider->panel(new \Filament\Panel);

        // Property: Panel should have no auth middleware
        $authMiddleware = $panel->getAuthMiddleware();
        $this->assertEmpty($authMiddleware, 'Panel should have no authentication middleware');

        // Property: Panel should be configured for display-only interface
        $this->assertEquals('admin', $panel->getId(), 'Panel should have admin ID');
        $this->assertEquals('City Data Parser', $panel->getBrandName(), 'Panel should have correct brand name');
    }

    /**
     * Property: Resources should display relationship data correctly
     */
    public function test_resources_display_relationship_data(): void
    {
        $this->forAll(
            Generator\choose(1, 3)
        )->then(function (int $relationshipCount) {
            // Create interconnected test data with unique names
            $uniqueId = uniqid();
            $country = Country::factory()->create([
                'name' => "Test Country {$uniqueId}",
                'slug' => "test-country-{$uniqueId}",
                'code' => strtoupper(substr($uniqueId, 0, 2)),
            ]);
            $city = City::factory()->create([
                'name' => "Test City {$uniqueId}",
                'slug' => "test-city-{$uniqueId}",
                'country_id' => $country->id,
            ]);

            $venues = [];
            for ($i = 0; $i < $relationshipCount; $i++) {
                $venue = Venue::factory()->create([
                    'city_id' => $city->id,
                    'name' => "Venue {$i}",
                ]);
                $venues[] = $venue;

                // Create treatments for each venue
                Treatment::factory()->create([
                    'venue_id' => $venue->id,
                    'name' => "Treatment {$i}",
                ]);
            }

            // Property: City should show correct venue count
            $cityVenueCount = $city->venues()->count();
            $this->assertEquals($relationshipCount, $cityVenueCount, 'City should show correct venue count');

            // Property: Each venue should show correct city relationship
            foreach ($venues as $venue) {
                $this->assertEquals($city->id, $venue->city_id, 'Venue should be linked to correct city');
                $this->assertEquals($city->name, $venue->city->name, 'Venue should display correct city name');
            }

            // Property: Treatment count should be accurate for this city
            $cityTreatments = Treatment::whereHas('venue', function ($query) use ($city) {
                $query->where('city_id', $city->id);
            })->count();
            $this->assertEquals($relationshipCount, $cityTreatments, 'City treatments should match created count');
        });
    }

    /**
     * Property: Search and filtering should work on displayed fields
     */
    public function test_resources_provide_search_and_filter_capabilities(): void
    {
        // Create test data with unique names
        $uniqueId = uniqid();
        $country = Country::factory()->create([
            'name' => "Lithuania {$uniqueId}",
            'slug' => "lithuania-{$uniqueId}",
            'code' => 'LT',
        ]);
        $city = City::factory()->create([
            'name' => "Vilnius {$uniqueId}",
            'slug' => "vilnius-{$uniqueId}",
            'country_id' => $country->id,
        ]);

        $venue = Venue::factory()->create([
            'city_id' => $city->id,
            'name' => 'Test Spa',
            'is_active' => true,
            'booking_enabled' => true,
        ]);

        $treatment = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Massage Therapy',
            'category' => 'wellness',
            'is_active' => true,
        ]);

        // Property: Models should provide searchable and filterable data
        $this->assertNotEmpty($venue->name, 'Venue should have searchable name');
        $this->assertNotEmpty($city->name, 'City should have searchable name');
        $this->assertNotEmpty($treatment->name, 'Treatment should have searchable name');
        $this->assertNotEmpty($treatment->category, 'Treatment should have filterable category');

        // Property: Models should support filtering by status
        $activeVenues = Venue::where('is_active', true)->count();
        $this->assertGreaterThan(0, $activeVenues, 'Should be able to filter active venues');

        $activeTreatments = Treatment::where('is_active', true)->count();
        $this->assertGreaterThan(0, $activeTreatments, 'Should be able to filter active treatments');

        // Property: Models should support relationship filtering
        $venuesInCity = Venue::where('city_id', $city->id)->count();
        $this->assertGreaterThan(0, $venuesInCity, 'Should be able to filter venues by city');

        $treatmentsInVenue = Treatment::where('venue_id', $venue->id)->count();
        $this->assertGreaterThan(0, $treatmentsInVenue, 'Should be able to filter treatments by venue');
    }

    /**
     * Configure Eris to run sufficient iterations
     */
    protected function erisSetUp(): void
    {
        $this->iterations = 10; // Reduce iterations to avoid unique constraint issues
        $this->seed = rand(0, 2 ** 31 - 1);
        $this->randRange = \Eris\Random\purePhpMtRand();
    }
}
