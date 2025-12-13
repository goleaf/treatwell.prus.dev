<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookingPolicy>
 */
class BookingPolicyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true).' Policy',
            'description' => $this->faker->optional()->sentence(),
            'policy_type' => $this->faker->randomElement(['system', 'venue']),
            'venue_id' => $this->faker->optional(0.5)->passthrough(\App\Models\Venue::factory()),
            'cancellation_hours' => $this->faker->randomElement([12, 24, 48, 72]),
            'advance_booking_days' => $this->faker->randomElement([7, 14, 30, 60, 90]),
            'max_bookings_per_day' => $this->faker->numberBetween(3, 10),
            'require_payment' => $this->faker->boolean(30), // 30% chance
            'allow_modifications' => $this->faker->boolean(80), // 80% chance
            'modification_hours' => $this->faker->randomElement([12, 24, 48]),
            'send_confirmation' => $this->faker->boolean(95), // 95% chance
            'send_reminders' => $this->faker->boolean(85), // 85% chance
            'reminder_hours' => $this->faker->randomElement([2, 4, 12, 24, 48]),
            'is_active' => $this->faker->boolean(90), // 90% chance
        ];
    }

    /**
     * Indicate that the policy is a system policy.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'policy_type' => 'system',
            'venue_id' => null,
            'name' => 'System Default Policy',
        ]);
    }

    /**
     * Indicate that the policy is a venue policy.
     */
    public function venue(): static
    {
        return $this->state(fn (array $attributes) => [
            'policy_type' => 'venue',
            'venue_id' => \App\Models\Venue::factory(),
        ]);
    }

    /**
     * Indicate that the policy is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the policy is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a strict policy.
     */
    public function strict(): static
    {
        return $this->state(fn (array $attributes) => [
            'cancellation_hours' => 72,
            'advance_booking_days' => 7,
            'max_bookings_per_day' => 3,
            'require_payment' => true,
            'allow_modifications' => false,
            'modification_hours' => 72,
        ]);
    }

    /**
     * Create a flexible policy.
     */
    public function flexible(): static
    {
        return $this->state(fn (array $attributes) => [
            'cancellation_hours' => 12,
            'advance_booking_days' => 90,
            'max_bookings_per_day' => 10,
            'require_payment' => false,
            'allow_modifications' => true,
            'modification_hours' => 12,
        ]);
    }
}
