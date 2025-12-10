<?php

namespace Database\Factories;

use App\Models\ApiCallLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiCallLog>
 */
class ApiCallLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statusCode = $this->faker->randomElement([200, 201, 400, 404, 500, 503]);
        $isError = $statusCode >= 400;

        return [
            'endpoint' => $this->faker->url(),
            'city_slug' => $this->faker->slug(),
            'status_code' => $statusCode,
            'response_time' => $this->faker->numberBetween(100, 10000), // 100ms to 10s
            'data_points_extracted' => $isError ? 0 : $this->faker->numberBetween(1, 100),
            'error_message' => $isError ? $this->faker->sentence() : null,
            'called_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the API call was successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_code' => $this->faker->randomElement([200, 201, 202]),
            'error_message' => null,
            'data_points_extracted' => $this->faker->numberBetween(1, 100),
        ]);
    }

    /**
     * Indicate that the API call failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_code' => $this->faker->randomElement([400, 404, 500, 503]),
            'error_message' => $this->faker->sentence(),
            'data_points_extracted' => 0,
        ]);
    }

    /**
     * Indicate that the API call was slow.
     */
    public function slow(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_time' => $this->faker->numberBetween(5000, 30000), // 5s to 30s
        ]);
    }

    /**
     * Indicate that the API call was fast.
     */
    public function fast(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_time' => $this->faker->numberBetween(100, 1000), // 100ms to 1s
        ]);
    }

    /**
     * Set a specific endpoint.
     */
    public function forEndpoint(string $endpoint): static
    {
        return $this->state(fn (array $attributes) => [
            'endpoint' => $endpoint,
        ]);
    }

    /**
     * Set a specific city slug.
     */
    public function forCity(string $citySlug): static
    {
        return $this->state(fn (array $attributes) => [
            'city_slug' => $citySlug,
        ]);
    }
}
