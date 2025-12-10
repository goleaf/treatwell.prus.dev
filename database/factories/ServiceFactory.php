<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $serviceCategories = [
            'Hair Services',
            'Spa & Wellness',
            'Beauty Treatments',
            'Nail Services',
            'Massage Therapy',
            'Skincare',
            'Body Treatments',
            'Men\'s Grooming',
        ];

        $serviceNamesByCategory = [
            'Hair Services' => ['Haircut & Style', 'Hair Coloring', 'Hair Treatment', 'Blowdry', 'Hair Extensions'],
            'Spa & Wellness' => ['Full Body Massage', 'Relaxation Package', 'Aromatherapy', 'Hot Stone Therapy'],
            'Beauty Treatments' => ['Facial Treatment', 'Anti-Aging Facial', 'Deep Cleansing', 'Eyebrow Shaping'],
            'Nail Services' => ['Manicure', 'Pedicure', 'Gel Polish', 'Nail Art', 'Nail Extensions'],
            'Massage Therapy' => ['Swedish Massage', 'Deep Tissue', 'Sports Massage', 'Couples Massage'],
            'Skincare' => ['Hydrating Facial', 'Acne Treatment', 'Chemical Peel', 'Microdermabrasion'],
            'Body Treatments' => ['Body Scrub', 'Body Wrap', 'Cellulite Treatment', 'Tanning'],
            'Men\'s Grooming' => ['Men\'s Haircut', 'Beard Trim', 'Men\'s Facial', 'Hot Towel Shave'],
        ];

        $category = $this->faker->randomElement($serviceCategories);
        $serviceNames = $serviceNamesByCategory[$category] ?? ['Standard Service'];
        $name = $this->faker->randomElement($serviceNames);

        $minPrice = $this->faker->numberBetween(15, 80);
        $maxPrice = $this->faker->boolean(60) ? ($minPrice + $this->faker->numberBetween(10, 50)) : $minPrice;
        $price = $minPrice === $maxPrice ? $minPrice : null;

        return [
            'venue_id' => Venue::factory(),
            'name' => $name,
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->paragraph(2),
            'category' => $category,
            'duration' => $this->faker->randomElement([30, 45, 60, 90, 120]),
            'price' => $price,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'is_active' => $this->faker->boolean(85),
            'is_featured' => $this->faker->boolean(20),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'metadata' => [
                'booking_required' => $this->faker->boolean(80),
                'cancellation_policy' => $this->faker->randomElement(['24h', '48h', '72h']),
                'suitable_for' => $this->faker->randomElements(['adults', 'teens', 'seniors', 'pregnant'], $this->faker->numberBetween(1, 3)),
            ],
        ];
    }

    /**
     * Indicate that the service is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'sort_order' => $this->faker->numberBetween(1, 10),
        ]);
    }

    /**
     * Indicate that the service is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a premium service with higher prices.
     */
    public function premium(): static
    {
        return $this->state(function (array $attributes) {
            $minPrice = $this->faker->numberBetween(100, 200);
            $maxPrice = $minPrice + $this->faker->numberBetween(50, 150);

            return [
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'price' => null,
                'is_featured' => true,
                'duration' => $this->faker->randomElement([90, 120, 150, 180]),
            ];
        });
    }
}
