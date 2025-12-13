<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class {{TestName}} extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Additional setup if needed
    }

    /**
     * Test successful operation.
     */
    public function test_successful_operation(): void
    {
        // Arrange
        $user = User::factory()->create();
        $data = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
        ];

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/endpoint', $data);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('table_name', [
            'name' => $data['name'],
            'email' => $data['email']
        ]);
    }

    /**
     * Test validation failure.
     */
    public function test_validation_failure(): void
    {
        // Arrange
        $user = User::factory()->create();
        $invalidData = [
            'name' => '', // Invalid: empty name
            'email' => 'invalid-email', // Invalid: malformed email
        ];

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/endpoint', $invalidData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    /**
     * Test unauthorized access.
     */
    public function test_unauthorized_access(): void
    {
        // Arrange
        $data = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
        ];

        // Act
        $response = $this->postJson('/api/endpoint', $data);

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test resource not found.
     */
    public function test_resource_not_found(): void
    {
        // Arrange
        $user = User::factory()->create();
        $nonExistentId = 99999;

        // Act
        $response = $this->actingAs($user)
            ->getJson("/api/endpoint/{$nonExistentId}");

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test edge case scenario.
     */
    public function test_edge_case_scenario(): void
    {
        // Arrange
        $user = User::factory()->create();
        
        // Create specific test conditions
        $existingRecord = Model::factory()->create([
            'user_id' => $user->id,
            'status' => 'active'
        ]);

        // Act
        $response = $this->actingAs($user)
            ->patchJson("/api/endpoint/{$existingRecord->id}", [
                'status' => 'inactive'
            ]);

        // Assert
        $response->assertStatus(200);
        
        $existingRecord->refresh();
        $this->assertEquals('inactive', $existingRecord->status);
    }
}