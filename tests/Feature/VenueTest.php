<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that venues page loads successfully.
     */
    public function test_venues_page_loads_successfully(): void
    {
        $response = $this->get(route('venues.index'));
        $response->assertStatus(200, 'The venues page should return a 200 OK status');
    }

    /**
     * Test that dashboard page loads successfully.
     */
    public function test_dashboard_page_loads_successfully(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertStatus(200, 'The dashboard page should return a 200 OK status');
    }

    /**
     * Test that admin page loads successfully.
     */
    public function test_admin_page_loads_successfully(): void
    {
        $response = $this->get(route('admin.index'));
        $response->assertStatus(200, 'The admin page should return a 200 OK status');
    }

    /**
     * Test that venue model can be created along with related models.
     */
    public function test_venue_model_can_be_created(): void
    {
        // Create a country first
        $country = Country::create([
            'name' => 'Lithuania',
            'code' => 'LT',
            'normalised_name' => 'lithuania',
            'active' => true,
        ]);

        // Create a city
        $city = City::create([
            'country_id' => $country->id,
            'name' => 'Vilnius',
            'slug' => 'vilnius',
            'normalised_name' => 'vilnius-lt',
            'entity_id' => 'test123',
        ]);

        // Create a venue
        $venue = Venue::create([
            'external_id' => 12345,
            'name' => 'Test Venue',
            'slug' => 'test-venue',
            'url' => 'https://example.com/test-venue',
            'source' => 'test',
            'description' => 'Test description',
            'type_name' => 'Test Type',
            'normalised_name' => 'test-venue',
            'desktop_uri' => 'https://example.com/test-venue',
            'mobile_uri' => 'https://example.com/test-venue',
            'is_new_venue' => false,
        ]);

        $this->assertDatabaseHas('venues', [
            'name' => 'Test Venue',
        ]);

        $this->assertDatabaseHas('countries', [
            'name' => 'Lithuania',
        ]);

        $this->assertDatabaseHas('cities', [
            'name' => 'Vilnius',
        ]);
    }

    /**
     * Test that API endpoints return proper JSON structure.
     */
    public function test_api_endpoints_return_json(): void
    {
        $response = $this->getJson('/api/venues');
        $response->assertStatus(200, 'The venues API endpoint should return a 200 OK status');
        $response->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);

        $response = $this->getJson('/api/stats');
        $response->assertStatus(200, 'The stats API endpoint should return a 200 OK status');
        $response->assertJsonStructure([
            'total_venues',
            'total_cities',
        ]);
    }
}
