<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeSlot>
 */
class TimeSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('now', '+30 days');
        $startHour = $this->faker->numberBetween(9, 17);
        $startMinute = $this->faker->randomElement([0, 15, 30, 45]);
        $startTime = sprintf('%02d:%02d', $startHour, $startMinute);
        $duration = $this->faker->randomElement([30, 45, 60, 90, 120]);
        $endTime = date('H:i', strtotime($startTime) + ($duration * 60));

        return [
            'venue_id' => \App\Models\Venue::factory(),
            'treatment_id' => $this->faker->optional(0.7)->passthrough(\App\Models\Treatment::factory()),
            'date' => $date->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $duration,
            'is_available' => $this->faker->boolean(85), // 85% chance of being available
            'is_blocked' => $this->faker->boolean(10), // 10% chance of being blocked
            'capacity' => $this->faker->numberBetween(1, 4),
            'booked_count' => 0,
            'is_recurring' => $this->faker->boolean(20), // 20% chance of being recurring
            'recurrence_type' => $this->faker->optional(0.2)->randomElement(['daily', 'weekly', 'monthly']),
            'recurrence_end_date' => $this->faker->optional(0.2)->dateTimeBetween('+1 month', '+6 months'),
            'created_by_user_id' => \App\Models\User::factory(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the time slot is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => true,
            'is_blocked' => false,
        ]);
    }

    /**
     * Indicate that the time slot is blocked.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blocked' => true,
        ]);
    }

    /**
     * Indicate that the time slot is unavailable.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }

    /**
     * Indicate that the time slot is fully booked.
     */
    public function fullyBooked(): static
    {
        return $this->state(function (array $attributes) {
            $capacity = $attributes['capacity'] ?? 1;

            return [
                'booked_count' => $capacity,
            ];
        });
    }

    /**
     * Indicate that the time slot is partially booked.
     */
    public function partiallyBooked(): static
    {
        return $this->state(function (array $attributes) {
            $capacity = $attributes['capacity'] ?? 1;

            return [
                'booked_count' => $this->faker->numberBetween(1, max(1, $capacity - 1)),
            ];
        });
    }

    /**
     * Create a time slot for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => now()->toDateString(),
        ]);
    }

    /**
     * Create a time slot for tomorrow.
     */
    public function tomorrow(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => now()->addDay()->toDateString(),
        ]);
    }

    /**
     * Create a recurring time slot.
     */
    public function recurring(string $type = 'weekly'): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
            'recurrence_type' => $type,
            'recurrence_end_date' => now()->addMonths(3)->toDateString(),
        ]);
    }

    /**
     * Create a time slot with high capacity.
     */
    public function highCapacity(): static
    {
        return $this->state(fn (array $attributes) => [
            'capacity' => $this->faker->numberBetween(5, 10),
        ]);
    }
}
