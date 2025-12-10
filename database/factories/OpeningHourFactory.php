<?php

namespace Database\Factories;

use App\Models\OpeningHour;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

class OpeningHourFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OpeningHour::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $isOpen = $this->faker->boolean(80); // 80% chance the venue is open

        $openingTime = null;
        $closingTime = null;

        if ($isOpen) {
            $openingTime = sprintf('%02d:00', $this->faker->numberBetween(8, 12));
            $closingTime = sprintf('%02d:00', $this->faker->numberBetween(17, 22));
        }

        return [
            'venue_id' => Venue::factory(),
            'day_of_week' => $this->faker->randomElement(OpeningHour::DAYS_OF_WEEK),
            'opening_time' => $openingTime,
            'closing_time' => $closingTime,
            'is_open' => $isOpen,
        ];
    }

    /**
     * Configure the factory to create closed day.
     */
    public function closed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'opening_time' => null,
                'closing_time' => null,
                'is_open' => false,
            ];
        });
    }

    /**
     * Configure the factory to create a full week of opening hours.
     */
    public function fullWeek(): static
    {
        return $this->count(7)->sequence(
            ...collect(OpeningHour::DAYS_OF_WEEK)->map(fn ($day) => ['day_of_week' => $day])->toArray()
        );
    }
}
