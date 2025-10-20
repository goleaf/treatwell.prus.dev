<?php

namespace Tests\Feature\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\Rating;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a country for foreign key references
        $this->country = Country::factory()->create([
            'name' => 'Lithuania',
            'code' => 'LT',
        ]);
    }

    /**
     * Test the index method displays venues correctly.
     */
    public function test_index_displays_venues(): void
    {
        // Create some venues
        Venue::factory()->count(5)->create();

        // Access the venues index page
        $response = $this->get(route('venues.index'));

        $response->assertStatus(200);
        $response->assertViewIs('venues.index');
        $response->assertViewHas('venues');
    }

    /**
     * Test the index method with search filter.
     */
    public function test_index_with_search_filter(): void
    {
        // Create venues with specific names
        Venue::factory()->create(['name' => 'Test Salon']);
        Venue::factory()->create(['name' => 'Another Salon']);
        Venue::factory()->create(['name' => 'Test Spa']);

        // Search for venues containing "Test"
        $response = $this->get(route('venues.index', ['search' => 'Test']));

        $response->assertStatus(200);
        $response->assertViewIs('venues.index');
        $response->assertViewHas('venues');

        // Get the venues from the response
        $venues = $response->viewData('venues');

        // Assert that only venues with "Test" in the name are returned
        $this->assertTrue($venues->contains('name', 'Test Salon'));
        $this->assertTrue($venues->contains('name', 'Test Spa'));
        $this->assertFalse($venues->contains('name', 'Another Salon'));
    }

    /**
     * Test the index method with type filter.
     */
    public function test_index_with_type_filter(): void
    {
        // Create venues with specific types
        Venue::factory()->create(['name' => 'Test Salon 1', 'type_name' => 'Spa']);
        Venue::factory()->create(['name' => 'Test Salon 2', 'type_name' => 'Salon']);
        Venue::factory()->create(['name' => 'Test Salon 3', 'type_name' => 'Spa']);

        // Filter venues by type "Spa"
        $response = $this->get(route('venues.index', ['type' => 'Spa']));

        $response->assertStatus(200);
        $response->assertViewIs('venues.index');

        // Get the venues from the response
        $venues = $response->viewData('venues');

        // Assert that only venues with type "Spa" are returned
        $this->assertTrue($venues->contains('name', 'Test Salon 1'));
        $this->assertTrue($venues->contains('name', 'Test Salon 3'));
        $this->assertFalse($venues->contains('name', 'Test Salon 2'));
    }

    /**
     * Test the showBySlug method displays a venue correctly.
     */
    public function test_show_by_slug_displays_venue(): void
    {
        // Create a venue with a specific slug
        $venue = Venue::factory()->create([
            'name' => 'Test Venue',
            'normalised_name' => 'test-venue',
        ]);

        // Access the venue's show page by slug
        $response = $this->get(route('venues.show', ['slug' => 'test-venue']));

        $response->assertStatus(200);
        $response->assertViewIs('venues.show');
        $response->assertViewHas('venue', $venue);
    }

    /**
     * Test the showBySlug method returns 404 for non-existent venue.
     */
    public function test_show_by_slug_returns_404_for_nonexistent_venue(): void
    {
        // Access a non-existent venue's show page
        $response = $this->get(route('venues.show', ['slug' => 'non-existent-venue']));

        $response->assertStatus(404);
    }

    /**
     * Test the byCity method displays venues for a city.
     */
    public function test_by_city_displays_venues_for_city(): void
    {
        // Create a city
        $city = City::factory()->create([
            'country_id' => $this->country->id,
            'name' => 'Vilnius',
            'is_main_city' => true,
        ]);

        // Create venues
        $venue1 = Venue::factory()->create(['name' => 'Venue in Vilnius']);
        $venue2 = Venue::factory()->create(['name' => 'Venue not in Vilnius']);

        // Create locations to associate venues with cities
        Location::factory()->create([
            'venue_id' => $venue1->id,
            'city_id' => $city->id,
        ]);

        // Access venues by city page
        $response = $this->get(route('venues.city', ['city' => $city->id]));

        $response->assertStatus(200);
        $response->assertViewIs('venues.index');
        $response->assertViewHas('venues');
        $response->assertViewHas('city', $city);

        // Get the venues from the response
        $venues = $response->viewData('venues');

        // Assert that only venues in the city are returned
        $this->assertTrue($venues->contains('id', $venue1->id));
        $this->assertFalse($venues->contains('id', $venue2->id));
    }

    /**
     * Test the byCity method includes subregions when requested.
     */
    public function test_by_city_includes_subregions(): void
    {
        // Create a main city
        $mainCity = City::factory()->create([
            'country_id' => $this->country->id,
            'name' => 'Vilnius',
            'is_main_city' => true,
        ]);

        // Create a subregion
        $subregion = City::factory()->create([
            'country_id' => $this->country->id,
            'name' => 'Old Town, Vilnius',
            'is_main_city' => false,
            'main_city_id' => $mainCity->id,
            'subregion' => 'Old Town',
        ]);

        // Create venues
        $venueMain = Venue::factory()->create(['name' => 'Venue in Vilnius']);
        $venueSubregion = Venue::factory()->create(['name' => 'Venue in Old Town']);

        // Create locations
        Location::factory()->create([
            'venue_id' => $venueMain->id,
            'city_id' => $mainCity->id,
        ]);

        Location::factory()->create([
            'venue_id' => $venueSubregion->id,
            'city_id' => $subregion->id,
        ]);

        // Access venues by city page including subregions
        $response = $this->get(route('venues.city', [
            'city' => $mainCity->id,
            'include_subregions' => true,
        ]));

        $response->assertStatus(200);

        // Get the venues from the response
        $venues = $response->viewData('venues');

        // Assert that venues from both main city and subregion are returned
        $this->assertTrue($venues->contains('id', $venueMain->id));
        $this->assertTrue($venues->contains('id', $venueSubregion->id));
    }

    /**
     * Test the byCity method filters by subregion when requested.
     */
    public function test_by_city_filters_by_subregion(): void
    {
        // Create a main city
        $mainCity = City::factory()->create([
            'country_id' => $this->country->id,
            'name' => 'Vilnius',
            'is_main_city' => true,
        ]);

        // Create subregions
        $subregion1 = City::factory()->create([
            'country_id' => $this->country->id,
            'name' => 'Old Town, Vilnius',
            'is_main_city' => false,
            'main_city_id' => $mainCity->id,
            'subregion' => 'Old Town',
        ]);

        $subregion2 = City::factory()->create([
            'country_id' => $this->country->id,
            'name' => 'New Town, Vilnius',
            'is_main_city' => false,
            'main_city_id' => $mainCity->id,
            'subregion' => 'New Town',
        ]);

        // Create venues
        $venueOldTown = Venue::factory()->create(['name' => 'Venue in Old Town']);
        $venueNewTown = Venue::factory()->create(['name' => 'Venue in New Town']);

        // Create locations
        Location::factory()->create([
            'venue_id' => $venueOldTown->id,
            'city_id' => $subregion1->id,
        ]);

        Location::factory()->create([
            'venue_id' => $venueNewTown->id,
            'city_id' => $subregion2->id,
        ]);

        // Access venues by city page filtering by subregion
        $response = $this->get(route('venues.city', [
            'city' => $mainCity->id,
            'subregion' => 'Old Town',
        ]));

        $response->assertStatus(200);

        // Get the venues from the response
        $venues = $response->viewData('venues');

        // Assert that only venues from the specified subregion are returned
        $this->assertTrue($venues->contains('id', $venueOldTown->id));
        $this->assertFalse($venues->contains('id', $venueNewTown->id));
    }

    /**
     * Test the dashboard method displays statistics correctly.
     */
    public function test_dashboard_displays_statistics(): void
    {
        // Create venues and cities
        $venues = Venue::factory()->count(3)->create();
        $cities = City::factory()->count(2)->create([
            'country_id' => $this->country->id,
        ]);

        // Create ratings for venues
        foreach ($venues as $index => $venue) {
            Rating::factory()->create([
                'venue_id' => $venue->id,
                'weighted_average' => 5 - $index, // First venue has highest rating
            ]);
        }

        // Create locations to associate venues with cities
        foreach ($venues as $index => $venue) {
            Location::factory()->create([
                'venue_id' => $venue->id,
                'city_id' => $cities[$index % 2]->id, // Distribute venues between cities
            ]);
        }

        // Access dashboard page
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('totalVenues', 3);
        $response->assertViewHas('totalCities', 2);
        $response->assertViewHas('topRatedVenues');
        $response->assertViewHas('citiesWithMostVenues');

        // Get the top rated venues from the response
        $topRatedVenues = $response->viewData('topRatedVenues');

        // Assert that venues are ordered by rating
        $this->assertEquals($venues[0]->id, $topRatedVenues->first()->id);
    }
}
