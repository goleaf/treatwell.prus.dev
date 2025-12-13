<?php

namespace Tests\Unit;

use App\Models\City;
use App\Models\Venue;
use App\Models\Treatment;
use App\Models\Service;
use App\Models\Image;
use App\Models\Rating;
use App\Models\OpeningHour;
use App\Models\Location;
use App\Models\Country;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CityRelationshipsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_city_has_proper_relationships(): void
    {
        // Create a country first
        $country = Country::factory()->create();
        
        // Create a city
        $city = City::factory()->create([
            'country_id' => $country->id,
            'name' => 'Test City',
            'slug' => 'test-city-' . uniqid(),
        ]);

        // Create a venue in the city
        $venue = Venue::factory()->create([
            'city_id' => $city->id,
            'name' => 'Test Venue',
        ]);

        // Create related entities
        $service = Service::factory()->create([
            'venue_id' => $venue->id,
            'city_id' => $city->id,
            'name' => 'Test Service',
        ]);

        $image = Image::factory()->create([
            'venue_id' => $venue->id,
            'city_id' => $city->id,
            'path' => 'test-image.jpg',
        ]);

        $rating = Rating::factory()->create([
            'venue_id' => $venue->id,
            'city_id' => $city->id,
            'rating' => 4.5,
        ]);

        $openingHour = OpeningHour::factory()->create([
            'venue_id' => $venue->id,
            'city_id' => $city->id,
            'day_of_week' => 'Monday',
        ]);

        $location = Location::factory()->create([
            'venue_id' => $venue->id,
            'city_id' => $city->id,
            'name' => 'Test Location',
        ]);

        $treatment = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Test Treatment',
        ]);

        // Attach treatment to city
        $city->treatments()->attach($treatment->id);

        // Test city relationships
        $this->assertTrue($city->venues->contains($venue));
        $this->assertTrue($city->services->contains($service));
        $this->assertTrue($city->images->contains($image));
        $this->assertTrue($city->ratings->contains($rating));
        $this->assertTrue($city->openingHours->contains($openingHour));
        $this->assertTrue($city->locations->contains($location));
        $this->assertTrue($city->treatments->contains($treatment));

        // Test reverse relationships
        $this->assertEquals($city->id, $venue->city_id);
        $this->assertEquals($city->id, $service->city_id);
        $this->assertEquals($city->id, $image->city_id);
        $this->assertEquals($city->id, $rating->city_id);
        $this->assertEquals($city->id, $openingHour->city_id);
        $this->assertEquals($city->id, $location->city_id);
        $this->assertTrue($treatment->cities->contains($city));

        // Test city helper methods
        $this->assertEquals(1, $city->getTotalServicesCount());
        $this->assertEquals(1, $city->getTotalTreatmentsCount());
        $this->assertGreaterThan(0, $city->getDataCompleteness());
    }

    public function test_venue_sync_related_data_with_city(): void
    {
        // Create a country first
        $country = Country::factory()->create();
        
        // Create a city
        $city = City::factory()->create([
            'country_id' => $country->id,
            'name' => 'Test City',
            'slug' => 'test-city-' . uniqid(),
        ]);

        // Create a venue
        $venue = Venue::factory()->create([
            'city_id' => $city->id,
            'name' => 'Test Venue',
        ]);

        // Create related entities without city_id initially
        $service = Service::factory()->create([
            'venue_id' => $venue->id,
            'city_id' => null, // Initially null
            'name' => 'Test Service',
        ]);

        $image = Image::factory()->create([
            'venue_id' => $venue->id,
            'city_id' => null, // Initially null
            'path' => 'test-image.jpg',
        ]);

        $treatment = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'name' => 'Test Treatment',
        ]);

        // Sync related data with city
        $venue->syncRelatedDataWithCity();

        // Refresh models
        $service->refresh();
        $image->refresh();

        // Verify city_id was set
        $this->assertEquals($city->id, $service->city_id);
        $this->assertEquals($city->id, $image->city_id);
        $this->assertTrue($city->treatments->contains($treatment));
    }

    public function test_city_sync_venue_related_data(): void
    {
        // Create a country first
        $country = Country::factory()->create();
        
        // Create a city
        $city = City::factory()->create([
            'country_id' => $country->id,
            'name' => 'Test City',
            'slug' => 'test-city-' . uniqid(),
        ]);

        // Create venues
        $venue1 = Venue::factory()->create([
            'city_id' => $city->id,
            'name' => 'Test Venue 1',
        ]);

        $venue2 = Venue::factory()->create([
            'city_id' => $city->id,
            'name' => 'Test Venue 2',
        ]);

        // Create services without city_id
        $service1 = Service::factory()->create([
            'venue_id' => $venue1->id,
            'city_id' => null,
            'name' => 'Test Service 1',
        ]);

        $service2 = Service::factory()->create([
            'venue_id' => $venue2->id,
            'city_id' => null,
            'name' => 'Test Service 2',
        ]);

        // Create treatments
        $treatment1 = Treatment::factory()->create([
            'venue_id' => $venue1->id,
            'name' => 'Test Treatment 1',
        ]);

        $treatment2 = Treatment::factory()->create([
            'venue_id' => $venue2->id,
            'name' => 'Test Treatment 2',
        ]);

        // Sync all venue-related data
        $city->syncVenueRelatedData();

        // Refresh models
        $service1->refresh();
        $service2->refresh();

        // Verify all services now have city_id
        $this->assertEquals($city->id, $service1->city_id);
        $this->assertEquals($city->id, $service2->city_id);

        // Verify treatments are synced with city
        $this->assertTrue($city->treatments->contains($treatment1));
        $this->assertTrue($city->treatments->contains($treatment2));
    }
}