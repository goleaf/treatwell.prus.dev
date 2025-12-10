<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_venues_index_page_loads(): void
    {
        $response = $this->get(route('web.venues.index'));
        $response->assertStatus(200);
        $response->assertViewIs('venues.index');
    }

    public function test_venues_index_displays_active_venues(): void
    {
        $city = City::factory()->create();
        $activeVenue = Venue::factory()->create([
            'city_id' => $city->id,
            'is_active' => true,
            'name' => 'Active Venue',
        ]);
        $inactiveVenue = Venue::factory()->create([
            'city_id' => $city->id,
            'is_active' => false,
            'name' => 'Inactive Venue',
        ]);

        $response = $this->get(route('web.venues.index'));

        $response->assertStatus(200);
        $response->assertSee('Active Venue');
        $response->assertDontSee('Inactive Venue');
    }

    public function test_venue_show_page_loads(): void
    {
        $city = City::factory()->create();
        $venue = Venue::factory()->create([
            'city_id' => $city->id,
            'is_active' => true,
        ]);

        $response = $this->get(route('web.venues.show', $venue));

        $response->assertStatus(200);
        $response->assertViewIs('venues.show');
        $response->assertSee($venue->name);
    }

    public function test_venues_can_be_filtered_by_city(): void
    {
        $city1 = City::factory()->create(['slug' => 'city-1']);
        $city2 = City::factory()->create(['slug' => 'city-2']);

        $venue1 = Venue::factory()->create([
            'city_id' => $city1->id,
            'is_active' => true,
            'name' => 'Venue in City 1',
        ]);
        $venue2 = Venue::factory()->create([
            'city_id' => $city2->id,
            'is_active' => true,
            'name' => 'Venue in City 2',
        ]);

        $response = $this->get(route('web.venues.index', ['city' => 'city-1']));

        $response->assertStatus(200);
        $response->assertSee('Venue in City 1');
        $response->assertDontSee('Venue in City 2');
    }

    public function test_venues_can_be_searched_by_name(): void
    {
        $city = City::factory()->create();
        $venue1 = Venue::factory()->create([
            'city_id' => $city->id,
            'is_active' => true,
            'name' => 'Spa Paradise',
        ]);
        $venue2 = Venue::factory()->create([
            'city_id' => $city->id,
            'is_active' => true,
            'name' => 'Fitness Center',
        ]);

        $response = $this->get(route('web.venues.index', ['search' => 'Spa']));

        $response->assertStatus(200);
        $response->assertSee('Spa Paradise');
        $response->assertDontSee('Fitness Center');
    }

    public function test_authenticated_user_can_see_delete_button_on_venue_show_page(): void
    {
        $user = \App\Models\User::factory()->create();
        $city = City::factory()->create();
        $venue = Venue::factory()->create([
            'city_id' => $city->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('web.venues.show', $venue));

        $response->assertStatus(200);
        $response->assertSee('Delete Venue');
        $response->assertSee('confirmDelete');
    }

    public function test_guest_cannot_see_delete_button_on_venue_show_page(): void
    {
        $city = City::factory()->create();
        $venue = Venue::factory()->create([
            'city_id' => $city->id,
            'is_active' => true,
        ]);

        $response = $this->get(route('web.venues.show', $venue));

        $response->assertStatus(200);
        $response->assertDontSee('Delete Venue');
    }

    public function test_authenticated_user_can_delete_venue(): void
    {
        $user = \App\Models\User::factory()->create();
        $city = City::factory()->create();
        $venue = Venue::factory()->create([
            'city_id' => $city->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-token'])
            ->delete(route('web.venues.destroy', $venue), [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect(route('web.venues.index'));
        $response->assertSessionHas('success', 'Venue deleted successfully.');
        $this->assertSoftDeleted('venues', ['id' => $venue->id]);
    }

    public function test_guest_cannot_delete_venue(): void
    {
        $city = City::factory()->create();
        $venue = Venue::factory()->create([
            'city_id' => $city->id,
            'is_active' => true,
        ]);

        $response = $this->withSession(['_token' => 'test-token'])
            ->delete(route('web.venues.destroy', $venue), [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('venues', ['id' => $venue->id]);
    }

    public function test_authenticated_user_can_see_delete_button_on_venue_index_page(): void
    {
        $user = \App\Models\User::factory()->create();
        $city = City::factory()->create();
        $venue = Venue::factory()->create([
            'city_id' => $city->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('web.venues.index'));

        $response->assertStatus(200);
        $response->assertSee('Delete');
        $response->assertSee('Edit');
    }

    public function test_guest_cannot_see_delete_button_on_venue_index_page(): void
    {
        $city = City::factory()->create();
        $venue = Venue::factory()->create([
            'city_id' => $city->id,
            'is_active' => true,
        ]);

        $response = $this->get(route('web.venues.index'));

        $response->assertStatus(200);
        // Check that the delete form is not present
        $response->assertDontSee('web.venues.destroy');
        $response->assertDontSee('method="POST"');
        $response->assertDontSee('@method(\'DELETE\')');
    }
}
