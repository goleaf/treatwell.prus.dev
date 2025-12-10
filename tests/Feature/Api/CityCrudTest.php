<?php

namespace Tests\Feature\Api;

use App\Models\City;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CityCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Country $country;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->country = Country::factory()->create();

        // Create admin user for authorization
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
        ]);
    }

    /**
     * Test creating a city via API.
     */
    public function test_can_create_city(): void
    {
        $cityData = [
            'country_id' => $this->country->id,
            'name' => 'Test City',
            'slug' => 'test-city',
            'active' => true,
        ];

        $response = $this->actingAs($this->adminUser)->postJson('/api/cities', $cityData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
                'country_id',
                'latitude',
                'longitude',
                'timezone',
                'is_active',
                'created_at',
                'updated_at',
            ],
        ]);

        $this->assertDatabaseHas('cities', [
            'name' => 'Test City',
            'slug' => 'test-city',
            'country_id' => $this->country->id,
        ]);
    }

    /**
     * Test creating a city with invalid data fails validation.
     */
    public function test_create_city_validation_fails_with_invalid_data(): void
    {
        $invalidData = [
            'country_id' => 999999, // Non-existent country
            'name' => '', // Required field empty
            'slug' => '', // Required field empty
        ];

        $response = $this->postJson('/api/cities', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'slug', 'country_id']);
    }

    /**
     * Test showing a city via API.
     */
    public function test_can_show_city(): void
    {
        $city = City::factory()->create([
            'country_id' => $this->country->id,
            'name' => 'Show Test City',
            'slug' => 'show-test-city',
            'entity_id' => 'show-test-city-lt',
            'normalised_name' => 'show-test-city-lt',
        ]);

        $response = $this->getJson("/api/cities/{$city->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
                'country_id',
                'latitude',
                'longitude',
                'timezone',
                'is_active',
                'created_at',
                'updated_at',
                'country',
                'locations',
            ],
        ]);

        $response->assertJson([
            'data' => [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
            ],
        ]);
    }

    /**
     * Test updating a city via API.
     */
    public function test_can_update_city(): void
    {
        $city = City::factory()->create([
            'country_id' => $this->country->id,
            'name' => 'Update Test City',
            'slug' => 'update-test-city',
            'entity_id' => 'update-test-city-lt',
            'normalised_name' => 'update-test-city-lt',
        ]);

        $updateData = [
            'country_id' => $this->country->id,
            'name' => 'Updated City Name',
            'slug' => $city->slug, // Keep the same slug

            'active' => true,
        ];

        $response = $this->actingAs($this->adminUser)->putJson("/api/cities/{$city->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $city->id,
                'name' => 'Updated City Name',
            ],
        ]);

        $this->assertDatabaseHas('cities', [
            'id' => $city->id,
            'name' => 'Updated City Name',
        ]);
    }

    /**
     * Test updating a city with invalid data fails validation.
     */
    public function test_update_city_validation_fails_with_invalid_data(): void
    {
        $city = City::factory()->create([
            'country_id' => $this->country->id,
            'name' => 'Validation Test City',
            'slug' => 'validation-test-city',
            'entity_id' => 'validation-test-city-lt',
            'normalised_name' => 'validation-test-city-lt',
        ]);

        $invalidData = [
            'country_id' => 999999, // Non-existent country
            'name' => '', // Required field empty
            'slug' => '', // Required field empty
        ];

        $response = $this->putJson("/api/cities/{$city->id}", $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'slug', 'country_id']);
    }

    /**
     * Test deleting a city via API.
     */
    public function test_can_delete_city(): void
    {
        $city = City::factory()->create([
            'country_id' => $this->country->id,
            'name' => 'Delete Test City',
            'slug' => 'delete-test-city',
            'entity_id' => 'delete-test-city-lt',
            'normalised_name' => 'delete-test-city-lt',
        ]);

        $response = $this->actingAs($this->adminUser)->deleteJson("/api/cities/{$city->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('cities', [
            'id' => $city->id,
        ]);
    }

    /**
     * Test listing cities via API.
     */
    public function test_can_list_cities(): void
    {
        // Create cities with unique names to avoid slug conflicts
        for ($i = 1; $i <= 3; $i++) {
            City::factory()->create([
                'country_id' => $this->country->id,
                'name' => "List City {$i}",
                'slug' => "list-city-{$i}",
                'entity_id' => "list-city-{$i}-lt",
                'normalised_name' => "list-city-{$i}-lt",
            ]);
        }

        $response = $this->getJson('/api/cities');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'country_id',
                    'latitude',
                    'longitude',
                    'timezone',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ],
            'links',
            'meta',
        ]);
    }

    /**
     * Test searching cities via API.
     */
    public function test_can_search_cities(): void
    {
        City::factory()->create([
            'name' => 'London',
            'slug' => 'london-search',
            'entity_id' => 'london-search-lt',
            'normalised_name' => 'london-search-lt',
            'country_id' => $this->country->id,
        ]);
        City::factory()->create([
            'name' => 'Paris',
            'slug' => 'paris-search',
            'entity_id' => 'paris-search-lt',
            'normalised_name' => 'paris-search-lt',
            'country_id' => $this->country->id,
        ]);

        $response = $this->getJson('/api/cities?search=London');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['name' => 'London']);
    }

    /**
     * Test filtering cities by country via API.
     */
    public function test_can_filter_cities_by_country(): void
    {
        $anotherCountry = Country::factory()->create();

        City::factory()->create([
            'country_id' => $this->country->id,
            'name' => 'Filter City 1',
            'slug' => 'filter-city-1',
            'entity_id' => 'filter-city-1-lt',
            'normalised_name' => 'filter-city-1-lt',
        ]);
        City::factory()->create([
            'country_id' => $anotherCountry->id,
            'name' => 'Filter City 2',
            'slug' => 'filter-city-2',
            'entity_id' => 'filter-city-2-xx',
            'normalised_name' => 'filter-city-2-xx',
        ]);

        $response = $this->getJson("/api/cities?country_id={$this->country->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['country_id' => $this->country->id]);
    }

    /**
     * Test sorting cities via API.
     */
    public function test_can_sort_cities(): void
    {
        City::factory()->create([
            'name' => 'Zebra City',
            'slug' => 'zebra-city',
            'entity_id' => 'zebra-city-lt',
            'normalised_name' => 'zebra-city-lt',
            'country_id' => $this->country->id,
        ]);
        City::factory()->create([
            'name' => 'Alpha City',
            'slug' => 'alpha-city',
            'entity_id' => 'alpha-city-lt',
            'normalised_name' => 'alpha-city-lt',
            'country_id' => $this->country->id,
        ]);

        $response = $this->getJson('/api/cities?sort=name');

        $response->assertStatus(200);
        $cities = $response->json('data');
        $this->assertEquals('Alpha City', $cities[0]['name']);
        $this->assertEquals('Zebra City', $cities[1]['name']);
    }

    /**
     * Test city not found returns 404.
     */
    public function test_city_not_found_returns_404(): void
    {
        $response = $this->getJson('/api/cities/999999');

        $response->assertStatus(404);
    }

    /**
     * Test pagination works correctly.
     */
    public function test_pagination_works_correctly(): void
    {
        // Create cities with unique names to avoid slug conflicts
        for ($i = 1; $i <= 20; $i++) {
            City::factory()->create([
                'country_id' => $this->country->id,
                'name' => "Test City {$i}",
                'slug' => "test-city-{$i}",
                'entity_id' => "test-city-{$i}-lt",
                'normalised_name' => "test-city-{$i}-lt",
            ]);
        }

        $response = $this->getJson('/api/cities?per_page=5');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
        $response->assertJsonStructure([
            'data',
            'links',
            'meta' => [
                'current_page',
                'total_pages',
                'per_page',
                'total',
                'count',
            ],
        ]);
    }
}
