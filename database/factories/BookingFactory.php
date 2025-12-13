<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bookingDate = $this->faker->dateTimeBetween('now', '+30 days');
        $startTime = $this->faker->time('H:i');
        $duration = $this->faker->randomElement([30, 45, 60, 90, 120]);
        $endTime = date('H:i', strtotime($startTime) + ($duration * 60));

        return [
            'booking_reference' => 'BK-'.date('Y').'-'.$this->faker->unique()->numberBetween(100000, 999999),
            'user_id' => \App\Models\User::factory(),
            'venue_id' => \App\Models\Venue::factory(),
            'treatment_id' => \App\Models\Treatment::factory(),
            'time_slot_id' => \App\Models\TimeSlot::factory(),
            'booking_date' => $bookingDate->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $duration,
            'price' => $this->faker->randomFloat(2, 20, 200),
            'currency' => 'EUR',
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'cancelled', 'completed']),
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'customer_phone' => $this->faker->phoneNumber(),
            'special_requests' => $this->faker->optional()->sentence(),
            'cancellation_deadline' => $bookingDate->modify('-24 hours'),
            'advance_booking_days' => $this->faker->numberBetween(1, 30),
            'booked_at' => now(),
        ];
    }

    /**
     * Indicate that the booking is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the booking is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Indicate that the booking is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the booking is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'confirmed_at' => null,
            'cancelled_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Create a booking for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_date' => now()->toDateString(),
        ]);
    }

    /**
     * Create a booking for tomorrow.
     */
    public function tomorrow(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_date' => now()->addDay()->toDateString(),
        ]);
    }

    /**
     * Create a past booking.
     */
    public function past(): static
    {
        $pastDate = $this->faker->dateTimeBetween('-30 days', '-1 day');

        return $this->state(fn (array $attributes) => [
            'booking_date' => $pastDate->format('Y-m-d'),
            'status' => 'completed',
            'completed_at' => $pastDate,
        ]);
    }
}
