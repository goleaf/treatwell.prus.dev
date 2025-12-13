<?php

namespace Tests\Unit\Filament\Widgets;

use App\Filament\Widgets\VenuesChart;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenuesChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_can_be_instantiated(): void
    {
        $widget = new VenuesChart;

        $this->assertInstanceOf(VenuesChart::class, $widget);
    }

    public function test_get_data_returns_correct_structure(): void
    {
        Venue::factory()->count(5)->create([
            'created_at' => now()->subDays(10),
        ]);

        $widget = new VenuesChart;
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

    public function test_get_type_returns_bar(): void
    {
        $widget = new VenuesChart;
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getType');
        $method->setAccessible(true);

        $this->assertEquals('bar', $method->invoke($widget));
    }
}
