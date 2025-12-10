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
        $dayOfWeek = $this->faker->randomElement(OpeningHour::DAYS_OF_WEEK);

        // Different opening patterns based on day of week
        $isOpen = match ($dayOfWeek) {
            'Sunday' => $this->faker->boolean(40), // Many salons closed on Sunday
            'Monday' => $this->faker->boolean(70), // Some salons closed on Monday
            'Saturday' => $this->faker->boolean(95), // Almost always open on Saturday
            default => $this->faker->boolean(90), // Usually open on weekdays
        };

        $openingTime = null;
        $closingTime = null;

        if ($isOpen) {
            // Realistic opening hours based on day
            [$openStart, $openEnd, $closeStart, $closeEnd] = match ($dayOfWeek) {
                'Monday' => [9, 11, 17, 19], // Later start on Monday
                'Tuesday', 'Wednesday', 'Thursday' => [8, 10, 18, 20], // Regular weekdays
                'Friday' => [8, 10, 19, 21], // Later close on Friday
                'Saturday' => [8, 10, 17, 19], // Earlier close on Saturday
                'Sunday' => [10, 12, 16, 18], // Shorter hours on Sunday
            };

            $openingHour = $this->faker->numberBetween($openStart, $openEnd);
            $closingHour = $this->faker->numberBetween($closeStart, $closeEnd);

            // Add some variation with 30-minute intervals
            $openingMinutes = $this->faker->randomElement(['00', '30']);
            $closingMinutes = $this->faker->randomElement(['00', '30']);

            $openingTime = sprintf('%02d:%s', $openingHour, $openingMinutes);
            $closingTime = sprintf('%02d:%s', $closingHour, $closingMinutes);
        }

        return [
            'venue_id' => Venue::factory(),
            'day_of_week' => $dayOfWeek,
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

    /**
     * Configure the factory for standard business hours.
     */
    public function businessHours(): static
    {
        return $this->state(function (array $attributes) {
            $dayOfWeek = $attributes['day_of_week'];

            // Standard 9-5 business hours
            $isOpen = ! in_array($dayOfWeek, ['Sunday']);

            if ($isOpen) {
                return [
                    'opening_time' => '09:00',
                    'closing_time' => '17:00',
                    'is_open' => true,
                ];
            }

            return [
                'opening_time' => null,
                'closing_time' => null,
                'is_open' => false,
            ];
        });
    }

    /**
     * Configure the factory for extended hours (salon/spa typical).
     */
    public function extendedHours(): static
    {
        return $this->state(function (array $attributes) {
            $dayOfWeek = $attributes['day_of_week'];

            $schedule = match ($dayOfWeek) {
                'Monday' => ['10:00', '18:00', true],
                'Tuesday', 'Wednesday', 'Thursday' => ['08:30', '19:30', true],
                'Friday' => ['08:30', '20:00', true],
                'Saturday' => ['08:00', '18:00', true],
                'Sunday' => [null, null, false],
            };

            return [
                'opening_time' => $schedule[0],
                'closing_time' => $schedule[1],
                'is_open' => $schedule[2],
            ];
        });
    }

    /**
     * Configure the factory for weekend hours.
     */
    public function weekendOnly(): static
    {
        return $this->state(function (array $attributes) {
            $dayOfWeek = $attributes['day_of_week'];
            $isWeekend = in_array($dayOfWeek, ['Saturday', 'Sunday']);

            if ($isWeekend) {
                return [
                    'opening_time' => '09:00',
                    'closing_time' => '17:00',
                    'is_open' => true,
                ];
            }

            return [
                'opening_time' => null,
                'closing_time' => null,
                'is_open' => false,
            ];
        });
    }

    /**
     * Configure the factory for a specific day.
     */
    public function forDay(string $day): static
    {
        return $this->state(function (array $attributes) use ($day) {
            return [
                'day_of_week' => $day,
            ];
        });
    }

    /**
     * Configure the factory with specific hours.
     */
    public function withHours(string $opening, string $closing): static
    {
        return $this->state(function (array $attributes) use ($opening, $closing) {
            return [
                'opening_time' => $opening,
                'closing_time' => $closing,
                'is_open' => true,
            ];
        });
    }
}
