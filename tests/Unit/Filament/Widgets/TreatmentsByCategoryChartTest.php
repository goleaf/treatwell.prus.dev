<?php

namespace Tests\Unit\Filament\Widgets;

use App\Filament\Widgets\TreatmentsByCategoryChart;
use App\Models\Treatment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreatmentsByCategoryChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_can_be_instantiated(): void
    {
        $widget = new TreatmentsByCategoryChart;

        $this->assertInstanceOf(TreatmentsByCategoryChart::class, $widget);
    }

    public function test_get_data_returns_correct_structure(): void
    {
        Treatment::factory()->count(3)->create(['category' => 'Massage']);
        Treatment::factory()->count(2)->create(['category' => 'Facial']);

        $widget = new TreatmentsByCategoryChart;
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

    public function test_get_data_excludes_empty_categories(): void
    {
        Treatment::factory()->create(['category' => 'Massage']);
        Treatment::factory()->create(['category' => null]);
        Treatment::factory()->create(['category' => '']);

        $widget = new TreatmentsByCategoryChart;
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);
        $data = $method->invoke($widget);

        $this->assertCount(1, $data['labels']);
        $this->assertContains('Massage', $data['labels']);
    }

    public function test_get_type_returns_pie(): void
    {
        $widget = new TreatmentsByCategoryChart;
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getType');
        $method->setAccessible(true);

        $this->assertEquals('pie', $method->invoke($widget));
    }
}
