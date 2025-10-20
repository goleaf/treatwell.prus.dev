<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\Rating;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreatmentControllerTest extends TestCase
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
     * Test the categories method returns unique treatment categories.
     */
    public function test_categories_returns_unique_treatment_categories(): void
    {
        // Create a venue
        $venue = Venue::factory()->create();

        // Create treatments with different categories
        Treatment::factory()->create([
            'venue_id' => $venue->id,
            'category_name' => 'Massage',
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue->id,
            'category_name' => 'Massage', // Duplicate category
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue->id,
            'category_name' => 'Haircut',
        ]);

        // Call the API endpoint
        $response = $this->getJson('/api/treatments/categories');

        // Assert response
        $response->assertStatus(200);
        $response->assertJsonCount(2); // Only unique categories
        $response->assertJsonFragment(['Massage']);
        $response->assertJsonFragment(['Haircut']);
    }

    /**
     * Test venuesByTreatment returns venues filtered by category.
     */
    public function test_venues_by_treatment_filters_by_category(): void
    {
        // Create venues
        $venue1 = Venue::factory()->create(['name' => 'Massage Salon']);
        $venue2 = Venue::factory()->create(['name' => 'Hair Salon']);

        // Create treatments
        Treatment::factory()->create([
            'venue_id' => $venue1->id,
            'category_name' => 'Massage',
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue2->id,
            'category_name' => 'Haircut',
        ]);

        // Call the API endpoint with category filter
        $response = $this->getJson('/api/treatments/venues?category=Massage');

        // Assert response
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.name', 'Massage Salon');
        $response->assertJsonCount(1, 'data');
    }

    /**
     * Test venuesByTreatment returns venues filtered by name.
     */
    public function test_venues_by_treatment_filters_by_name(): void
    {
        // Create venues
        $venue1 = Venue::factory()->create(['name' => 'Spa 1']);
        $venue2 = Venue::factory()->create(['name' => 'Spa 2']);

        // Create treatments
        Treatment::factory()->create([
            'venue_id' => $venue1->id,
            'name' => 'Deep Tissue Massage',
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue2->id,
            'name' => 'Swedish Massage',
        ]);

        // Call the API endpoint with name filter
        $response = $this->getJson('/api/treatments/venues?name=Deep');

        // Assert response
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.name', 'Spa 1');
        $response->assertJsonCount(1, 'data');
    }

    /**
     * Test venuesByTreatment returns venues filtered by rating.
     */
    public function test_venues_by_treatment_filters_by_rating(): void
    {
        // Create venues
        $venue1 = Venue::factory()->create(['name' => 'High Rated Salon']);
        $venue2 = Venue::factory()->create(['name' => 'Low Rated Salon']);

        // Create treatments for both venues
        Treatment::factory()->create([
            'venue_id' => $venue1->id,
            'category_name' => 'Massage',
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue2->id,
            'category_name' => 'Massage',
        ]);

        // Create ratings
        Rating::factory()->create([
            'venue_id' => $venue1->id,
            'weighted_average' => 4.5,
        ]);

        Rating::factory()->create([
            'venue_id' => $venue2->id,
            'weighted_average' => 3.5,
        ]);

        // Call the API endpoint with rating filter
        $response = $this->getJson('/api/treatments/venues?category=Massage&rating=4.0');

        // Assert response
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.name', 'High Rated Salon');
        $response->assertJsonCount(1, 'data');
    }

    /**
     * Test venuesByTreatment returns venues filtered by city.
     */
    public function test_venues_by_treatment_filters_by_city(): void
    {
        // Create cities
        $city1 = City::factory()->create(['country_id' => $this->country->id]);
        $city2 = City::factory()->create(['country_id' => $this->country->id]);

        // Create venues
        $venue1 = Venue::factory()->create(['name' => 'Venue in City 1']);
        $venue2 = Venue::factory()->create(['name' => 'Venue in City 2']);

        // Create locations
        Location::factory()->create([
            'venue_id' => $venue1->id,
            'city_id' => $city1->id,
        ]);

        Location::factory()->create([
            'venue_id' => $venue2->id,
            'city_id' => $city2->id,
        ]);

        // Create treatments for both venues
        Treatment::factory()->create([
            'venue_id' => $venue1->id,
            'category_name' => 'Massage',
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue2->id,
            'category_name' => 'Massage',
        ]);

        // Call the API endpoint with city filter
        $response = $this->getJson('/api/treatments/venues?category=Massage&city_id='.$city1->id);

        // Assert response
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.name', 'Venue in City 1');
        $response->assertJsonCount(1, 'data');
    }

    /**
     * Test venuesByPriceRange returns venues filtered by min_price.
     */
    public function test_venues_by_price_range_filters_by_min_price(): void
    {
        // Create venues
        $venue1 = Venue::factory()->create(['name' => 'Budget Salon']);
        $venue2 = Venue::factory()->create(['name' => 'Luxury Salon']);

        // Create treatments
        Treatment::factory()->create([
            'venue_id' => $venue1->id,
            'min_price' => 30,
            'max_price' => 50,
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue2->id,
            'min_price' => 100,
            'max_price' => 200,
        ]);

        // Call the API endpoint with min_price filter
        $response = $this->getJson('/api/treatments/venues/price-range?min_price=80');

        // Assert response
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.name', 'Luxury Salon');
        $response->assertJsonCount(1, 'data');
    }

    /**
     * Test venuesByPriceRange returns venues filtered by max_price.
     */
    public function test_venues_by_price_range_filters_by_max_price(): void
    {
        // Create venues
        $venue1 = Venue::factory()->create(['name' => 'Budget Salon']);
        $venue2 = Venue::factory()->create(['name' => 'Luxury Salon']);

        // Create treatments
        Treatment::factory()->create([
            'venue_id' => $venue1->id,
            'min_price' => 30,
            'max_price' => 50,
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue2->id,
            'min_price' => 100,
            'max_price' => 200,
        ]);

        // Call the API endpoint with max_price filter
        $response = $this->getJson('/api/treatments/venues/price-range?max_price=60');

        // Assert response
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.name', 'Budget Salon');
        $response->assertJsonCount(1, 'data');
    }

    /**
     * Test priceStats returns correct price statistics.
     */
    public function test_price_stats_returns_correct_values(): void
    {
        // Create venues
        $venue = Venue::factory()->create();

        // Create treatments with specific prices
        Treatment::factory()->create([
            'venue_id' => $venue->id,
            'min_price' => 10,
            'max_price' => 50,
        ]);

        Treatment::factory()->create([
            'venue_id' => $venue->id,
            'min_price' => 30,
            'max_price' => 100,
        ]);

        // Call the API endpoint
        $response = $this->getJson('/api/treatments/price-stats');

        // Assert response
        $response->assertStatus(200);
        $response->assertJson([
            'min_price' => 10,
            'max_price' => 100,
            'avg_price' => 20, // (10 + 30) / 2
        ]);
    }

    /**
     * Test validation for venuesByTreatment.
     */
    public function test_venues_by_treatment_validates_input(): void
    {
        // Call with invalid per_page parameter
        $response = $this->getJson('/api/treatments/venues?per_page=200'); // exceeds max of 100

        // Assert validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['per_page']);
    }

    /**
     * Test validation for venuesByPriceRange.
     */
    public function test_venues_by_price_range_validates_input(): void
    {
        // Call with invalid min_price parameter
        $response = $this->getJson('/api/treatments/venues/price-range?min_price=-10'); // below min of 0

        // Assert validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['min_price']);
    }
}
