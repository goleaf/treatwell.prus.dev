<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a country for proper foreign key references
        Country::factory()->create([
            'code' => 'LT',
            'name' => 'Lithuania',
        ]);
    }

    /**
     * Test that the venues index API returns venues correctly.
     *
     * @return void
     */
    public function test_venues_index_api_returns_venues()
    {
        $venue = Venue::factory()->create();

        $response = $this->getJson('/api/venues');

        $response->assertStatus(200, 'The venues API should return a 200 status code');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                ],
            ],
            'links',
            'meta',
        ], 'The venues API response should have the correct JSON structure');
        $response->assertJsonCount(1, 'data', 'The venues API should return exactly 1 venue');
    }

    /**
     * Test that the venue show API returns venue details correctly.
     *
     * @return void
     */
    public function test_venue_show_api_returns_venue_details()
    {
        $venue = Venue::factory()->create();

        $response = $this->getJson("/api/venues/{$venue->id}");

        $response->assertStatus(200, 'The venue details API should return a 200 status code');
        $response->assertJson([
            'id' => $venue->id,
            'name' => $venue->name,
        ], 'The venue details API should return the correct venue data');
    }

    /**
     * Test that the cities API returns cities correctly.
     *
     * @return void
     */
    public function test_cities_api_returns_cities()
    {
        $city = City::factory()->create();

        $response = $this->getJson('/api/cities');

        $response->assertStatus(200, 'The cities API should return a 200 status code');
        $response->assertJsonFragment([
            'id' => $city->id,
            'name' => $city->name,
        ], 'The cities API should include the created city in the response');
    }

    /**
     * Test that the city venues API returns venues in a specific city.
     *
     * @return void
     */
    public function test_city_venues_api_returns_venues_in_city()
    {
        $city = City::factory()->create();
        $venue = Venue::factory()->create();

        // Create location with city relationship
        Location::factory()->create([
            'venue_id' => $venue->id,
            'city_id' => $city->id,
        ]);

        $response = $this->getJson("/api/cities/{$city->id}/venues");

        $response->assertStatus(200, 'The city venues API should return a 200 status code');
        $response->assertJsonFragment([
            'id' => $venue->id,
            'name' => $venue->name,
        ], 'The city venues API should include venues for the specified city');
    }

    public function test_venue_types_api_returns_types()
    {
        Venue::factory()->create(['type_name' => 'Spa']);
        Venue::factory()->create(['type_name' => 'Hair Salon']);

        $response = $this->getJson('/api/types');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['Spa']);
        $response->assertJsonFragment(['Hair Salon']);
    }

    public function test_stats_api_returns_statistics()
    {
        Venue::factory()->count(3)->create();
        City::factory()->count(2)->create();

        $response = $this->getJson('/api/stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_venues',
            'total_cities',
            'top_rated_venues',
            'cities_with_most_venues',
        ]);
        $response->assertJson([
            'total_venues' => 3,
            'total_cities' => 2,
        ]);
    }

    public function test_treatment_categories_api_returns_categories()
    {
        $venue = Venue::factory()->create();
        Treatment::factory()->create([
            'venue_id' => $venue->id,
            'category_name' => 'Massage',
        ]);
        Treatment::factory()->create([
            'venue_id' => $venue->id,
            'category_name' => 'Haircut',
        ]);

        $response = $this->getJson('/api/treatments/categories');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['Massage']);
        $response->assertJsonFragment(['Haircut']);
    }

    public function test_venues_by_treatment_api_returns_venues()
    {
        $venue1 = Venue::factory()->create(['name' => 'Massage Salon']);
        $venue2 = Venue::factory()->create(['name' => 'Hair Salon']);

        Treatment::factory()->create([
            'venue_id' => $venue1->id,
            'category_name' => 'Massage',
            'name' => 'Swedish Massage',
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue2->id,
            'category_name' => 'Haircut',
            'name' => 'Men\'s Haircut',
        ]);

        // Test by category
        $response = $this->getJson('/api/treatments/venues?category=Massage');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Massage Salon']);
        $response->assertJsonMissing(['name' => 'Hair Salon']);

        // Test by name
        $response = $this->getJson('/api/treatments/venues?name=Swedish');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Massage Salon']);
        $response->assertJsonMissing(['name' => 'Hair Salon']);
    }

    public function test_venues_by_price_range_api_returns_venues()
    {
        $venue1 = Venue::factory()->create(['name' => 'Budget Salon']);
        $venue2 = Venue::factory()->create(['name' => 'Luxury Salon']);

        Treatment::factory()->create([
            'venue_id' => $venue1->id,
            'min_price' => 20,
            'max_price' => 50,
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue2->id,
            'min_price' => 100,
            'max_price' => 200,
        ]);

        $response = $this->getJson('/api/treatments/venues/price-range?min_price=80');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Luxury Salon']);
        $response->assertJsonMissing(['name' => 'Budget Salon']);
    }

    public function test_treatment_price_stats_api_returns_stats()
    {
        $venue = Venue::factory()->create();
        Treatment::factory()->create([
            'venue_id' => $venue->id,
            'min_price' => 20,
            'max_price' => 100,
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue->id,
            'min_price' => 50,
            'max_price' => 150,
        ]);

        $response = $this->getJson('/api/treatments/price-stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'min_price',
            'max_price',
            'avg_price',
        ]);
        $response->assertJson([
            'min_price' => 20,
            'max_price' => 150,
        ]);
    }
}
