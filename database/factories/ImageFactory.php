<?php

namespace Database\Factories;

use App\Models\Image;
use App\Models\Service;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Image::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $imageId = $this->faker->unique()->numerify('IMG######');

        // Generate realistic image dimensions and URLs
        $baseUrl = 'https://cdn1.treatwell.net/images/view/v2.i'.$imageId;
        $colorHex = substr($this->faker->hexColor(), 1); // Remove # from hex color

        // Realistic image descriptions for alt text
        $imageTypes = [
            'salon-interior' => 'Modern salon interior with comfortable seating',
            'treatment-room' => 'Clean and relaxing treatment room',
            'reception-area' => 'Welcoming reception area',
            'staff-photo' => 'Professional staff member',
            'before-after' => 'Treatment results showcase',
            'equipment' => 'Professional beauty equipment',
            'exterior' => 'Salon exterior and entrance',
            'product-display' => 'Premium beauty products display',
        ];

        $imageType = $this->faker->randomElement(array_keys($imageTypes));
        $altText = $imageTypes[$imageType];

        return [
            'venue_id' => Venue::factory(),
            'imageable_type' => Venue::class,
            'imageable_id' => function (array $attributes) {
                return $attributes['venue_id'];
            },
            'external_id' => $imageId,
            'path' => '/storage/images/'.$imageId.'.jpg',
            'uri_small' => $baseUrl.'.w360.h240.x'.$colorHex.'/',
            'uri_medium' => $baseUrl.'.w720.h480.x'.$colorHex.'/',
            'uri_large' => $baseUrl.'.w1080.h720.x'.$colorHex.'/',
            'uri_xlarge' => $baseUrl.'.w1440.h960.x'.$colorHex.'/',
            'alt_text' => $altText,
            'is_primary' => $this->faker->boolean(15),
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Configure the factory to create a primary image.
     */
    public function primary(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_primary' => true,
                'sort_order' => 1,
                'alt_text' => 'Main image of '.($attributes['venue_id'] ? 'venue' : 'business'),
            ];
        });
    }

    /**
     * Configure the factory for a specific venue.
     */
    public function forVenue(Venue $venue): static
    {
        return $this->state(function (array $attributes) use ($venue) {
            return [
                'venue_id' => $venue->id,
                'imageable_type' => Venue::class,
                'imageable_id' => $venue->id,
                'alt_text' => 'Image of '.$venue->name,
            ];
        });
    }

    /**
     * Configure the factory for a treatment.
     */
    public function forTreatment(Treatment $treatment): static
    {
        return $this->state(function (array $attributes) use ($treatment) {
            return [
                'venue_id' => $treatment->venue_id,
                'imageable_type' => Treatment::class,
                'imageable_id' => $treatment->id,
                'alt_text' => $treatment->name.' treatment image',
            ];
        });
    }

    /**
     * Configure the factory for a service.
     */
    public function forService(Service $service): static
    {
        return $this->state(function (array $attributes) use ($service) {
            return [
                'venue_id' => $service->venue_id,
                'imageable_type' => Service::class,
                'imageable_id' => $service->id,
                'alt_text' => $service->name.' service image',
            ];
        });
    }

    /**
     * Configure the factory to create interior images.
     */
    public function interior(): static
    {
        return $this->state(function (array $attributes) {
            $interiorTypes = [
                'Modern salon interior with stylish decor',
                'Comfortable waiting area with magazines',
                'Clean treatment room with professional equipment',
                'Relaxing atmosphere with ambient lighting',
                'Spacious salon floor with multiple stations',
            ];

            return [
                'alt_text' => $this->faker->randomElement($interiorTypes),
            ];
        });
    }

    /**
     * Configure the factory to create staff photos.
     */
    public function staff(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'alt_text' => 'Professional staff member at work',
                'sort_order' => $this->faker->numberBetween(5, 8),
            ];
        });
    }

    /**
     * Configure the factory to create before/after images.
     */
    public function beforeAfter(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'alt_text' => 'Before and after treatment results',
                'sort_order' => $this->faker->numberBetween(3, 6),
            ];
        });
    }

    /**
     * Configure the factory with a specific sort order.
     */
    public function sortOrder(int $order): static
    {
        return $this->state(function (array $attributes) use ($order) {
            return [
                'sort_order' => $order,
            ];
        });
    }

    /**
     * Configure the factory to create a gallery of images.
     */
    public function gallery(): static
    {
        return $this->state(function (array $attributes) {
            $galleryTypes = [
                'Interior gallery showcasing salon atmosphere',
                'Treatment room gallery with professional setup',
                'Staff gallery featuring team members',
                'Results gallery showing before and after',
                'Equipment gallery displaying modern tools',
            ];

            return [
                'alt_text' => $this->faker->randomElement($galleryTypes),
                'is_primary' => false,
                'sort_order' => $this->faker->numberBetween(2, 10),
            ];
        });
    }

    /**
     * Configure the factory to create high-resolution images.
     */
    public function highResolution(): static
    {
        return $this->state(function (array $attributes) {
            $imageId = $attributes['external_id'] ?? $this->faker->numerify('IMG######');
            $baseUrl = 'https://cdn1.treatwell.net/images/view/v2.i'.$imageId;
            $colorHex = substr($this->faker->hexColor(), 1);

            return [
                'uri_xlarge' => $baseUrl.'.w1920.h1280.x'.$colorHex.'/',
                'alt_text' => 'High resolution '.strtolower($attributes['alt_text'] ?? 'image'),
            ];
        });
    }
}
