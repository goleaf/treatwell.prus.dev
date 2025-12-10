<?php

namespace Database\Factories;

use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

class TreatmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Treatment::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $treatmentData = [
            'Haircut' => [
                'treatments' => [
                    'Women\'s Haircut & Blow Dry' => ['price' => [35, 65], 'duration' => [60, 90]],
                    'Men\'s Haircut' => ['price' => [20, 35], 'duration' => [30, 45]],
                    'Children\'s Haircut' => ['price' => [15, 25], 'duration' => [30, 45]],
                    'Haircut & Styling' => ['price' => [45, 80], 'duration' => [75, 120]],
                    'Fringe Trim' => ['price' => [10, 20], 'duration' => [15, 30]],
                ],
                'descriptions' => [
                    'Professional haircut tailored to your face shape and lifestyle.',
                    'Expert styling with premium products for a perfect finish.',
                    'Consultation included to achieve your desired look.',
                ],
            ],
            'Hair Color' => [
                'treatments' => [
                    'Full Head Color' => ['price' => [60, 120], 'duration' => [120, 180]],
                    'Root Touch-Up' => ['price' => [40, 70], 'duration' => [60, 90]],
                    'Highlights (Half Head)' => ['price' => [70, 110], 'duration' => [90, 150]],
                    'Highlights (Full Head)' => ['price' => [90, 150], 'duration' => [120, 180]],
                    'Balayage' => ['price' => [100, 180], 'duration' => [150, 240]],
                    'Ombre' => ['price' => [80, 140], 'duration' => [120, 180]],
                ],
                'descriptions' => [
                    'Professional color service using premium products.',
                    'Color consultation included to find your perfect shade.',
                    'Aftercare advice and products recommended.',
                ],
            ],
            'Massage' => [
                'treatments' => [
                    'Swedish Massage' => ['price' => [45, 75], 'duration' => [60, 90]],
                    'Deep Tissue Massage' => ['price' => [55, 85], 'duration' => [60, 90]],
                    'Hot Stone Massage' => ['price' => [65, 95], 'duration' => [75, 90]],
                    'Aromatherapy Massage' => ['price' => [50, 80], 'duration' => [60, 90]],
                    'Sports Massage' => ['price' => [60, 90], 'duration' => [60, 75]],
                    'Couples Massage' => ['price' => [120, 180], 'duration' => [60, 90]],
                ],
                'descriptions' => [
                    'Relaxing massage to relieve tension and stress.',
                    'Therapeutic treatment for muscle recovery.',
                    'Customized pressure and technique for your needs.',
                ],
            ],
            'Facial' => [
                'treatments' => [
                    'Classic Facial' => ['price' => [40, 70], 'duration' => [60, 75]],
                    'Deep Cleansing Facial' => ['price' => [50, 80], 'duration' => [75, 90]],
                    'Anti-Aging Facial' => ['price' => [70, 120], 'duration' => [75, 90]],
                    'Hydrating Facial' => ['price' => [45, 75], 'duration' => [60, 75]],
                    'Acne Treatment Facial' => ['price' => [55, 85], 'duration' => [60, 90]],
                    'Microdermabrasion' => ['price' => [80, 120], 'duration' => [45, 60]],
                ],
                'descriptions' => [
                    'Customized facial treatment for your skin type.',
                    'Professional skincare using premium products.',
                    'Includes skin analysis and aftercare advice.',
                ],
            ],
            'Manicure' => [
                'treatments' => [
                    'Classic Manicure' => ['price' => [25, 40], 'duration' => [45, 60]],
                    'Gel Manicure' => ['price' => [35, 55], 'duration' => [60, 75]],
                    'French Manicure' => ['price' => [30, 50], 'duration' => [45, 60]],
                    'Spa Manicure' => ['price' => [40, 65], 'duration' => [60, 90]],
                    'Nail Art' => ['price' => [45, 75], 'duration' => [60, 90]],
                ],
                'descriptions' => [
                    'Professional nail care and polish application.',
                    'Includes cuticle care and hand massage.',
                    'Long-lasting results with quality products.',
                ],
            ],
            'Pedicure' => [
                'treatments' => [
                    'Classic Pedicure' => ['price' => [30, 50], 'duration' => [45, 60]],
                    'Gel Pedicure' => ['price' => [40, 65], 'duration' => [60, 75]],
                    'Spa Pedicure' => ['price' => [45, 75], 'duration' => [75, 90]],
                    'Medical Pedicure' => ['price' => [50, 80], 'duration' => [60, 75]],
                    'Callus Removal' => ['price' => [35, 55], 'duration' => [45, 60]],
                ],
                'descriptions' => [
                    'Complete foot care and nail treatment.',
                    'Relaxing treatment with foot massage.',
                    'Hygienic and professional service.',
                ],
            ],
        ];

        $category = $this->faker->randomElement(array_keys($treatmentData));
        $categoryData = $treatmentData[$category];
        $treatmentName = $this->faker->randomElement(array_keys($categoryData['treatments']));
        $treatmentInfo = $categoryData['treatments'][$treatmentName];

        $minPrice = $this->faker->numberBetween($treatmentInfo['price'][0], $treatmentInfo['price'][1] - 10);
        $maxPrice = $this->faker->numberBetween($minPrice + 5, $treatmentInfo['price'][1]);
        $minDuration = $this->faker->numberBetween($treatmentInfo['duration'][0], $treatmentInfo['duration'][1] - 15);
        $maxDuration = $this->faker->numberBetween($minDuration + 15, $treatmentInfo['duration'][1]);

        // Generate realistic options
        $options = [];
        if ($this->faker->boolean(70)) {
            $options[] = [
                'id' => 1,
                'name' => 'Duration',
                'options' => [
                    ['id' => 1, 'name' => $minDuration.' minutes', 'price' => $minPrice],
                    ['id' => 2, 'name' => $maxDuration.' minutes', 'price' => $maxPrice],
                ],
            ];
        }

        if ($this->faker->boolean(40)) {
            $options[] = [
                'id' => 2,
                'name' => 'Add-ons',
                'options' => [
                    ['id' => 1, 'name' => 'Premium Products', 'price' => $this->faker->numberBetween(5, 15)],
                    ['id' => 2, 'name' => 'Extended Treatment', 'price' => $this->faker->numberBetween(10, 25)],
                ],
            ];
        }

        $description = $this->faker->randomElement($categoryData['descriptions']);
        $slug = \Illuminate\Support\Str::slug($treatmentName);

        return [
            'venue_id' => Venue::factory(),
            'external_id' => $this->faker->unique()->numerify('T######'),
            'name' => $treatmentName,
            'slug' => $slug,
            'description' => $description,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'min_duration' => $minDuration,
            'max_duration' => $maxDuration,
            'category_id' => $this->faker->numberBetween(1, 20),
            'category_name' => $category,
            'category' => $category,
            'options' => $options,
            'is_active' => $this->faker->boolean(90),
        ];
    }

    /**
     * Configure the factory to create a premium treatment.
     */
    public function premium(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'min_price' => $attributes['min_price'] * 1.5,
                'max_price' => $attributes['max_price'] * 1.5,
                'max_duration' => $attributes['max_duration'] + 30,
                'name' => 'Premium '.$attributes['name'],
            ];
        });
    }

    /**
     * Configure the factory to create a quick treatment.
     */
    public function quick(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'min_duration' => 15,
                'max_duration' => 30,
                'min_price' => $this->faker->numberBetween(10, 25),
                'max_price' => $this->faker->numberBetween(20, 35),
                'name' => 'Express '.$attributes['name'],
            ];
        });
    }

    /**
     * Configure the factory for a specific category.
     */
    public function category(string $category): static
    {
        return $this->state(function (array $attributes) use ($category) {
            return [
                'category' => $category,
                'category_name' => $category,
            ];
        });
    }

    /**
     * Configure the factory to create an inactive treatment.
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * Configure the factory to create a treatment with fixed price.
     */
    public function fixedPrice(): static
    {
        return $this->state(function (array $attributes) {
            $price = $this->faker->numberBetween(25, 150);

            return [
                'price' => $price,
                'min_price' => $price,
                'max_price' => $price,
            ];
        });
    }

    /**
     * Configure the factory to create a treatment with price range.
     */
    public function priceRange(): static
    {
        return $this->state(function (array $attributes) {
            $minPrice = $this->faker->numberBetween(20, 80);
            $maxPrice = $minPrice + $this->faker->numberBetween(20, 100);

            return [
                'price' => null,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
            ];
        });
    }

    /**
     * Configure the factory to create a treatment with duration range.
     */
    public function durationRange(): static
    {
        return $this->state(function (array $attributes) {
            $minDuration = $this->faker->numberBetween(30, 60);
            $maxDuration = $minDuration + $this->faker->numberBetween(30, 90);

            return [
                'duration' => null,
                'min_duration' => $minDuration,
                'max_duration' => $maxDuration,
            ];
        });
    }

    /**
     * Configure the factory to create a treatment with options.
     */
    public function withOptions(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'options' => [
                    [
                        'id' => 1,
                        'name' => 'Duration',
                        'options' => [
                            ['id' => 1, 'name' => '60 minutes', 'price' => $attributes['min_price'] ?? 50],
                            ['id' => 2, 'name' => '90 minutes', 'price' => ($attributes['min_price'] ?? 50) + 25],
                        ],
                    ],
                    [
                        'id' => 2,
                        'name' => 'Add-ons',
                        'options' => [
                            ['id' => 1, 'name' => 'Premium Products', 'price' => 10],
                            ['id' => 2, 'name' => 'Extended Treatment', 'price' => 20],
                        ],
                    ],
                ],
            ];
        });
    }
}
