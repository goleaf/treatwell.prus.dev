<?php

namespace Tests\Feature\Api;

use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user for authorization
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
        ]);
    }

    /**
     * Test creating a country via API.
     */
    public function test_can_create_country(): void
    {
        $countryData = [
            'name' => 'Test Country',
            'code' => 'TC',
            'slug' => 'test-country',
            'active' => true,
        ];

        $response = $this->actingAs($this->adminUser)->postJson('/api/countries', $countryData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'code',
                'slug',
                'active',
                'created_at',
                'updated_at',
            ],
        ]);

        $this->assertDatabaseHas('countries', [
            'name' => 'Test Country',
            'code' => 'TC',
            'slug' => 'test-country',
        ]);
    }

    /**
     * Test showing a country via API.
     */
    public function test_can_show_country(): void
    {
        $country = Country::factory()->create();

        $response = $this->getJson("/api/countries/{$country->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $country->id,
                'name' => $country->name,
                'code' => $country->code,
            ],
        ]);
    }

    /**
     * Test updating a country via API.
     */
    public function test_can_update_country(): void
    {
        $country = Country::factory()->create();

        $updateData = [
            'name' => 'Updated Country Name',
            'code' => $country->code,
            'slug' => $country->slug,
            'active' => true,
        ];

        $response = $this->actingAs($this->adminUser)->putJson("/api/countries/{$country->id}", $updateData);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $country->id,
                'name' => 'Updated Country Name',
            ],
        ]);

        $this->assertDatabaseHas('countries', [
            'id' => $country->id,
            'name' => 'Updated Country Name',
        ]);
    }

    /**
     * Test deleting a country via API.
     */
    public function test_can_delete_country(): void
    {
        $country = Country::factory()->create();

        $response = $this->actingAs($this->adminUser)->deleteJson("/api/countries/{$country->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('countries', [
            'id' => $country->id,
        ]);
    }

    /**
     * Test listing countries via API.
     */
    public function test_can_list_countries(): void
    {
        Country::factory()->count(3)->create();

        $response = $this->getJson('/api/countries');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'code',
                    'slug',
                    'active',
                    'created_at',
                    'updated_at',
                ],
            ],
            'links',
            'meta',
        ]);
    }
}
