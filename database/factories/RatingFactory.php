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
     */
    public function definition(): array
    {
        // Generate realistic rating distribution (most venues have good ratings)
        $baseRating = $this->faker->randomFloat(2, 3.8, 4.9);

        // Generate individual category ratings around the base rating
        $cleanlinessAvg = $this->faker->randomFloat(2, max(3.0, $baseRating - 0.3), min(5.0, $baseRating + 0.2));
        $staffAvg = $this->faker->randomFloat(2, max(3.0, $baseRating - 0.2), min(5.0, $baseRating + 0.3));
        $atmosphereAvg = $this->faker->randomFloat(2, max(3.0, $baseRating - 0.2), min(5.0, $baseRating + 0.2));

        // Calculate weighted average from individual ratings
        $weightedAverage = ($cleanlinessAvg + $staffAvg + $atmosphereAvg) / 3;

        // Generate realistic review count (newer venues have fewer reviews)
        $count = $this->faker->numberBetween(3, 150);

        // Individual category counts might vary slightly
        $cleanlinessCount = $this->faker->numberBetween(max(1, $count - 5), $count);
        $staffCount = $this->faker->numberBetween(max(1, $count - 3), $count);
        $atmosphereCount = $this->faker->numberBetween(max(1, $count - 4), $count);

        // Generate realistic reviewer information for individual reviews
        $reviewerName = $this->faker->boolean(70) ? $this->faker->firstName().' '.substr($this->faker->lastName(), 0, 1).'.' : null;
        $reviewerEmail = $this->faker->boolean(30) ? $this->faker->safeEmail() : null;

        // Individual review rating and comment
        $individualRating = $this->faker->boolean(60) ? $this->faker->randomFloat(1, 3.0, 5.0) : null;

        $comments = [
            'Great service and friendly staff!',
            'Very professional and clean environment.',
            'Excellent results, will definitely come back.',
            'Good value for money.',
            'Staff was very knowledgeable and helpful.',
            'Beautiful salon with relaxing atmosphere.',
            'Quick service without compromising quality.',
            'Highly recommend this place!',
            'Professional treatment, very satisfied.',
            'Clean facilities and modern equipment.',
        ];

        $comment = $this->faker->boolean(40) ? $this->faker->randomElement($comments) : null;

        return [
            'venue_id' => Venue::factory(),
            'weighted_average' => round($weightedAverage, 2),
            'count' => $count,
            'cleanliness_avg' => round($cleanlinessAvg, 2),
            'cleanliness_count' => $cleanlinessCount,
            'staff_avg' => round($staffAvg, 2),
            'staff_count' => $staffCount,
            'atmosphere_avg' => round($atmosphereAvg, 2),
            'atmosphere_count' => $atmosphereCount,
            'display_average' => number_format($weightedAverage, 1),
            'reviewer_name' => $reviewerName,
            'reviewer_email' => $reviewerEmail,
            'rating' => $individualRating,
            'comment' => $comment,
            'is_verified' => $this->faker->boolean(75),
        ];
    }

    /**
     * Configure the factory to create excellent ratings.
     */
    public function excellent(): static
    {
        return $this->state(function (array $attributes) {
            $baseRating = $this->faker->randomFloat(2, 4.7, 5.0);

            return [
                'weighted_average' => $baseRating,
                'cleanliness_avg' => $this->faker->randomFloat(2, 4.6, 5.0),
                'staff_avg' => $this->faker->randomFloat(2, 4.7, 5.0),
                'atmosphere_avg' => $this->faker->randomFloat(2, 4.5, 5.0),
                'display_average' => number_format($baseRating, 1),
                'count' => $this->faker->numberBetween(20, 200),
                'rating' => $this->faker->randomFloat(1, 4.5, 5.0),
                'comment' => $this->faker->randomElement([
                    'Outstanding service! Exceeded all expectations.',
                    'Absolutely perfect experience from start to finish.',
                    'The best salon I\'ve ever been to!',
                    'Incredible results and amazing staff.',
                    'Five stars - couldn\'t be happier!',
                ]),
            ];
        });
    }

    /**
     * Configure the factory to create poor ratings.
     */
    public function poor(): static
    {
        return $this->state(function (array $attributes) {
            $baseRating = $this->faker->randomFloat(2, 2.0, 3.2);

            return [
                'weighted_average' => $baseRating,
                'cleanliness_avg' => $this->faker->randomFloat(2, 2.0, 3.5),
                'staff_avg' => $this->faker->randomFloat(2, 1.8, 3.0),
                'atmosphere_avg' => $this->faker->randomFloat(2, 2.2, 3.3),
                'display_average' => number_format($baseRating, 1),
                'count' => $this->faker->numberBetween(5, 30),
                'rating' => $this->faker->randomFloat(1, 1.0, 3.0),
                'comment' => $this->faker->randomElement([
                    'Not satisfied with the service.',
                    'Could be much better.',
                    'Had some issues with the treatment.',
                    'Staff seemed inexperienced.',
                    'Facilities need improvement.',
                ]),
            ];
        });
    }

    /**
     * Configure the factory to create a verified review.
     */
    public function verified(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_verified' => true,
                'reviewer_name' => $this->faker->firstName().' '.substr($this->faker->lastName(), 0, 1).'.',
                'comment' => $this->faker->randomElement([
                    'Great service and professional staff.',
                    'Very satisfied with the results.',
                    'Clean and comfortable environment.',
                    'Excellent value for money.',
                    'Will definitely return.',
                ]),
            ];
        });
    }

    /**
     * Configure the factory for a new venue with few reviews.
     */
    public function newVenue(): static
    {
        return $this->state(function (array $attributes) {
            $count = $this->faker->numberBetween(1, 8);

            return [
                'count' => $count,
                'cleanliness_count' => $count,
                'staff_count' => $count,
                'atmosphere_count' => $count,
            ];
        });
    }

    /**
     * Configure the factory for a popular venue with many reviews.
     */
    public function popular(): static
    {
        return $this->state(function (array $attributes) {
            $count = $this->faker->numberBetween(100, 500);

            return [
                'count' => $count,
                'cleanliness_count' => $this->faker->numberBetween($count - 20, $count),
                'staff_count' => $this->faker->numberBetween($count - 15, $count),
                'atmosphere_count' => $this->faker->numberBetween($count - 25, $count),
            ];
        });
    }
}
