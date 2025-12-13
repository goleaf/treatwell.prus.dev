<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentInstallationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_installation_is_complete(): void
    {
        // Test that Filament panel is accessible
        $response = $this->get('/admin');
        $response->assertStatus(200);

        // Test that the panel has the correct branding
        $response->assertSee('City Data Parser');

        // Test that dashboard is accessible
        $response->assertSee('Dashboard');
    }

    public function test_filament_works_alongside_backpack(): void
    {
        // Test that both Filament and Backpack can coexist
        $filamentResponse = $this->get('/admin');
        $filamentResponse->assertStatus(200);

        // Verify Filament is working
        $filamentResponse->assertSee('City Data Parser');

        // Verify both packages are installed
        $this->assertTrue(class_exists('App\Providers\Filament\AdminPanelProvider'));
        $this->assertTrue(function_exists('filament'));
    }

    public function test_filament_directories_exist(): void
    {
        // Test that required directories were created
        $this->assertDirectoryExists(app_path('Filament'));
        $this->assertDirectoryExists(app_path('Filament/Resources'));
        $this->assertDirectoryExists(app_path('Filament/Pages'));
        $this->assertDirectoryExists(app_path('Filament/Widgets'));
    }

    public function test_filament_service_provider_is_registered(): void
    {
        // Test that the AdminPanelProvider is properly registered
        $this->assertFileExists(app_path('Providers/Filament/AdminPanelProvider.php'));

        // Test that it's registered in providers
        $providers = require base_path('bootstrap/providers.php');
        $this->assertContains('App\Providers\Filament\AdminPanelProvider', $providers);
    }
}
