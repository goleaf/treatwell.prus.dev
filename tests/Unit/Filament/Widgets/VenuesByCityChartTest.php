<?php

namespace Tests\Unit\Filament\Widgets;

use App\Filament\Widgets\VenuesByCityChart;
use App\Models\City;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenuesByCityChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_can_be_instantiated(): void
    {
        $widget = new VenuesByCityChart;

        $this->assertInstanceOf(VenuesByCityChart::class, $widget);
    }

    public function test_get_data_returns_correct_structure(): void
    {
        $city = City::factory()->create();
        Venue::factory()->count(5)->create(['city_id' => $city->id]);

        $widget = new VenuesByCityChart;
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);
        $data = $method->invoke($widget);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertIsArray($data['datasets']);
        $this->assertCount(1, $data['datasets']);
        $this->assertArrayHasKey('data', $data['datasets'][0]);
        $this->assertArrayHasKey('label', $data['datasets'][0]);
    }

    public function test_get_data_excludes_cities_without_venues(): void
    {
        $cityWithVenues = City::factory()->create();
        $cityWithoutVenues = City::factory()->create();
        Venue::factory()->count(3)->create(['city_id' => $cityWithVenues->id]);

        $widget = new VenuesByCityChart;
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);
        $data = $method->invoke($widget);

        $this->assertContains($cityWithVenues->name, $data['labels']);
        $this->assertNotContains($cityWithoutVenues->name, $data['labels']);
    }

    public function test_get_type_returns_doughnut(): void
    {
        $widget = new VenuesByCityChart;
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getType');
        $method->setAccessible(true);

        $this->assertEquals('doughnut', $method->invoke($widget));
    }
}
