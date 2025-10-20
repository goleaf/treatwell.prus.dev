<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Location;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'venue_id' => Venue::factory(),
            'city_id' => City::factory(),
            'postal_code' => $this->faker->postcode(),
            'address_line1' => $this->faker->streetAddress(),
            'address_line2' => $this->faker->secondaryAddress(),
            'latitude' => $this->faker->latitude(54.0, 56.0),  // Lithuania's approx. latitude range
            'longitude' => $this->faker->longitude(21.0, 26.0), // Lithuania's approx. longitude range
            'map_zoom' => $this->faker->numberBetween(12, 16)
        ];
    }
} 