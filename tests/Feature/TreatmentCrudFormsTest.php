<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Treatment;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreatmentCrudFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create();
    }

    public function test_treatment_create_form_loads_successfully(): void
    {
        $response = $this->get(route('web.treatments.create'));

        $response->assertStatus(200);
        $response->assertViewIs('treatments.create');
        $response->assertSee('Create New Treatment');
    }

    public function test_treatment_can_be_created_via_form(): void
    {
        $city = City::factory()->create();
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        $treatmentData = [
            'name' => 'Test Treatment',
            'venue_id' => $venue->id,
            'description' => 'A test treatment description',
            'category_name' => 'Massage',
            'price' => 50.00,
            'duration' => 60,
            'is_active' => true,
        ];

        $response = $this->post(route('web.treatments.store'), $treatmentData);

        $response->assertRedirect();
        $this->assertDatabaseHas('treatments', [
            'name' => 'Test Treatment',
            'venue_id' => $venue->id,
            'category_name' => 'Massage',
        ]);
    }

    public function test_treatment_edit_form_loads_successfully(): void
    {
        $city = City::factory()->create();
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $treatment = Treatment::factory()->create(['venue_id' => $venue->id]);

        $response = $this->get(route('web.treatments.edit', $treatment));

        $response->assertStatus(200);
        $response->assertViewIs('treatments.edit');
        $response->assertSee('Edit Treatment');
        $response->assertSee($treatment->name);
    }

    public function test_treatment_can_be_updated_via_form(): void
    {
        $city = City::factory()->create();
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $treatment = Treatment::factory()->create(['venue_id' => $venue->id]);

        $updateData = [
            'name' => 'Updated Treatment Name',
            'venue_id' => $venue->id,
            'description' => 'Updated description',
            'category_name' => 'Updated Category',
            'price' => 75.00,
            'is_active' => true,
        ];

        $response = $this->put(route('web.treatments.update', $treatment), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('treatments', [
            'id' => $treatment->id,
            'name' => 'Updated Treatment Name',
            'description' => 'Updated description',
            'category_name' => 'Updated Category',
        ]);
    }

    public function test_treatment_create_is_publicly_accessible(): void
    {
        $response = $this->get(route('web.treatments.create'));

        $response->assertStatus(200);
        $response->assertViewIs('treatments.create');
    }

    public function test_treatment_store_is_publicly_accessible(): void
    {
        $city = City::factory()->create();
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        $treatmentData = [
            'name' => 'Test Treatment',
            'venue_id' => $venue->id,
            'description' => 'Test description',
            'category_name' => 'Test Category',
            'price' => 50.00,
            'is_active' => true,
        ];

        $response = $this->post(route('web.treatments.store'), $treatmentData);

        $response->assertRedirect();
        $this->assertDatabaseHas('treatments', [
            'name' => 'Test Treatment',
            'venue_id' => $venue->id,
        ]);
    }

    public function test_treatment_create_form_validation(): void
    {
        $response = $this->post(route('web.treatments.store'), []);

        $response->assertSessionHasErrors(['name', 'venue_id']);
    }
}
