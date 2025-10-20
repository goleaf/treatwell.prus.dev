<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a country for proper foreign key references
        Country::factory()->create([
            'code' => 'LT',
            'name' => 'Lithuania',
        ]);
    }

    public function test_dashboard_page_loads()
    {
        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
    }

    public function test_venues_index_page_loads()
    {
        $response = $this->get(route('venues.index'));
        $response->assertStatus(200);
        $response->assertViewIs('venues.index');
    }

    public function test_venue_show_page_loads()
    {
        $venue = Venue::factory()->create();
        $response = $this->get(route('venues.show', $venue));
        $response->assertStatus(200);
        $response->assertViewIs('venues.show');
    }

    public function test_venues_by_city_page_loads()
    {
        $city = City::factory()->create();
        $response = $this->get(route('venues.by-city', $city));
        $response->assertStatus(200);
        $response->assertViewIs('venues.index');
    }

    public function test_admin_index_page_loads()
    {
        $response = $this->get(route('admin.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.index');
    }

    public function test_api_docs_page_loads()
    {
        $response = $this->get(route('api.docs'));
        $response->assertStatus(200);
        $response->assertViewIs('api-docs');
    }

    public function test_venues_search_works()
    {
        $venue1 = Venue::factory()->create(['name' => 'Test Salon 1']);
        $venue2 = Venue::factory()->create(['name' => 'Different Spa']);

        $response = $this->get(route('venues.index', ['search' => 'Salon']));
        $response->assertStatus(200);
        $response->assertSee('Test Salon 1');
        $response->assertDontSee('Different Spa');
    }

    public function test_venues_filtering_by_city_works()
    {
        // Create cities directly with country relationship
        $city1 = City::factory()->create();
        $city2 = City::factory()->create();

        // Create venues
        $venue1 = Venue::factory()->create(['name' => 'Venue in City 1']);
        $venue2 = Venue::factory()->create(['name' => 'Venue in City 2']);

        // Create locations with city relationships
        Location::factory()->create([
            'venue_id' => $venue1->id,
            'city_id' => $city1->id,
        ]);

        Location::factory()->create([
            'venue_id' => $venue2->id,
            'city_id' => $city2->id,
        ]);

        $response = $this->get(route('venues.index', ['city_id' => $city1->id]));
        $response->assertStatus(200);
        $response->assertSee('Venue in City 1');
        $response->assertDontSee('Venue in City 2');
    }
}
