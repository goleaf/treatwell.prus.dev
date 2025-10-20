<?php

namespace Tests\Feature;

use App\Services\CommandService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_index_page_loads()
    {
        $response = $this->get('/admin');

        $response->assertStatus(200);
        $response->assertViewIs('admin.index');
    }

    public function test_scrape_city_form_validation()
    {
        $response = $this->post('/admin/scrape', [
            'city' => '',  // Empty city should fail validation
            'page_limit' => 5,
        ]);

        $response->assertStatus(302);  // Redirect back with errors
        $response->assertSessionHasErrors('city');
    }

    public function test_scrape_city_page_limit_validation()
    {
        $response = $this->post('/admin/scrape', [
            'city' => 'vilnius-lt',
            'page_limit' => 'not-a-number',  // Should fail validation
        ]);

        $response->assertStatus(302);  // Redirect back with errors
        $response->assertSessionHasErrors('page_limit');
    }

    public function test_scrape_city_success()
    {
        // Mock the CommandService
        $mockService = Mockery::mock(CommandService::class);
        $mockService->shouldReceive('execute')->once()->andReturn(0);
        $this->app->instance(CommandService::class, $mockService);

        $response = $this->post('/admin/scrape', [
            'city' => 'vilnius-lt',
            'page_limit' => 5,
        ]);

        $response->assertStatus(302);  // Redirect to admin index
        $response->assertSessionHas('success');
        $response->assertRedirect('/admin');
    }

    public function test_scrape_all_cities_success()
    {
        // Mock the CommandService
        $mockService = Mockery::mock(CommandService::class);
        $mockService->shouldReceive('execute')->once()->andReturn(0);
        $this->app->instance(CommandService::class, $mockService);

        $response = $this->post('/admin/scrape-all', [
            'page_limit' => 3,
        ]);

        $response->assertStatus(302);  // Redirect to admin index
        $response->assertSessionHas('success');
        $response->assertRedirect('/admin');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
