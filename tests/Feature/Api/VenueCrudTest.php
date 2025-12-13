<?php

namespace Tests\Feature\Api;

use App\Models\City;
use App\Models\Country;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $country = Country::factory()->create();
        $this->city = City::factory()->create(['country_id' => $country->id]);

        // Create users for authentication
        $this->user = User::factory()->create();
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
        ]);
    }

    /**
     * Test creating a venue via API.
     */
    public function test_can_create_venue(): void
    {
        $venueData = [
            'city_id' => $this->city->id,
            'name' => 'Test Venue',
            'slug' => 'test-venue',
            'address' => '123 Test Street',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/venues', $venueData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
                'address',
                'is_active',
                'created_at',
                'updated_at',
            ],
        ]);

        $this->assertDatabaseHas('venues', [
            'name' => 'Test Venue',
            'slug' => 'test-venue',
        ]);
    }

    /**
     * Test showing a venue via API.
     */
    public function test_can_show_venue(): void
    {
        $venue = Venue::factory()->create(['city_id' => $this->city->id]);

        $response = $this->getJson("/api/venues/{$venue->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
                'address',
                'is_active',
                'created_at',
                'updated_at',
            ],
        ]);

        $response->assertJson([
            'data' => [
                'id' => $venue->id,
                'name' => $venue->name,
            ],
        ]);
    }

    /**
     * Test updating a venue via API.
     */
    public function test_can_update_venue(): void
    {
        $venue = Venue::factory()->create(['city_id' => $this->city->id]);

        $updateData = [
            'city_id' => $this->city->id,
            'name' => 'Updated Venue Name',
            'slug' => $venue->slug, // Keep the same slug
            'address' => $venue->address ?: '123 Default Address',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->putJson("/api/venues/{$venue->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $venue->id,
                'name' => 'Updated Venue Name',
            ],
        ]);

        $this->assertDatabaseHas('venues', [
            'id' => $venue->id,
            'name' => 'Updated Venue Name',
        ]);
    }

    /**
     * Test deleting a venue via API.
     */
    public function test_can_delete_venue(): void
    {
        $venue = Venue::factory()->create(['city_id' => $this->city->id]);

        $response = $this->actingAs($this->adminUser)->deleteJson("/api/venues/{$venue->id}");

        $response->assertStatus(204);

        // Check that the venue is soft deleted
        $this->assertSoftDeleted('venues', [
            'id' => $venue->id,
        ]);
    }

    /**
     * Test venue not found returns 404.
     */
    public function test_venue_not_found_returns_404(): void
    {
        $response = $this->getJson('/api/venues/999999');

        $response->assertStatus(404);
    }
}
