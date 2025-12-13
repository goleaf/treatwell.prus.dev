<?php

namespace Database\Factories;

use App\Models\Rating;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

class RatingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Rating::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $cleanlinessAvg = $this->faker->randomFloat(2, 3.0, 5.0);
        $staffAvg = $this->faker->randomFloat(2, 3.0, 5.0);
        $atmosphereAvg = $this->faker->randomFloat(2, 3.0, 5.0);

        $count = $this->faker->numberBetween(5, 100);
        $weightedAverage = $this->faker->randomFloat(2, 3.5, 5.0);

        return [
            'venue_id' => Venue::factory(),
            'weighted_average' => $weightedAverage,
            'count' => $count,
            'cleanliness_avg' => $cleanlinessAvg,
            'cleanliness_count' => $count,
            'staff_avg' => $staffAvg,
            'staff_count' => $count,
            'atmosphere_avg' => $atmosphereAvg,
            'atmosphere_count' => $count,
            'display_average' => number_format($weightedAverage, 1),
            'rating' => $this->faker->numberBetween(1, 5),
            'is_verified' => $this->faker->boolean(70),
        ];
    }
}
