<?php

namespace Tests\Feature\Api;

use App\Models\City;
use App\Models\Country;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user for authentication
        $this->user = User::factory()->create();
    }

    /**
     * Test API returns 404 for non-existent resources.
     */
    public function test_api_returns_404_for_non_existent_resources(): void
    {
        $endpoints = [
            '/api/venues/999999',
            '/api/cities/999999',
            '/api/treatments/999999',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertStatus(404);
        }
    }

    /**
     * Test API returns 422 for invalid JSON data.
     */
    public function test_api_returns_422_for_invalid_json_data(): void
    {
        $response = $this->postJson('/api/venues', [
            'name' => '', // Invalid: required field empty
            'slug' => '', // Invalid: required field empty
            'city_id' => 'not-a-number', // Invalid: should be integer
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => [
                'name',
                'slug',
                'city_id',
            ],
        ]);
    }

    /**
     * Test API handles malformed JSON gracefully.
     */
    public function test_api_handles_malformed_json_gracefully(): void
    {
        $response = $this->withHeaders([
            'Content-Type' => 'application/json',
        ])->call('POST', '/api/venues', [], [], [], [], '{"invalid": json}');

        // Laravel may handle malformed JSON differently, expecting validation errors
        $this->assertContains($response->status(), [400, 422, 302]);
    }

    /**
     * Test API handles missing Content-Type header.
     */
    public function test_api_handles_missing_content_type_header(): void
    {
        $response = $this->json('POST', '/api/venues', [
            'name' => 'Test Venue',
        ], []);

        // Should still work, Laravel is flexible with content types
        $response->assertStatus(422); // Will fail validation for missing required fields
    }

    /**
     * Test API handles very large payloads.
     */
    public function test_api_handles_large_payloads(): void
    {
        $largeDescription = str_repeat('A', 10000); // 10KB string

        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        $response = $this->actingAs($this->user)->postJson('/api/venues', [
            'city_id' => $city->id,
            'name' => 'Test Venue',
            'slug' => 'test-venue',
            'description' => $largeDescription,
            'address' => '123 Test Street',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('venues', [
            'name' => 'Test Venue',
            'description' => $largeDescription,
        ]);
    }

    /**
     * Test API handles special characters in data.
     */
    public function test_api_handles_special_characters(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        $specialName = 'Café & Spa "Relax" <script>alert("test")</script>';
        $specialSlug = 'cafe-spa-relax-test';

        $response = $this->actingAs($this->user)->postJson('/api/venues', [
            'city_id' => $city->id,
            'name' => $specialName,
            'slug' => $specialSlug,
            'address' => '123 Test Street',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('venues', [
            'name' => $specialName,
            'slug' => $specialSlug,
        ]);
    }

    /**
     * Test API handles Unicode characters.
     */
    public function test_api_handles_unicode_characters(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        $unicodeName = '温泉スパ 🌸 Émilie\'s Café';
        $unicodeSlug = 'onsen-spa-emilies-cafe';

        $response = $this->actingAs($this->user)->postJson('/api/venues', [
            'city_id' => $city->id,
            'name' => $unicodeName,
            'slug' => $unicodeSlug,
            'address' => '123 Test Street',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('venues', [
            'name' => $unicodeName,
            'slug' => $unicodeSlug,
        ]);
    }

    /**
     * Test API handles null values appropriately.
     */
    public function test_api_handles_null_values_appropriately(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        $response = $this->actingAs($this->user)->postJson('/api/venues', [
            'city_id' => $city->id,
            'name' => 'Test Venue',
            'slug' => 'test-venue',
            'description' => null,
            'phone' => null,
            'email' => null,
            'website' => null,
            'address' => '123 Test Street',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('venues', [
            'name' => 'Test Venue',
            'description' => null,
            'phone' => null,
            'email' => null,
            'website' => null,
        ]);
    }

    /**
     * Test API handles concurrent requests to same resource.
     */
    public function test_api_handles_concurrent_updates(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        // Simulate concurrent updates
        $updateData1 = [
            'city_id' => $city->id,
            'name' => 'Updated Name 1',
            'slug' => $venue->slug,
            'address' => $venue->address ?: '123 Default Address',
            'is_active' => true,
        ];

        $updateData2 = [
            'city_id' => $city->id,
            'name' => 'Updated Name 2',
            'slug' => $venue->slug,
            'address' => $venue->address ?: '123 Default Address',
            'is_active' => true,
        ];

        $response1 = $this->actingAs($this->user)->putJson("/api/venues/{$venue->id}", $updateData1);
        $response2 = $this->actingAs($this->user)->putJson("/api/venues/{$venue->id}", $updateData2);

        // Both should succeed (last one wins)
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Verify the last update won
        $this->assertDatabaseHas('venues', [
            'id' => $venue->id,
            'name' => 'Updated Name 2',
        ]);
    }

    /**
     * Test API handles database constraint violations.
     */
    public function test_api_handles_database_constraint_violations(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        // Create a venue with a specific slug
        Venue::factory()->create([
            'city_id' => $city->id,
            'slug' => 'existing-venue',
        ]);

        // Try to create another venue with the same slug
        $response = $this->actingAs($this->user)->postJson('/api/venues', [
            'city_id' => $city->id,
            'name' => 'Another Venue',
            'slug' => 'existing-venue', // Duplicate slug
            'address' => '123 Test Street',
            'is_active' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['slug']);
    }

    /**
     * Test API pagination with edge cases.
     */
    public function test_api_pagination_edge_cases(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        // Test with no data
        $response = $this->getJson('/api/venues');
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');

        // Create some venues
        Venue::factory()->count(5)->create(['city_id' => $city->id]);

        // Test with per_page = 0 (should use default)
        $response = $this->getJson('/api/venues?per_page=0');
        $response->assertStatus(200);

        // Test with very large per_page
        $response = $this->getJson('/api/venues?per_page=1000');
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');

        // Test with negative page number
        $response = $this->getJson('/api/venues?page=-1');
        $response->assertStatus(200);

        // Test with non-numeric page
        $response = $this->getJson('/api/venues?page=abc');
        $response->assertStatus(200);
    }

    /**
     * Test API search with edge cases.
     */
    public function test_api_search_edge_cases(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        Venue::factory()->create([
            'city_id' => $city->id,
            'name' => 'Test Venue',
        ]);

        // Test empty search
        $response = $this->getJson('/api/venues?search=');
        $response->assertStatus(200);

        // Test search with special characters
        $response = $this->getJson('/api/venues?search='.urlencode('!@#$%^&*()'));
        $response->assertStatus(200);

        // Test search with very long string
        $longSearch = str_repeat('a', 1000);
        $response = $this->getJson('/api/venues?search='.urlencode($longSearch));
        $response->assertStatus(200);

        // Test search with SQL injection attempt
        $response = $this->getJson('/api/venues?search='.urlencode("'; DROP TABLE venues; --"));
        $response->assertStatus(200);
    }

    /**
     * Test API handles invalid sort parameters.
     */
    public function test_api_handles_invalid_sort_parameters(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        Venue::factory()->count(3)->create(['city_id' => $city->id]);

        // Test invalid sort parameter
        $response = $this->getJson('/api/venues?sort=invalid_column');
        $response->assertStatus(200); // Should fallback to default sort

        // Test SQL injection in sort
        $response = $this->getJson('/api/venues?sort='.urlencode('name; DROP TABLE venues; --'));
        $response->assertStatus(200); // Should fallback to default sort
    }
}
