<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class CityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = City::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $cityName = $this->faker->city();

        return [
            'entity_id' => strtolower(str_replace(' ', '-', $cityName)).'-lt',
            'slug' => strtolower(str_replace(' ', '-', $cityName)),
            'country_id' => function () {
                return Country::firstOrCreate(
                    ['code' => 'LT'],
                    [
                        'name' => 'Lithuania',
                        'slug' => 'lithuania',
                    ]
                )->id;
            },
            'name' => $cityName,
            'normalised_name' => strtolower(str_replace(' ', '-', $cityName)).'-lt',
            'latitude' => $this->faker->latitude(54.0, 56.0),  // Lithuania's approx. latitude range
            'longitude' => $this->faker->longitude(21.0, 26.0),  // Lithuania's approx. longitude range
            'type' => 'city',
            'radius_distance' => $this->faker->numberBetween(5, 30),
            'radius_unit' => 'km',
        ];
    }
}
