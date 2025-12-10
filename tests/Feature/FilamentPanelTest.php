<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_admin_panel_is_accessible_without_authentication(): void
    {
        $response = $this->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    public function test_filament_panel_configuration_is_correct(): void
    {
        $panel = filament()->getPanel('admin');

        $this->assertEquals('admin', $panel->getId());
        $this->assertEquals('admin', $panel->getPath());
        $this->assertEmpty($panel->getAuthMiddleware());
    }
}
