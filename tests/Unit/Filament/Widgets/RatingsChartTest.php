<?php

namespace Tests\Unit\Filament\Widgets;

use App\Filament\Widgets\RatingsChart;
use App\Models\Rating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RatingsChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_can_be_instantiated(): void
    {
        $widget = new RatingsChart;

        $this->assertInstanceOf(RatingsChart::class, $widget);
    }

    public function test_get_data_returns_correct_structure(): void
    {
        Rating::factory()->count(5)->create(['rating' => 5]);
        Rating::factory()->count(3)->create(['rating' => 4]);
        Rating::factory()->count(2)->create(['rating' => 3]);

        $widget = new RatingsChart;
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

    public function test_get_data_includes_all_ratings(): void
    {
        Rating::factory()->create(['rating' => 1]);
        Rating::factory()->create(['rating' => 5]);

        $widget = new RatingsChart;
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);
        $data = $method->invoke($widget);

        $this->assertCount(5, $data['labels']);
        $this->assertCount(5, $data['datasets'][0]['data']);
    }

    public function test_get_type_returns_bar(): void
    {
        $widget = new RatingsChart;
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getType');
        $method->setAccessible(true);

        $this->assertEquals('bar', $method->invoke($widget));
    }
}
