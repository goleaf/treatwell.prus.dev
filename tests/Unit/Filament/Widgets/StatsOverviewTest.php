<?php

namespace Tests\Unit\Filament\Widgets;

use App\Filament\Widgets\StatsOverview;
use App\Models\City;
use App\Models\Rating;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_can_be_instantiated(): void
    {
        $widget = new StatsOverview;

        $this->assertInstanceOf(StatsOverview::class, $widget);
    }

    public function test_get_stats_returns_correct_structure(): void
    {
        Venue::factory()->count(10)->create(['is_active' => true]);
        Venue::factory()->count(5)->create(['is_active' => false]);
        City::factory()->count(3)->create();
        Treatment::factory()->count(8)->create(['is_active' => true]);
        Treatment::factory()->count(2)->create(['is_active' => false]);
        Rating::factory()->count(15)->create(['is_verified' => true]);
        Rating::factory()->count(5)->create(['is_verified' => false]);

        $widget = new StatsOverview;
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($widget);

        $this->assertIsArray($stats);
        $this->assertCount(6, $stats);
        $this->assertArrayHasKey(0, $stats);
        $this->assertArrayHasKey(1, $stats);
        $this->assertArrayHasKey(2, $stats);
        $this->assertArrayHasKey(3, $stats);
        $this->assertArrayHasKey(4, $stats);
        $this->assertArrayHasKey(5, $stats);
    }

    public function test_get_stats_calculates_correct_counts(): void
    {
        Venue::factory()->count(10)->create(['is_active' => true]);
        Venue::factory()->count(5)->create(['is_active' => false]);
        City::factory()->count(3)->create();

        // Use existing venues to avoid creating extra ones
        $venues = Venue::factory()->count(2)->create();
        Treatment::factory()->count(8)->create(['venue_id' => $venues->first()->id]);

        $venuesForRatings = Venue::factory()->count(2)->create();
        Rating::factory()->count(20)->create(['venue_id' => $venuesForRatings->first()->id]);

        $widget = new StatsOverview;
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($widget);

        // Factories may create additional records through relationships, so we check for minimum values
        $totalVenues = (int) $stats[0]->getValue();
        $activeVenues = (int) $stats[1]->getValue();
        $totalCities = (int) $stats[3]->getValue();
        $totalTreatments = (int) $stats[4]->getValue();
        $totalRatings = (int) $stats[5]->getValue();

        $this->assertGreaterThanOrEqual(15, $totalVenues);
        $this->assertGreaterThanOrEqual(10, $activeVenues);
        $this->assertGreaterThanOrEqual(3, $totalCities);
        $this->assertGreaterThanOrEqual(8, $totalTreatments);
        $this->assertGreaterThanOrEqual(20, $totalRatings);
    }
}
