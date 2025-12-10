<?php

namespace Tests\Feature\Api;

use App\Models\Service;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Venue $venue;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->venue = Venue::factory()->create();
    }

    /**
     * Test creating a service via API.
     */
    public function test_can_create_service(): void
    {
        $serviceData = [
            'venue_id' => $this->venue->id,
            'name' => 'Test Service',
            'slug' => 'test-service',
            'description' => 'A test service',
            'category' => 'Test Category',
            'price' => 50.00,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/services', $serviceData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'venue_id',
                'name',
                'slug',
                'description',
                'category',
                'price',
                'is_active',
                'created_at',
                'updated_at',
            ],
        ]);

        $this->assertDatabaseHas('services', [
            'name' => 'Test Service',
            'slug' => 'test-service',
            'venue_id' => $this->venue->id,
        ]);
    }

    /**
     * Test showing a service via API.
     */
    public function test_can_show_service(): void
    {
        $service = Service::factory()->create(['venue_id' => $this->venue->id]);

        $response = $this->getJson("/api/services/{$service->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $service->id,
                'name' => $service->name,
                'venue_id' => $this->venue->id,
            ],
        ]);
    }

    /**
     * Test listing services via API.
     */
    public function test_can_list_services(): void
    {
        Service::factory()->count(3)->create(['venue_id' => $this->venue->id]);

        $response = $this->getJson('/api/services');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'venue_id',
                    'name',
                    'slug',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ],
            'links',
            'meta',
        ]);
    }
}
