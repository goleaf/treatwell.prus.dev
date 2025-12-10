<?php

namespace Database\Factories;

use App\Models\Image;
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
     *
     * @return array
     */
    public function definition()
    {
        $imageId = $this->faker->unique()->numerify('IMG######');

        return [
            'venue_id' => Venue::factory(),
            'imageable_type' => Venue::class,
            'imageable_id' => function (array $attributes) {
                return $attributes['venue_id'];
            },
            'external_id' => $imageId,
            'uri_small' => 'https://cdn1.treatwell.net/images/view/v2.i'.$imageId.'.w360.h240.x'.$this->faker->hexColor().'/',
            'uri_medium' => 'https://cdn1.treatwell.net/images/view/v2.i'.$imageId.'.w720.h480.x'.$this->faker->hexColor().'/',
            'uri_large' => 'https://cdn1.treatwell.net/images/view/v2.i'.$imageId.'.w1080.h720.x'.$this->faker->hexColor().'/',
            'uri_xlarge' => 'https://cdn1.treatwell.net/images/view/v2.i'.$imageId.'.w1280.h800.x'.$this->faker->hexColor().'/',
            'is_primary' => $this->faker->boolean(20),
        ];
    }

    /**
     * Configure the factory to create a primary image.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function primary()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_primary' => true,
            ];
        });
    }
}
