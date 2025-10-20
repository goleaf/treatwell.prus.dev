<?php

namespace Tests\Unit\Models;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test location has required fillable attributes.
     *
     * @return void
     */
    public function test_location_has_fillable_attributes(): void
    {
        $location = new Location();
        
        $this->assertContains('venue_id', $location->getFillable());
        $this->assertContains('city_id', $location->getFillable());
        $this->assertContains('postal_code', $location->getFillable());
        $this->assertContains('address_line1', $location->getFillable());
        $this->assertContains('address_line2', $location->getFillable());
        $this->assertContains('latitude', $location->getFillable());
        $this->assertContains('longitude', $location->getFillable());
    }

    /**
     * Test location belongs to a venue.
     *
     * @return void
     */
    public function test_location_belongs_to_venue(): void
    {
        $location = new Location();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $location->venue());
    }

    /**
     * Test location belongs to a city.
     *
     * @return void
     */
    public function test_location_belongs_to_city(): void
    {
        $location = new Location();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $location->city());
    }

    /**
     * Test creating a location with venue and city.
     *
     * @return void
     */
    public function test_creating_location_with_relationships(): void
    {
        // Create a country
        $country = Country::factory()->create([
            'name' => 'Lithuania',
            'code' => 'LT'
        ]);
        
        // Create a city
        $city = City::factory()->create([
            'country_id' => $country->id,
            'name' => 'Vilnius'
        ]);
        
        // Create a venue
        $venue = Venue::factory()->create([
            'name' => 'Test Salon'
        ]);
        
        // Create a location
        $location = Location::factory()->create([
            'venue_id' => $venue->id,
            'city_id' => $city->id,
            'postal_code' => 'LT-01234',
            'address_line1' => 'Gedimino pr. 1',
            'address_line2' => 'Vilnius',
            'latitude' => 54.687156,
            'longitude' => 25.279651
        ]);
        
        // Retrieve the location from the database
        $dbLocation = Location::find($location->id);
        
        // Test relationships
        $this->assertInstanceOf(Venue::class, $dbLocation->venue);
        $this->assertEquals('Test Salon', $dbLocation->venue->name);
        
        $this->assertInstanceOf(City::class, $dbLocation->city);
        $this->assertEquals('Vilnius', $dbLocation->city->name);
        
        // Test location attributes
        $this->assertEquals('LT-01234', $dbLocation->postal_code);
        $this->assertEquals('Gedimino pr. 1', $dbLocation->address_line1);
        $this->assertEquals('Vilnius', $dbLocation->address_line2);
        $this->assertEquals(54.687156, $dbLocation->latitude);
        $this->assertEquals(25.279651, $dbLocation->longitude);
    }
} 