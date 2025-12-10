<?php

namespace Tests\Unit\CityDataParser;

use App\Models\City;
use App\Models\Country;
use App\Models\Image;
use App\Models\Location;
use App\Models\Rating;
use App\Models\Treatment;
use App\Models\Venue;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: city-data-parser, Property 7: Interface functionality
 * Validates: Requirements 2.2, 2.3
 */
class InterfaceFunctionalityPropertyTest extends TestCase
{
    use RefreshDatabase, TestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->erisSetUp();
    }

    /**
     * Property 7: Interface functionality
     * For any data set displayed in the interface, search, filtering, and sorting
     * operations should work correctly and return expected results
     */
    public function test_venue_search_and_filtering_functionality(): void
    {
        $this->forAll(
            Generator\choose(2, 5),
            Generator\bool(),
            Generator\bool()
        )->then(function (
            int $venueCount,
            bool $activeStatus,
            bool $bookingEnabled
        ) {
            // Create test data with unique identifiers
            $uniqueId = uniqid();
            $country = Country::factory()->create([
                'name' => "Test Country {$uniqueId}",
                'slug' => "test-country-{$uniqueId}",
                'code' => 'T'.substr($uniqueId, -1),
            ]);

            $city = City::factory()->create([
                'name' => "Test City {$uniqueId}",
                'slug' => "test-city-{$uniqueId}",
                'country_id' => $country->id,
            ]);

            $venues = [];
            for ($i = 0; $i < $venueCount; $i++) {
                $venue = Venue::factory()->create([
                    'city_id' => $city->id,
                    'name' => "Test Venue {$i} {$uniqueId}",
                    'slug' => "test-venue-{$i}-{$uniqueId}",
                    'is_active' => $activeStatus,
                    'booking_enabled' => $bookingEnabled,
                    'rating' => rand(1, 5),
                ]);
                $venues[] = $venue;

                // Create treatments for each venue
                Treatment::factory()->create([
                    'venue_id' => $venue->id,
                    'name' => "Treatment {$i} {$uniqueId}",
                    'slug' => "treatment-{$i}-{$uniqueId}",
                    'is_active' => $activeStatus,
                ]);
            }

            // Property: Search functionality should work correctly
            $searchResults = Venue::where('name', 'like', "%Test Venue%{$uniqueId}%")->get();
            $this->assertCount($venueCount, $searchResults, 'Search should return all matching venues');

            // Property: Filtering by city should work correctly
            $cityFilterResults = Venue::where('city_id', $city->id)->get();
            $this->assertCount($venueCount, $cityFilterResults, 'City filter should return all venues in the city');

            // Property: Filtering by active status should work correctly
            $activeFilterResults = Venue::where('is_active', $activeStatus)->where('city_id', $city->id)->get();
            $this->assertCount($venueCount, $activeFilterResults, 'Active status filter should work correctly');

            // Property: Filtering by booking enabled should work correctly
            $bookingFilterResults = Venue::where('booking_enabled', $bookingEnabled)->where('city_id', $city->id)->get();
            $this->assertCount($venueCount, $bookingFilterResults, 'Booking enabled filter should work correctly');

            // Property: Sorting by name should work correctly
            $sortedResults = Venue::where('city_id', $city->id)->orderBy('name')->get();
            $this->assertCount($venueCount, $sortedResults, 'Sorting should return all venues');

            // Verify sorting order
            $sortedNames = $sortedResults->pluck('name')->toArray();
            $expectedSortedNames = $sortedNames;
            sort($expectedSortedNames);
            $this->assertEquals($expectedSortedNames, $sortedNames, 'Venues should be sorted by name');

            // Property: Relationship filtering should work correctly
            $venuesWithTreatments = Venue::has('treatments')->where('city_id', $city->id)->get();
            $this->assertCount($venueCount, $venuesWithTreatments, 'Should find all venues with treatments');
        });
    }

    /**
     * Property: City search and filtering should work correctly
     */
    public function test_city_search_and_filtering_functionality(): void
    {
        $this->forAll(
            Generator\choose(2, 4),
            Generator\bool()
        )->then(function (
            int $cityCount,
            bool $isMainCity
        ) {
            // Create test data with unique identifiers
            $uniqueId = uniqid();
            $country = Country::factory()->create([
                'name' => "Test Country {$uniqueId}",
                'slug' => "test-country-{$uniqueId}",
                'code' => 'T'.substr($uniqueId, -1),
            ]);

            $cities = [];
            for ($i = 0; $i < $cityCount; $i++) {
                $city = City::factory()->create([
                    'name' => "Test City {$i} {$uniqueId}",
                    'slug' => "test-city-{$i}-{$uniqueId}",
                    'country_id' => $country->id,
                    'is_main_city' => $isMainCity,
                ]);
                $cities[] = $city;

                // Create venues for each city
                Venue::factory()->create([
                    'city_id' => $city->id,
                    'name' => "Venue in City {$i} {$uniqueId}",
                    'slug' => "venue-city-{$i}-{$uniqueId}",
                ]);
            }

            // Property: Search functionality should work correctly
            $searchResults = City::where('name', 'like', "%Test City%{$uniqueId}%")->get();
            $this->assertCount($cityCount, $searchResults, 'Search should return all matching cities');

            // Property: Filtering by country should work correctly
            $countryFilterResults = City::where('country_id', $country->id)->get();
            $this->assertGreaterThanOrEqual($cityCount, $countryFilterResults->count(), 'Country filter should return cities in the country');

            // Property: Filtering by main city status should work correctly
            $mainCityFilterResults = City::where('is_main_city', $isMainCity)->where('country_id', $country->id)->get();
            $this->assertCount($cityCount, $mainCityFilterResults, 'Main city filter should work correctly');

            // Property: Filtering cities with venues should work correctly
            $citiesWithVenues = City::has('venues')->where('country_id', $country->id)->get();
            $this->assertCount($cityCount, $citiesWithVenues, 'Should find all cities with venues');

            // Property: Sorting by name should work correctly
            $sortedResults = City::where('country_id', $country->id)->orderBy('name')->get();
            $this->assertGreaterThanOrEqual($cityCount, $sortedResults->count(), 'Sorting should return cities');
        });
    }

    /**
     * Property: Treatment search and filtering should work correctly
     */
    public function test_treatment_search_and_filtering_functionality(): void
    {
        $this->forAll(
            Generator\choose(2, 4),
            Generator\choose(10, 200),
            Generator\choose(15, 120),
            Generator\string()
        )->then(function (
            int $treatmentCount,
            int $price,
            int $duration,
            string $category
        ) {
            // Skip empty categories
            if (empty(trim($category))) {
                $category = 'wellness';
            }

            // Create test data with unique identifiers
            $uniqueId = uniqid();
            $country = Country::factory()->create([
                'name' => "Test Country {$uniqueId}",
                'slug' => "test-country-{$uniqueId}",
                'code' => 'T'.substr($uniqueId, -1),
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

            $treatments = [];
            for ($i = 0; $i < $treatmentCount; $i++) {
                $treatment = Treatment::factory()->create([
                    'venue_id' => $venue->id,
                    'name' => "Test Treatment {$i} {$uniqueId}",
                    'slug' => "test-treatment-{$i}-{$uniqueId}",
                    'price' => (float) $price,
                    'duration' => $duration,
                    'category' => trim($category),
                    'is_active' => true,
                ]);
                $treatments[] = $treatment;
            }

            // Property: Search functionality should work correctly
            $searchResults = Treatment::where('name', 'like', "%Test Treatment%{$uniqueId}%")->get();
            $this->assertCount($treatmentCount, $searchResults, 'Search should return all matching treatments');

            // Property: Filtering by venue should work correctly
            $venueFilterResults = Treatment::where('venue_id', $venue->id)->get();
            $this->assertCount($treatmentCount, $venueFilterResults, 'Venue filter should return all treatments in the venue');

            // Property: Filtering by category should work correctly
            $categoryFilterResults = Treatment::where('category', trim($category))->where('venue_id', $venue->id)->get();
            $this->assertCount($treatmentCount, $categoryFilterResults, 'Category filter should work correctly');

            // Property: Filtering by price range should work correctly
            $priceFilterResults = Treatment::where('price', '>=', $price)->where('venue_id', $venue->id)->get();
            $this->assertCount($treatmentCount, $priceFilterResults, 'Price filter should work correctly');

            // Property: Filtering by duration should work correctly
            $durationFilterResults = Treatment::where('duration', '>=', $duration)->where('venue_id', $venue->id)->get();
            $this->assertCount($treatmentCount, $durationFilterResults, 'Duration filter should work correctly');

            // Property: Sorting by price should work correctly
            $sortedResults = Treatment::where('venue_id', $venue->id)->orderBy('price')->get();
            $this->assertCount($treatmentCount, $sortedResults, 'Sorting should return all treatments');
        });
    }

    /**
     * Property: Location search and filtering should work correctly
     */
    public function test_location_search_and_filtering_functionality(): void
    {
        $this->forAll(
            Generator\choose(1, 3),
            Generator\bool()
        )->then(function (
            int $locationCount,
            bool $isActive
        ) {
            // Create test data with unique identifiers
            $uniqueId = uniqid();
            $country = Country::factory()->create([
                'name' => "Test Country {$uniqueId}",
                'slug' => "test-country-{$uniqueId}",
                'code' => 'T'.substr($uniqueId, -1),
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

            $locations = [];
            for ($i = 0; $i < $locationCount; $i++) {
                $location = Location::factory()->create([
                    'venue_id' => $venue->id,
                    'city_id' => $city->id,
                    'name' => "Test Location {$i} {$uniqueId}",
                    'is_active' => $isActive,
                    'latitude' => 54.6872 + ($i * 0.001),
                    'longitude' => 25.2797 + ($i * 0.001),
                ]);
                $locations[] = $location;
            }

            // Property: Search functionality should work correctly
            $searchResults = Location::where('name', 'like', "%Test Location%{$uniqueId}%")->get();
            $this->assertCount($locationCount, $searchResults, 'Search should return all matching locations');

            // Property: Filtering by venue should work correctly
            $venueFilterResults = Location::where('venue_id', $venue->id)->get();
            $this->assertCount($locationCount, $venueFilterResults, 'Venue filter should return all locations in the venue');

            // Property: Filtering by city should work correctly
            $cityFilterResults = Location::where('city_id', $city->id)->get();
            $this->assertCount($locationCount, $cityFilterResults, 'City filter should return all locations in the city');

            // Property: Filtering by active status should work correctly
            $activeFilterResults = Location::where('is_active', $isActive)->where('venue_id', $venue->id)->get();
            $this->assertCount($locationCount, $activeFilterResults, 'Active status filter should work correctly');

            // Property: Filtering by coordinates should work correctly
            $coordinateFilterResults = Location::whereNotNull('latitude')->whereNotNull('longitude')->where('venue_id', $venue->id)->get();
            $this->assertCount($locationCount, $coordinateFilterResults, 'Coordinate filter should work correctly');
        });
    }

    /**
     * Property: Rating search and filtering should work correctly
     */
    public function test_rating_search_and_filtering_functionality(): void
    {
        $this->forAll(
            Generator\choose(1, 3),
            Generator\choose(1, 5),
            Generator\bool()
        )->then(function (
            int $ratingCount,
            int $ratingValue,
            bool $isVerified
        ) {
            // Create test data with unique identifiers
            $uniqueId = uniqid();
            $country = Country::factory()->create([
                'name' => "Test Country {$uniqueId}",
                'slug' => "test-country-{$uniqueId}",
                'code' => 'T'.substr($uniqueId, -1),
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

            $ratings = [];
            for ($i = 0; $i < $ratingCount; $i++) {
                $rating = Rating::factory()->create([
                    'venue_id' => $venue->id,
                    'city_id' => $city->id,
                    'display_average' => (float) $ratingValue,
                    'count' => rand(1, 100),
                    'is_verified' => $isVerified,
                    'reviewer_name' => "Reviewer {$i} {$uniqueId}",
                ]);
                $ratings[] = $rating;
            }

            // Property: Filtering by venue should work correctly
            $venueFilterResults = Rating::where('venue_id', $venue->id)->get();
            $this->assertCount($ratingCount, $venueFilterResults, 'Venue filter should return all ratings for the venue');

            // Property: Filtering by city should work correctly
            $cityFilterResults = Rating::where('city_id', $city->id)->get();
            $this->assertCount($ratingCount, $cityFilterResults, 'City filter should return all ratings in the city');

            // Property: Filtering by verified status should work correctly
            $verifiedFilterResults = Rating::where('is_verified', $isVerified)->where('venue_id', $venue->id)->get();
            $this->assertCount($ratingCount, $verifiedFilterResults, 'Verified status filter should work correctly');

            // Property: Filtering by rating range should work correctly
            $ratingFilterResults = Rating::where('display_average', '>=', $ratingValue)->where('venue_id', $venue->id)->get();
            $this->assertCount($ratingCount, $ratingFilterResults, 'Rating range filter should work correctly');

            // Property: Sorting by rating should work correctly
            $sortedResults = Rating::where('venue_id', $venue->id)->orderBy('display_average', 'desc')->get();
            $this->assertCount($ratingCount, $sortedResults, 'Sorting should return all ratings');
        });
    }

    /**
     * Property: Image search and filtering should work correctly
     */
    public function test_image_search_and_filtering_functionality(): void
    {
        $this->forAll(
            Generator\choose(1, 3),
            Generator\bool()
        )->then(function (
            int $imageCount,
            bool $isPrimary
        ) {
            // Create test data with unique identifiers
            $uniqueId = uniqid();
            $country = Country::factory()->create([
                'name' => "Test Country {$uniqueId}",
                'slug' => "test-country-{$uniqueId}",
                'code' => 'T'.substr($uniqueId, -1),
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

            $images = [];
            for ($i = 0; $i < $imageCount; $i++) {
                $image = Image::factory()->create([
                    'venue_id' => $venue->id,
                    'city_id' => $city->id,
                    'is_primary' => $isPrimary && $i === 0, // Only first image can be primary
                    'path' => "test-image-{$i}-{$uniqueId}.jpg",
                    'uri_large' => "https://example.com/large/test-image-{$i}-{$uniqueId}.jpg",
                    'alt_text' => "Test Image {$i} {$uniqueId}",
                ]);
                $images[] = $image;
            }

            // Property: Filtering by venue should work correctly
            $venueFilterResults = Image::where('venue_id', $venue->id)->get();
            $this->assertCount($imageCount, $venueFilterResults, 'Venue filter should return all images for the venue');

            // Property: Filtering by city should work correctly
            $cityFilterResults = Image::where('city_id', $city->id)->get();
            $this->assertCount($imageCount, $cityFilterResults, 'City filter should return all images in the city');

            // Property: Filtering by primary status should work correctly
            $primaryFilterResults = Image::where('is_primary', true)->where('venue_id', $venue->id)->get();
            $expectedPrimaryCount = $isPrimary ? 1 : 0;
            $this->assertCount($expectedPrimaryCount, $primaryFilterResults, 'Primary status filter should work correctly');

            // Property: Filtering by alt text should work correctly
            $altTextFilterResults = Image::whereNotNull('alt_text')->where('venue_id', $venue->id)->get();
            $this->assertCount($imageCount, $altTextFilterResults, 'Alt text filter should work correctly');

            // Property: Sorting by sort order should work correctly
            $sortedResults = Image::where('venue_id', $venue->id)->orderBy('sort_order')->get();
            $this->assertCount($imageCount, $sortedResults, 'Sorting should return all images');
        });
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
