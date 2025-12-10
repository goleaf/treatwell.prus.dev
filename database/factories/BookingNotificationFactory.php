<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookingNotification>
 */
class BookingNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['confirmation', 'reminder', 'cancellation', 'modification', 'venue_update']);

        return [
            'booking_id' => \App\Models\Booking::factory(),
            'user_id' => \App\Models\User::factory(),
            'type' => $type,
            'channel' => $this->faker->randomElement(['email', 'sms', 'push']),
            'recipient_email' => $this->faker->safeEmail(),
            'recipient_phone' => $this->faker->optional(0.6)->phoneNumber(),
            'subject' => $this->getSubjectForType($type),
            'message' => $this->getMessageForType($type),
            'template_data' => $this->faker->optional(0.7)->passthrough([
                'booking_reference' => 'BK-2024-123456',
                'venue_name' => $this->faker->company(),
                'treatment_name' => $this->faker->words(2, true),
                'booking_date' => $this->faker->date(),
                'booking_time' => $this->faker->time(),
            ]),
            'status' => $this->faker->randomElement(['pending', 'sent', 'failed', 'delivered']),
            'sent_at' => $this->faker->optional(0.8)->dateTimeBetween('-7 days', 'now'),
            'delivered_at' => $this->faker->optional(0.6)->dateTimeBetween('-7 days', 'now'),
            'error_message' => $this->faker->optional(0.1)->sentence(),
            'retry_count' => $this->faker->numberBetween(0, 3),
        ];
    }

    /**
     * Get subject for notification type.
     */
    private function getSubjectForType(string $type): string
    {
        return match ($type) {
            'confirmation' => 'Booking Confirmation - Your appointment is confirmed',
            'reminder' => 'Booking Reminder - Your appointment is tomorrow',
            'cancellation' => 'Booking Cancelled - Your appointment has been cancelled',
            'modification' => 'Booking Modified - Your appointment details have changed',
            'venue_update' => 'Venue Update - Important information about your booking',
            default => 'Booking Notification',
        };
    }

    /**
     * Get message for notification type.
     */
    private function getMessageForType(string $type): string
    {
        return match ($type) {
            'confirmation' => 'Your booking has been confirmed. We look forward to seeing you!',
            'reminder' => 'This is a friendly reminder about your upcoming appointment.',
            'cancellation' => 'Your booking has been cancelled. Please contact us if you have any questions.',
            'modification' => 'Your booking details have been updated. Please review the changes.',
            'venue_update' => 'There has been an update regarding your booking venue.',
            default => 'This is a notification about your booking.',
        };
    }

    /**
     * Indicate that the notification is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'sent_at' => null,
            'delivered_at' => null,
        ]);
    }

    /**
     * Indicate that the notification is sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Indicate that the notification is delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'sent_at' => now()->subMinutes(5),
            'delivered_at' => now(),
        ]);
    }

    /**
     * Indicate that the notification failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => $this->faker->sentence(),
            'retry_count' => $this->faker->numberBetween(1, 3),
        ]);
    }

    /**
     * Create a confirmation notification.
     */
    public function confirmation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'confirmation',
            'subject' => 'Booking Confirmation - Your appointment is confirmed',
            'message' => 'Your booking has been confirmed. We look forward to seeing you!',
        ]);
    }

    /**
     * Create a reminder notification.
     */
    public function reminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'reminder',
            'subject' => 'Booking Reminder - Your appointment is tomorrow',
            'message' => 'This is a friendly reminder about your upcoming appointment.',
        ]);
    }

    /**
     * Create a cancellation notification.
     */
    public function cancellation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'cancellation',
            'subject' => 'Booking Cancelled - Your appointment has been cancelled',
            'message' => 'Your booking has been cancelled. Please contact us if you have any questions.',
        ]);
    }
}
