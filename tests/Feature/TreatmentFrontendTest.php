<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreatmentFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_treatments_index_page_loads(): void
    {
        $response = $this->get(route('web.treatments.index'));
        $response->assertStatus(200);
        $response->assertViewIs('treatments.index');
    }

    public function test_treatments_index_displays_active_treatments(): void
    {
        $city = City::factory()->create();
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        $activeTreatment = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'is_active' => true,
            'name' => 'Active Treatment',
        ]);
        $inactiveTreatment = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'is_active' => false,
            'name' => 'Inactive Treatment',
        ]);

        $response = $this->get(route('web.treatments.index'));

        $response->assertStatus(200);
        $response->assertSee('Active Treatment');
        $response->assertDontSee('Inactive Treatment');
    }

    public function test_treatment_show_page_loads(): void
    {
        $city = City::factory()->create();
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $treatment = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'is_active' => true,
        ]);

        $response = $this->get(route('web.treatments.show', $treatment));

        if ($response->status() !== 200) {
            // Debug the error
            $this->fail('Response status: '.$response->status().'. Content: '.$response->getContent());
        }

        $response->assertStatus(200);
        $response->assertViewIs('treatments.show');
        $response->assertSee($treatment->name);
    }

    public function test_treatments_can_be_filtered_by_category(): void
    {
        $city = City::factory()->create();
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        $treatment1 = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'is_active' => true,
            'name' => 'Massage Treatment',
            'category_name' => 'Massage',
        ]);
        $treatment2 = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'is_active' => true,
            'name' => 'Facial Treatment',
            'category_name' => 'Facial',
        ]);

        $response = $this->get(route('web.treatments.index', ['category' => 'Massage']));

        $response->assertStatus(200);
        $response->assertSee('Massage Treatment');
        $response->assertDontSee('Facial Treatment');
    }

    public function test_treatments_can_be_searched_by_name(): void
    {
        $city = City::factory()->create();
        $venue = Venue::factory()->create(['city_id' => $city->id]);

        $treatment1 = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'is_active' => true,
            'name' => 'Deep Tissue Massage',
        ]);
        $treatment2 = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'is_active' => true,
            'name' => 'Relaxing Facial',
        ]);

        $response = $this->get(route('web.treatments.index', ['search' => 'Massage']));

        $response->assertStatus(200);
        $response->assertSee('Deep Tissue Massage');
        $response->assertDontSee('Relaxing Facial');
    }

    public function test_authenticated_user_can_see_delete_button_on_treatment_show_page(): void
    {
        $user = \App\Models\User::factory()->create();
        $city = City::factory()->create();
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $treatment = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('web.treatments.show', $treatment));

        $response->assertStatus(200);
        $response->assertSee('Delete Treatment');
        $response->assertSee('confirmDelete');
    }

    public function test_guest_cannot_see_delete_button_on_treatment_show_page(): void
    {
        $city = City::factory()->create();
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $treatment = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'is_active' => true,
        ]);

        $response = $this->get(route('web.treatments.show', $treatment));

        $response->assertStatus(200);
        $response->assertDontSee('Delete Treatment');
    }

    public function test_authenticated_user_can_delete_treatment(): void
    {
        $user = \App\Models\User::factory()->create();
        $city = City::factory()->create();
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $treatment = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-token'])
            ->delete(route('web.treatments.destroy', $treatment), [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect(route('web.treatments.index'));
        $response->assertSessionHas('success', 'Treatment deleted successfully.');
        $this->assertSoftDeleted('treatments', ['id' => $treatment->id]);
    }

    public function test_guest_cannot_delete_treatment(): void
    {
        $city = City::factory()->create();
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $treatment = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'is_active' => true,
        ]);

        $response = $this->withSession(['_token' => 'test-token'])
            ->delete(route('web.treatments.destroy', $treatment), [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('treatments', ['id' => $treatment->id]);
    }

    public function test_authenticated_user_can_see_delete_button_on_treatment_index_page(): void
    {
        $user = \App\Models\User::factory()->create();
        $city = City::factory()->create();
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $treatment = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('web.treatments.index'));

        $response->assertStatus(200);
        $response->assertSee('Delete');
        $response->assertSee('Edit');
    }

    public function test_guest_cannot_see_delete_button_on_treatment_index_page(): void
    {
        $city = City::factory()->create();
        $venue = Venue::factory()->create(['city_id' => $city->id]);
        $treatment = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'is_active' => true,
        ]);

        $response = $this->get(route('web.treatments.index'));

        $response->assertStatus(200);
        // Check that the delete form is not present
        $response->assertDontSee('web.treatments.destroy');
        $response->assertDontSee('method="POST"');
        $response->assertDontSee('@method(\'DELETE\')');
    }
}
