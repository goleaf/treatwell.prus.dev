<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiTest extends TestCase
{
    /**
     * Test venues API endpoint
     */
    public function test_venues_api_returns_valid_response(): void
    {
        $response = $this->getJson('/api/venues');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'links',
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
                'to',
                'total'
            ]
        ]);
    }

    /**
     * Test cities API endpoint
     */
    public function test_cities_api_returns_valid_response(): void
    {
        $response = $this->getJson('/api/cities');
        $response->assertStatus(200);
    }

    /**
     * Test venue types API endpoint
     */
    public function test_types_api_returns_valid_response(): void
    {
        $response = $this->getJson('/api/types');
        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    /**
     * Test stats API endpoint
     */
    public function test_stats_api_returns_valid_response(): void
    {
        $response = $this->getJson('/api/stats');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_venues',
            'total_cities',
            'top_rated_venues',
            'cities_with_most_venues'
        ]);
    }

    /**
     * Test venues filtering by query parameters
     */
    public function test_venues_api_filters_work(): void
    {
        // Test with a venue name query
        if (Venue::count() > 0) {
            $venue = Venue::first();
            $response = $this->getJson('/api/venues?search=' . urlencode(substr($venue->name, 0, 5)));
            $response->assertStatus(200);
        }

        // Test with a city filter
        if (City::count() > 0) {
            $city = City::first();
            $response = $this->getJson('/api/venues?city_id=' . $city->id);
            $response->assertStatus(200);
        }

        // Test with rating filter
        $response = $this->getJson('/api/venues?rating=4.5');
        $response->assertStatus(200);

        // Test with type filter (if we have venues with types)
        $venueWithType = Venue::whereNotNull('type_name')->first();
        if ($venueWithType) {
            $response = $this->getJson('/api/venues?type=' . urlencode($venueWithType->type_name));
            $response->assertStatus(200);
        }
    }

    /**
     * Test sorting parameters
     */
    public function test_venues_api_sorting_works(): void
    {
        // Test various sorting options
        $sortOptions = ['name', 'name_desc', 'rating', 'rating_asc', 'newest', 'oldest'];
        
        foreach ($sortOptions as $sort) {
            $response = $this->getJson('/api/venues?sort=' . $sort);
            $response->assertStatus(200);
        }
    }

    /**
     * Test pagination parameters
     */
    public function test_venues_api_pagination_works(): void
    {
        // Test with custom per_page parameter
        $response = $this->getJson('/api/venues?per_page=5');
        $response->assertStatus(200);
        $jsonData = $response->json();
        
        // Verify the 'per_page' meta value matches our request
        if (isset($jsonData['meta']) && isset($jsonData['meta']['per_page'])) {
            $this->assertEquals(5, $jsonData['meta']['per_page']);
        }
    }
}
