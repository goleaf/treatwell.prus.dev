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
        $serviceData = [
            'Hair Services' => [
                'services' => [
                    'Signature Haircut & Style' => ['price' => [45, 85], 'duration' => [75, 120], 'description' => 'Complete hair transformation with consultation, cut, and professional styling.'],
                    'Color Correction Service' => ['price' => [120, 250], 'duration' => [180, 300], 'description' => 'Expert color correction for damaged or poorly colored hair.'],
                    'Keratin Treatment' => ['price' => [150, 300], 'duration' => [120, 180], 'description' => 'Smoothing treatment for frizzy and unmanageable hair.'],
                    'Hair Extensions Application' => ['price' => [200, 500], 'duration' => [120, 240], 'description' => 'Professional hair extension application for length and volume.'],
                    'Bridal Hair Package' => ['price' => [100, 200], 'duration' => [90, 150], 'description' => 'Complete bridal hair styling with trial session included.'],
                ],
                'metadata' => [
                    'booking_required' => true,
                    'cancellation_policy' => '48h',
                    'suitable_for' => ['adults', 'teens'],
                ],
            ],
            'Spa & Wellness' => [
                'services' => [
                    'Luxury Spa Day Package' => ['price' => [180, 350], 'duration' => [240, 360], 'description' => 'Full day relaxation package with multiple treatments.'],
                    'Couples Retreat Package' => ['price' => [250, 450], 'duration' => [180, 240], 'description' => 'Romantic spa experience designed for couples.'],
                    'Detox & Wellness Program' => ['price' => [120, 220], 'duration' => [120, 180], 'description' => 'Comprehensive wellness program for body detoxification.'],
                    'Aromatherapy Experience' => ['price' => [80, 150], 'duration' => [90, 120], 'description' => 'Relaxing aromatherapy session with essential oils.'],
                    'Meditation & Mindfulness' => ['price' => [40, 80], 'duration' => [60, 90], 'description' => 'Guided meditation and mindfulness session.'],
                ],
                'metadata' => [
                    'booking_required' => true,
                    'cancellation_policy' => '24h',
                    'suitable_for' => ['adults', 'seniors'],
                ],
            ],
            'Beauty Treatments' => [
                'services' => [
                    'Premium Anti-Aging Facial' => ['price' => [90, 180], 'duration' => [90, 120], 'description' => 'Advanced anti-aging treatment with premium products.'],
                    'Acne Treatment Program' => ['price' => [70, 140], 'duration' => [75, 90], 'description' => 'Specialized treatment program for acne-prone skin.'],
                    'Eyebrow Design & Tinting' => ['price' => [25, 50], 'duration' => [45, 60], 'description' => 'Professional eyebrow shaping and tinting service.'],
                    'Lash Lift & Tint Package' => ['price' => [45, 85], 'duration' => [60, 90], 'description' => 'Eyelash lifting and tinting for natural enhancement.'],
                    'Microblading Service' => ['price' => [200, 400], 'duration' => [120, 180], 'description' => 'Semi-permanent eyebrow tattooing technique.'],
                ],
                'metadata' => [
                    'booking_required' => true,
                    'cancellation_policy' => '24h',
                    'suitable_for' => ['adults', 'teens'],
                ],
            ],
            'Nail Services' => [
                'services' => [
                    'Luxury Manicure & Pedicure' => ['price' => [60, 120], 'duration' => [90, 120], 'description' => 'Complete nail care with premium products and massage.'],
                    'Gel Extensions with Art' => ['price' => [50, 100], 'duration' => [90, 150], 'description' => 'Gel nail extensions with custom nail art design.'],
                    'Russian Manicure' => ['price' => [40, 80], 'duration' => [75, 90], 'description' => 'Precise cuticle work using electric file technique.'],
                    'Nail Repair Service' => ['price' => [20, 40], 'duration' => [30, 45], 'description' => 'Professional nail repair and strengthening treatment.'],
                    'Bridal Nail Package' => ['price' => [80, 150], 'duration' => [120, 180], 'description' => 'Complete bridal nail service with trial session.'],
                ],
                'metadata' => [
                    'booking_required' => true,
                    'cancellation_policy' => '24h',
                    'suitable_for' => ['adults', 'teens'],
                ],
            ],
            'Massage Therapy' => [
                'services' => [
                    'Therapeutic Deep Tissue' => ['price' => [70, 120], 'duration' => [60, 90], 'description' => 'Intensive massage for muscle tension and pain relief.'],
                    'Prenatal Massage' => ['price' => [60, 100], 'duration' => [60, 75], 'description' => 'Specialized massage for expecting mothers.'],
                    'Sports Recovery Massage' => ['price' => [80, 140], 'duration' => [75, 90], 'description' => 'Targeted massage for athletes and active individuals.'],
                    'Lymphatic Drainage' => ['price' => [70, 130], 'duration' => [60, 90], 'description' => 'Gentle massage to stimulate lymphatic system.'],
                    'Reflexology Session' => ['price' => [50, 90], 'duration' => [45, 60], 'description' => 'Foot massage focusing on pressure points.'],
                ],
                'metadata' => [
                    'booking_required' => true,
                    'cancellation_policy' => '24h',
                    'suitable_for' => ['adults', 'seniors', 'pregnant'],
                ],
            ],
            'Men\'s Grooming' => [
                'services' => [
                    'Executive Grooming Package' => ['price' => [60, 120], 'duration' => [75, 90], 'description' => 'Complete grooming service for the modern gentleman.'],
                    'Traditional Hot Towel Shave' => ['price' => [35, 65], 'duration' => [45, 60], 'description' => 'Classic barbershop shave with hot towel treatment.'],
                    'Beard Styling & Maintenance' => ['price' => [25, 50], 'duration' => [30, 45], 'description' => 'Professional beard trimming and styling service.'],
                    'Men\'s Facial Treatment' => ['price' => [50, 90], 'duration' => [60, 75], 'description' => 'Skincare treatment designed specifically for men.'],
                    'Scalp Treatment & Massage' => ['price' => [40, 70], 'duration' => [45, 60], 'description' => 'Therapeutic scalp treatment and relaxing massage.'],
                ],
                'metadata' => [
                    'booking_required' => true,
                    'cancellation_policy' => '24h',
                    'suitable_for' => ['adults', 'teens'],
                ],
            ],
        ];

        $category = $this->faker->randomElement(array_keys($serviceData));
        $categoryData = $serviceData[$category];
        $serviceName = $this->faker->randomElement(array_keys($categoryData['services']));
        $serviceInfo = $categoryData['services'][$serviceName];

        $minPrice = $this->faker->numberBetween($serviceInfo['price'][0], $serviceInfo['price'][1] - 20);
        $maxPrice = $this->faker->numberBetween($minPrice + 10, $serviceInfo['price'][1]);
        $duration = $this->faker->numberBetween($serviceInfo['duration'][0], $serviceInfo['duration'][1]);

        // Sometimes use fixed price instead of range
        $price = $this->faker->boolean(40) ? $minPrice : null;
        if ($price) {
            $maxPrice = $minPrice;
        }

        $slug = \Illuminate\Support\Str::slug($serviceName);

        return [
            'venue_id' => Venue::factory(),
            'name' => $serviceName,
            'slug' => $slug,
            'description' => $serviceInfo['description'],
            'category' => $category,
            'duration' => $duration,
            'price' => $price,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'is_active' => $this->faker->boolean(90),
            'is_featured' => $this->faker->boolean(25),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'metadata' => array_merge($categoryData['metadata'], [
                'requires_consultation' => $this->faker->boolean(30),
                'includes_aftercare' => $this->faker->boolean(60),
                'group_booking_available' => $this->faker->boolean(40),
            ]),
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
            'name' => 'Featured '.$attributes['name'],
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
            $minPrice = $this->faker->numberBetween(150, 300);
            $maxPrice = $minPrice + $this->faker->numberBetween(50, 200);

            return [
                'name' => 'Premium '.$attributes['name'],
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'price' => null,
                'is_featured' => true,
                'duration' => $this->faker->randomElement([120, 150, 180, 240]),
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'premium_products' => true,
                    'includes_consultation' => true,
                    'aftercare_package' => true,
                ]),
            ];
        });
    }

    /**
     * Create a quick service with shorter duration.
     */
    public function express(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Express '.$attributes['name'],
                'duration' => $this->faker->numberBetween(15, 45),
                'min_price' => $this->faker->numberBetween(20, 50),
                'max_price' => $this->faker->numberBetween(40, 70),
                'price' => $this->faker->numberBetween(25, 60),
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'quick_service' => true,
                    'no_consultation' => true,
                ]),
            ];
        });
    }

    /**
     * Create a package service with multiple treatments.
     */
    public function package(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $attributes['name'].' Package',
                'duration' => $this->faker->numberBetween(120, 300),
                'min_price' => $this->faker->numberBetween(100, 200),
                'max_price' => $this->faker->numberBetween(200, 400),
                'price' => null,
                'is_featured' => $this->faker->boolean(60),
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'package_deal' => true,
                    'multiple_treatments' => true,
                    'includes_refreshments' => true,
                ]),
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
            ];
        });
    }

    /**
     * Create a service with fixed pricing.
     */
    public function fixedPrice(): static
    {
        return $this->state(function (array $attributes) {
            $price = $this->faker->numberBetween(30, 200);

            return [
                'price' => $price,
                'min_price' => $price,
                'max_price' => $price,
            ];
        });
    }

    /**
     * Create a service with consultation required.
     */
    public function requiresConsultation(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'requires_consultation' => true,
                    'consultation_duration' => 15,
                ]),
            ];
        });
    }

    /**
     * Create a service suitable for groups.
     */
    public function groupBooking(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Group '.$attributes['name'],
                'duration' => $this->faker->numberBetween(120, 240),
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'group_booking_available' => true,
                    'max_group_size' => $this->faker->numberBetween(4, 12),
                    'group_discount' => $this->faker->numberBetween(10, 25),
                ]),
            ];
        });
    }
}
