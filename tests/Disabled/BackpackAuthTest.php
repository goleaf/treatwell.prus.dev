<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackpackAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_backpack_login_route_exists(): void
    {
        $response = $this->get(route('backpack.auth.login'));

        $response->assertStatus(200);
    }

    public function test_backpack_login_route_name_is_defined(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('backpack.auth.login'),
            'The backpack.auth.login route should be defined'
        );
    }

    public function test_admin_dashboard_requires_authentication(): void
    {
        $response = $this->get('/admin/dashboard');

        // Should redirect to login since user is not authenticated
        $response->assertRedirect(route('backpack.auth.login'));
    }

    public function test_authenticated_admin_can_access_dashboard(): void
    {
        // Create an admin user
        $adminUser = \App\Models\User::factory()->create([
            'email' => 'admin@example.com',
            'name' => 'Admin User',
        ]);

        // Authenticate as the admin user using the backpack guard
        $this->actingAs($adminUser, 'backpack');

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);
    }
}
