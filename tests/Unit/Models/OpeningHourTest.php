<?php

namespace Tests\Unit\Models;

use App\Models\OpeningHour;
use App\Models\Venue;
use Tests\TestCase;

class OpeningHourTest extends TestCase
{
    public function test_venue_relationship(): void
    {
        $venue = Venue::factory()->create();
        $openingHour = OpeningHour::factory()->create(['venue_id' => $venue->id]);

        $this->assertInstanceOf(Venue::class, $openingHour->venue);
        $this->assertEquals($venue->id, $openingHour->venue->id);
    }

    public function test_casts_boolean_fields_correctly(): void
    {
        $openingHour = OpeningHour::factory()->create(['is_open' => 1]);

        $this->assertIsBool($openingHour->is_open);
        $this->assertTrue($openingHour->is_open);
    }

    public function test_casts_time_fields_correctly(): void
    {
        $openingHour = OpeningHour::factory()->create([
            'opening_time' => '09:00',
            'closing_time' => '17:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $openingHour->opening_time);
        $this->assertInstanceOf(\Carbon\Carbon::class, $openingHour->closing_time);
        $this->assertEquals('09:00', $openingHour->opening_time->format('H:i'));
        $this->assertEquals('17:00', $openingHour->closing_time->format('H:i'));
    }

    public function test_formatted_hours_for_open_venue(): void
    {
        $openingHour = OpeningHour::factory()->create([
            'is_open' => true,
            'opening_time' => '09:00',
            'closing_time' => '17:00',
        ]);

        $this->assertEquals('9:00 AM - 5:00 PM', $openingHour->getFormattedHours());
    }

    public function test_formatted_hours_for_closed_venue(): void
    {
        $openingHour = OpeningHour::factory()->closed()->create();

        $this->assertEquals('Closed', $openingHour->getFormattedHours());
    }

    public function test_for_day_scope(): void
    {
        OpeningHour::factory()->create(['day_of_week' => 'Monday']);
        OpeningHour::factory()->create(['day_of_week' => 'Tuesday']);

        $mondayHours = OpeningHour::forDay('Monday')->get();

        $this->assertCount(1, $mondayHours);
        $this->assertEquals('Monday', $mondayHours->first()->day_of_week);
    }

    public function test_open_scope(): void
    {
        OpeningHour::factory()->create(['is_open' => true]);
        OpeningHour::factory()->closed()->create();

        $openHours = OpeningHour::open()->get();

        $this->assertCount(1, $openHours);
        $this->assertTrue($openHours->first()->is_open);
    }

    public function test_days_of_week_constant(): void
    {
        $expectedDays = [
            'Monday', 'Tuesday', 'Wednesday', 'Thursday',
            'Friday', 'Saturday', 'Sunday',
        ];

        $this->assertEquals($expectedDays, OpeningHour::DAYS_OF_WEEK);
    }

    public function test_model_exists(): void
    {
        $this->assertTrue(class_exists(OpeningHour::class));
    }
}
