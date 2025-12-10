<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

class VenueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Venue::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $venueTypes = [
            'Hair Salon' => ['Hair Studio', 'Hair Lounge', 'Hair Boutique', 'Salon', 'Hair & Beauty'],
            'Spa' => ['Day Spa', 'Wellness Center', 'Spa & Wellness', 'Luxury Spa', 'Urban Spa'],
            'Beauty Salon' => ['Beauty Studio', 'Beauty Bar', 'Beauty Lounge', 'Cosmetic Studio'],
            'Nail Salon' => ['Nail Studio', 'Nail Bar', 'Nail Lounge', 'Manicure Studio'],
            'Massage' => ['Massage Studio', 'Therapy Center', 'Wellness Studio', 'Relaxation Center'],
            'Barbershop' => ['Barber Shop', 'Gentlemen\'s Barber', 'Traditional Barber', 'Modern Barber'],
        ];

        $typeCategory = $this->faker->randomElement(array_keys($venueTypes));
        $typeVariations = $venueTypes[$typeCategory];
        $specificType = $this->faker->randomElement($typeVariations);

        // Generate realistic business names
        $businessNames = [
            'Elite', 'Premium', 'Luxury', 'Modern', 'Classic', 'Urban', 'Boutique',
            'Royal', 'Golden', 'Crystal', 'Diamond', 'Pearl', 'Silk', 'Velvet',
            'Zen', 'Harmony', 'Serenity', 'Bliss', 'Pure', 'Fresh', 'Glow',
        ];

        $businessName = $this->faker->randomElement($businessNames).' '.$specificType;
        $baseSlug = \Illuminate\Support\Str::slug($businessName);
        $slug = $baseSlug;

        // Make slug unique if needed
        $counter = 1;
        while (\App\Models\Venue::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        // Generate realistic coordinates for Lithuania
        $latitude = $this->faker->randomFloat(6, 54.0, 56.5);
        $longitude = $this->faker->randomFloat(6, 20.5, 26.8);

        // Generate realistic contact information
        $phone = $this->faker->boolean(85) ? '+370 '.$this->faker->numerify('### #####') : null;
        $email = $this->faker->boolean(70) ? strtolower(str_replace(' ', '', $businessName)).'@'.$this->faker->safeEmailDomain() : null;
        $website = $this->faker->boolean(60) ? 'https://www.'.strtolower(str_replace(' ', '', $businessName)).'.lt' : null;

        // Generate realistic descriptions
        $descriptions = [
            'Professional beauty services in a relaxing atmosphere.',
            'Expert stylists providing personalized treatments.',
            'Modern facility with state-of-the-art equipment.',
            'Experienced team dedicated to your beauty needs.',
            'Premium services using high-quality products.',
            'Comfortable environment with friendly staff.',
            'Innovative treatments and classic services.',
            'Your beauty destination in the heart of the city.',
        ];

        return [
            'city_id' => City::factory(),
            'external_id' => $this->faker->unique()->numerify('V######'),
            'name' => $businessName,
            'slug' => $slug,
            'description' => $this->faker->randomElement($descriptions).' '.$this->faker->sentence(),
            'address' => $this->faker->streetAddress(),
            'type_id' => $this->faker->numberBetween(1, 20),
            'type_name' => $typeCategory,
            'normalised_name' => strtolower(str_replace([' ', '&', '\''], ['-', 'and', ''], $typeCategory)),
            'desktop_uri' => '/salon/'.$slug,
            'mobile_uri' => '/salon/'.$slug,
            'app_uri' => '/salon/'.$slug,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'phone' => $phone,
            'email' => $email,
            'website' => $website,
            'rating' => $this->faker->randomFloat(2, 3.5, 5.0),
            'rating_count' => $this->faker->numberBetween(5, 150),
            'is_new_venue' => $this->faker->boolean(15),
            'is_active' => $this->faker->boolean(95),
            'raw_data' => [
                'source' => 'treatwell_api',
                'imported_at' => now()->toISOString(),
                'external_data' => [
                    'id' => $this->faker->numerify('######'),
                    'category' => $typeCategory,
                    'verified' => $this->faker->boolean(80),
                ],
            ],
        ];
    }

    /**
     * Configure the factory to create a premium venue.
     */
    public function premium(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Luxury '.$attributes['name'],
                'rating' => $this->faker->randomFloat(2, 4.5, 5.0),
                'rating_count' => $this->faker->numberBetween(50, 300),
                'website' => 'https://www.'.\Illuminate\Support\Str::slug($attributes['name']).'.com',
            ];
        });
    }

    /**
     * Configure the factory to create a new venue.
     */
    public function newVenue(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_new_venue' => true,
                'rating' => null,
                'rating_count' => 0,
            ];
        });
    }

    /**
     * Configure the factory to create an inactive venue.
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
     * Configure the factory for a specific city.
     */
    public function inCity(City $city): static
    {
        return $this->state(function (array $attributes) use ($city) {
            return [
                'city_id' => $city->id,
                'latitude' => $city->latitude + $this->faker->randomFloat(4, -0.05, 0.05),
                'longitude' => $city->longitude + $this->faker->randomFloat(4, -0.05, 0.05),
            ];
        });
    }

    /**
     * Configure the factory to create a venue with complete contact information.
     */
    public function withFullContact(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'phone' => '+370 '.$this->faker->numerify('### #####'),
                'email' => strtolower(str_replace(' ', '', $attributes['name'])).'@'.$this->faker->safeEmailDomain(),
                'website' => 'https://www.'.strtolower(str_replace(' ', '', $attributes['name'])).'.lt',
            ];
        });
    }

    /**
     * Configure the factory to create a venue with minimal information.
     */
    public function minimal(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'phone' => null,
                'email' => null,
                'website' => null,
                'description' => null,
                'rating' => null,
                'rating_count' => 0,
            ];
        });
    }

    /**
     * Configure the factory to create a venue with high ratings.
     */
    public function highlyRated(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'rating' => $this->faker->randomFloat(2, 4.5, 5.0),
                'rating_count' => $this->faker->numberBetween(50, 200),
            ];
        });
    }
}
