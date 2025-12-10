<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class VenueApiTest extends TestCase
{
    /**
     * Test API routes are accessible.
     */
    public function test_api_routes_are_accessible(): void
    {
        $response = $this->getJson('/api/venues');
        $response->assertStatus(200);

        $response = $this->getJson('/api/cities');
        $response->assertStatus(200);

        $response = $this->getJson('/api/venue-types');
        $response->assertStatus(200);

        $response = $this->getJson('/api/venue-stats');
        $response->assertStatus(200);
    }

    /**
     * Test API response structure for venues index.
     */
    public function test_venues_index_response_structure(): void
    {
        $response = $this->getJson('/api/venues');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'total',
                'count',
                'per_page',
                'current_page',
                'total_pages',
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
        ]);
    }

    /**
     * Test API response structure for cities.
     */
    public function test_cities_response_structure(): void
    {
        $response = $this->getJson('/api/cities');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'total',
                'count',
                'per_page',
                'current_page',
                'total_pages',
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
        ]);
    }

    /**
     * Test API response structure for venue cities.
     */
    public function test_venue_cities_response_structure(): void
    {
        $response = $this->getJson('/api/venue-cities');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
            ],
        ]);
    }

    /**
     * Test API response structure for types.
     */
    public function test_types_response_structure(): void
    {
        $response = $this->getJson('/api/venue-types');

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    /**
     * Test API response structure for stats.
     */
    public function test_stats_response_structure(): void
    {
        $response = $this->getJson('/api/venue-stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_venues',
            'total_cities',
            'top_rated_venues',
            'cities_with_most_venues',
        ]);
    }

    /**
     * Test treatments API routes are accessible.
     */
    public function test_treatment_api_routes_are_accessible(): void
    {
        $response = $this->getJson('/api/treatments');
        $response->assertStatus(200);

        $response = $this->getJson('/api/treatment-categories');
        $response->assertStatus(200);

        $response = $this->getJson('/api/treatment-price-stats');
        $response->assertStatus(200);
    }

    /**
     * Test treatments index response structure.
     */
    public function test_treatments_index_response_structure(): void
    {
        $response = $this->getJson('/api/treatments');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'total',
                'count',
                'per_page',
                'current_page',
                'total_pages',
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
        ]);
    }
}