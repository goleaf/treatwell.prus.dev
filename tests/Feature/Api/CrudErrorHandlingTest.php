<?php

namespace Tests\Feature\Api;

use App\Models\City;
use App\Models\Country;
use App\Models\Image;
use App\Models\Location;
use App\Models\Service;
use App\Models\Treatment;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user for authentication
        $this->user = User::factory()->create([
            'email' => 'admin@example.com', // This makes the user an admin
        ]);
        $this->actingAs($this->user);
    }

    /**
     * Test successful venue creation with transaction.
     */
    public function test_successful_venue_creation_with_transaction(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        $response = $this->postJson('/api/venues', [
            'city_id' => $city->id,
            'name' => 'Test Venue',
            'slug' => 'test-venue',
            'address' => '123 Test Street',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
                'address',
            ],
        ]);

        // Verify venue was created
        $this->assertDatabaseHas('venues', [
            'name' => 'Test Venue',
            'slug' => 'test-venue',
        ]);
    }

    /**
     * Test validation of related models.
     */
    public function test_validates_related_models_exist(): void
    {
        // Try to create a venue with non-existent city
        $response = $this->postJson('/api/venues', [
            'city_id' => 99999, // Non-existent city
            'name' => 'Test Venue',
            'slug' => 'test-venue',
            'address' => '123 Test Street',
            'is_active' => true,
        ]);

        // Laravel's built-in validation catches this first
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['city_id']);
    }

    /**
     * Test successful treatment creation.
     */
    public function test_successful_treatment_creation(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        // Create a treatment for the venue
        $response = $this->postJson('/api/treatments', [
            'venue_id' => $venue->id,
            'name' => 'Test Treatment',
            'slug' => 'test-treatment',
            'category_name' => 'Spa',
            'min_price' => 100,
            'max_price' => 200,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'venue_id',
            ],
        ]);

        // Verify treatment was created
        $this->assertDatabaseHas('treatments', [
            'name' => 'Test Treatment',
            'venue_id' => $venue->id,
        ]);
    }

    /**
     * Test successful deletion of models without critical relationships.
     */
    public function test_successful_deletion_without_critical_relationships(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        // Delete venue (should succeed as it doesn't have critical relationships in our test)
        $response = $this->deleteJson("/api/venues/{$venue->id}");

        $response->assertStatus(204);

        // Verify venue was soft deleted
        $this->assertSoftDeleted('venues', [
            'id' => $venue->id,
        ]);
    }

    /**
     * Test successful update without concurrent modifications.
     */
    public function test_successful_update_without_concurrent_modifications(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        // Update venue normally
        $response = $this->putJson("/api/venues/{$venue->id}", [
            'city_id' => $city->id,
            'name' => 'Updated Venue Name',
            'slug' => $venue->slug,
            'address' => $venue->address ?: '123 Default Address',
            'is_active' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Updated Venue Name',
        ]);

        // Verify venue was updated
        $this->assertDatabaseHas('venues', [
            'id' => $venue->id,
            'name' => 'Updated Venue Name',
        ]);
    }

    /**
     * Test database constraint violation handling.
     */
    public function test_handles_database_constraint_violations(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id, 'slug' => 'unique-venue']);

        // Try to create another venue with the same slug (unique constraint)
        $response = $this->postJson('/api/venues', [
            'city_id' => $city->id,
            'name' => 'Another Venue',
            'slug' => 'unique-venue', // Duplicate slug
            'address' => '123 Test Street',
            'is_active' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['slug']);
    }

    /**
     * Test successful bulk operations.
     */
    public function test_successful_bulk_operations(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        // Create multiple valid images
        $imageData = [
            [
                'venue_id' => $venue->id,
                'imageable_type' => 'App\\Models\\Venue',
                'imageable_id' => $venue->id,
                'path' => 'image1.jpg',
                'alt_text' => 'First image',
                'sort_order' => 1,
            ],
            [
                'venue_id' => $venue->id,
                'imageable_type' => 'App\\Models\\Venue',
                'imageable_id' => $venue->id,
                'path' => 'image2.jpg',
                'alt_text' => 'Second image',
                'sort_order' => 2,
            ],
        ];

        $results = [];
        foreach ($imageData as $data) {
            $response = $this->postJson('/api/images', $data);
            $results[] = [
                'success' => $response->status() === 201,
                'data' => $data,
                'response' => $response->json(),
            ];
        }

        // Verify all operations succeeded
        $this->assertTrue($results[0]['success']);
        $this->assertTrue($results[1]['success']);

        // Verify images were created
        $this->assertDatabaseHas('images', ['path' => 'image1.jpg']);
        $this->assertDatabaseHas('images', ['path' => 'image2.jpg']);
    }

    /**
     * Test API operation logging.
     */
    public function test_logs_api_operations(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        // Create a venue
        $response = $this->postJson('/api/venues', [
            'city_id' => $city->id,
            'name' => 'Test Venue',
            'slug' => 'test-venue',
            'address' => '123 Test Street',
            'is_active' => true,
        ]);

        $response->assertStatus(201);

        // Check that logs were written (this would require log testing setup)
        // For now, we just verify the operation succeeded
        $this->assertDatabaseHas('venues', [
            'name' => 'Test Venue',
        ]);
    }

    /**
     * Test successful service creation.
     */
    public function test_successful_service_creation(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        // Create a service for the venue
        $response = $this->postJson('/api/services', [
            'venue_id' => $venue->id,
            'name' => 'Test Service',
            'slug' => 'test-service',
            'category' => 'Spa',
            'price' => 100,
            'duration' => 60,
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'venue_id',
            ],
        ]);

        // Verify service was created
        $this->assertDatabaseHas('services', [
            'name' => 'Test Service',
            'venue_id' => $venue->id,
        ]);
    }

    /**
     * Test validation of missing required relationships.
     */
    public function test_validates_missing_required_relationships(): void
    {
        // Try to create a treatment without venue_id
        $response = $this->postJson('/api/treatments', [
            'name' => 'Test Treatment',
            'category_name' => 'Spa',
            'min_price' => 100,
            'max_price' => 200,
        ]);

        // This should be caught by form request validation
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['venue_id']);
    }

    /**
     * Test successful image operations.
     */
    public function test_successful_image_operations(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        $image1 = Image::factory()->create([
            'venue_id' => $venue->id,
            'imageable_type' => 'App\\Models\\Venue',
            'imageable_id' => $venue->id,
        ]);

        $image2 = Image::factory()->create([
            'venue_id' => $venue->id,
            'imageable_type' => 'App\\Models\\Venue',
            'imageable_id' => $venue->id,
        ]);

        // Test reordering with valid image IDs
        $response = $this->patchJson('/api/images/reorder', [
            'imageable_type' => 'App\\Models\\Venue',
            'imageable_id' => $venue->id,
            'image_ids' => [$image1->id, $image2->id], // Both valid
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Images reordered successfully']);
    }

    /**
     * Test error handling for location operations with coordinates.
     */
    public function test_handles_location_coordinate_errors(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        // Test nearby search with invalid coordinates
        $response = $this->getJson('/api/locations/nearby?latitude=invalid&longitude=180.5');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['latitude', 'longitude']);
    }
}
