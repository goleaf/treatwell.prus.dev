<?php

namespace Database\Factories;

use App\Models\ParseProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParseProgress>
 */
class ParseProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']);
        $startedAt = $status !== 'pending' ? $this->faker->dateTimeBetween('-1 week', 'now') : null;
        $completedAt = in_array($status, ['completed', 'failed']) && $startedAt
            ? $this->faker->dateTimeBetween($startedAt, 'now')
            : null;

        return [
            'city_slug' => $this->faker->slug(),
            'status' => $status,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'venues_found' => $this->faker->numberBetween(0, 500),
            'treatments_found' => $this->faker->numberBetween(0, 2000),
            'api_calls_made' => $this->faker->numberBetween(1, 100),
            'error_message' => $status === 'failed' ? $this->faker->sentence() : null,
            'metadata' => [
                'batch_size' => $this->faker->numberBetween(10, 50),
                'retry_count' => $this->faker->numberBetween(0, 3),
                'last_endpoint' => $this->faker->url(),
            ],
        ];
    }

    /**
     * Indicate that the progress is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the progress is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'started_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'completed_at' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the progress is completed.
     */
    public function completed(): static
    {
        $startedAt = $this->faker->dateTimeBetween('-1 week', '-1 hour');

        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => $startedAt,
            'completed_at' => $this->faker->dateTimeBetween($startedAt, 'now'),
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the progress failed.
     */
    public function failed(): static
    {
        $startedAt = $this->faker->dateTimeBetween('-1 week', '-1 hour');

        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'started_at' => $startedAt,
            'completed_at' => $this->faker->dateTimeBetween($startedAt, 'now'),
            'error_message' => $this->faker->sentence(),
        ]);
    }
}
