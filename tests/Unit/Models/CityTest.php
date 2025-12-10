<?php

namespace Tests\Unit\Models;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test city has required fillable attributes.
     */
    public function test_city_has_fillable_attributes(): void
    {
        $city = new City;

        $this->assertContains('country_id', $city->getFillable());
        $this->assertContains('name', $city->getFillable());
        $this->assertContains('normalised_name', $city->getFillable());
        $this->assertContains('entity_id', $city->getFillable());
        $this->assertContains('is_main_city', $city->getFillable());
    }

    /**
     * Test city belongs to a country.
     */
    public function test_city_belongs_to_country(): void
    {
        $city = new City;

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $city->country());
    }

    /**
     * Test city has many locations.
     */
    public function test_city_has_many_locations(): void
    {
        $city = new City;

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $city->locations());
    }

    /**
     * Test city belongs to a main city if it's a subregion.
     */
    public function test_city_belongs_to_main_city(): void
    {
        $city = new City;

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $city->mainCity());
    }

    /**
     * Test main city has many subregions.
     */
    public function test_city_has_many_subregions(): void
    {
        $city = new City;

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $city->subregions());
    }

    /**
     * Test with most venues scope works correctly.
     */
    public function test_with_most_venues_scope(): void
    {
        // Create country
        $country = Country::factory()->create();

        // Create cities
        $city1 = City::factory()->create(['country_id' => $country->id]);
        $city2 = City::factory()->create(['country_id' => $country->id]);

        // Create venues
        $venue1 = Venue::factory()->create();
        $venue2 = Venue::factory()->create();
        $venue3 = Venue::factory()->create();

        // Create locations that link venues to cities
        Location::factory()->create([
            'city_id' => $city1->id,
            'venue_id' => $venue1->id,
        ]);

        Location::factory()->create([
            'city_id' => $city1->id,
            'venue_id' => $venue2->id,
        ]);

        Location::factory()->create([
            'city_id' => $city2->id,
            'venue_id' => $venue3->id,
        ]);

        $topCities = City::withMostVenues(2)->get();

        $this->assertEquals(2, $topCities->count());
        $this->assertEquals($city1->id, $topCities[0]->id);
        $this->assertEquals(2, $topCities[0]->locations_count);
        $this->assertEquals(1, $topCities[1]->locations_count);
    }

    /**
     * Test main cities scope returns only main cities.
     */
    public function test_main_cities_scope(): void
    {
        // Create country
        $country = Country::factory()->create();

        // Create main city
        $mainCity = City::factory()->create([
            'country_id' => $country->id,
            'is_main_city' => true,
        ]);

        // Create subregion
        $subregion = City::factory()->create([
            'country_id' => $country->id,
            'is_main_city' => false,
            'main_city_id' => $mainCity->id,
        ]);

        $mainCities = City::mainCities()->get();

        $this->assertEquals(1, $mainCities->count());
        $this->assertEquals($mainCity->id, $mainCities[0]->id);
    }

    /**
     * Test get all venues method includes venues from subregions.
     */
    public function test_get_all_venues_includes_subregion_venues(): void
    {
        // Create country
        $country = Country::factory()->create();

        // Create main city
        $mainCity = City::factory()->create([
            'country_id' => $country->id,
            'is_main_city' => true,
        ]);

        // Create subregion
        $subregion = City::factory()->create([
            'country_id' => $country->id,
            'is_main_city' => false,
            'main_city_id' => $mainCity->id,
        ]);

        // Create venues
        $venueMainCity = Venue::factory()->create();
        $venueSubregion = Venue::factory()->create();

        // Create locations that link venues to cities
        Location::factory()->create([
            'city_id' => $mainCity->id,
            'venue_id' => $venueMainCity->id,
        ]);

        Location::factory()->create([
            'city_id' => $subregion->id,
            'venue_id' => $venueSubregion->id,
        ]);

        $allVenues = $mainCity->getAllVenues()->get();

        $this->assertEquals(2, $allVenues->count());
        $this->assertTrue($allVenues->contains('id', $venueMainCity->id));
        $this->assertTrue($allVenues->contains('id', $venueSubregion->id));
    }

    /**
     * Test extract slug from URL method works correctly.
     */
    public function test_extract_slug_from_url(): void
    {
        $url = 'https://www.treatwell.lt/salonai/kur-vilnius-lt/';

        $slug = City::extractSlugFromUrl($url);

        $this->assertEquals('vilnius-lt', $slug);
    }

    public function test_country(): void
    {
        $this->markTestIncomplete('Test for country needs implementation');
    }

    public function test_locations(): void
    {
        $this->markTestIncomplete('Test for locations needs implementation');
    }

    public function test_main_city(): void
    {
        $this->markTestIncomplete('Test for mainCity needs implementation');
    }

    public function test_subregions(): void
    {
        $this->markTestIncomplete('Test for subregions needs implementation');
    }

    public function test_scope_main_cities(): void
    {
        $this->markTestIncomplete('Test for scopeMainCities needs implementation');
    }

    public function test_venues(): void
    {
        $this->markTestIncomplete('Test for venues needs implementation');
    }

    public function test_procedures(): void
    {
        $this->markTestIncomplete('Test for procedures needs implementation');
    }

    public function test_treatments(): void
    {
        $this->markTestIncomplete('Test for treatments needs implementation');
    }
}
