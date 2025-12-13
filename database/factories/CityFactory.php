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
     */
    public function definition(): array
    {
        // Real Lithuanian cities with approximate coordinates
        $lithuanianCities = [
            'Vilnius' => ['lat' => 54.6872, 'lng' => 25.2797, 'main' => true, 'radius' => 25],
            'Kaunas' => ['lat' => 54.8985, 'lng' => 23.9036, 'main' => true, 'radius' => 20],
            'Klaipėda' => ['lat' => 55.7033, 'lng' => 21.1443, 'main' => true, 'radius' => 15],
            'Šiauliai' => ['lat' => 55.9349, 'lng' => 23.3135, 'main' => true, 'radius' => 15],
            'Panevėžys' => ['lat' => 55.7348, 'lng' => 24.3625, 'main' => true, 'radius' => 12],
            'Alytus' => ['lat' => 54.3963, 'lng' => 24.0455, 'main' => false, 'radius' => 10],
            'Marijampolė' => ['lat' => 54.5593, 'lng' => 23.3540, 'main' => false, 'radius' => 8],
            'Mažeikiai' => ['lat' => 56.3089, 'lng' => 22.3431, 'main' => false, 'radius' => 8],
            'Jonava' => ['lat' => 55.0772, 'lng' => 24.2790, 'main' => false, 'radius' => 6],
            'Utena' => ['lat' => 55.4975, 'lng' => 25.5998, 'main' => false, 'radius' => 8],
            'Kėdainiai' => ['lat' => 55.2914, 'lng' => 23.9740, 'main' => false, 'radius' => 6],
            'Telšiai' => ['lat' => 55.9814, 'lng' => 22.2470, 'main' => false, 'radius' => 6],
            'Visaginas' => ['lat' => 55.5969, 'lng' => 26.4203, 'main' => false, 'radius' => 5],
            'Tauragė' => ['lat' => 55.2522, 'lng' => 22.2889, 'main' => false, 'radius' => 6],
            'Ukmergė' => ['lat' => 55.2497, 'lng' => 24.7563, 'main' => false, 'radius' => 6],
        ];

        // Use real city or generate fake one
        if ($this->faker->boolean(70)) {
            $cityName = $this->faker->randomElement(array_keys($lithuanianCities));
            $cityData = $lithuanianCities[$cityName];
            $latitude = $cityData['lat'] + $this->faker->randomFloat(4, -0.01, 0.01);
            $longitude = $cityData['lng'] + $this->faker->randomFloat(4, -0.01, 0.01);
            $isMainCity = $cityData['main'];
            $radiusDistance = $cityData['radius'];
        } else {
            $cityName = $this->faker->city();
            $latitude = $this->faker->randomFloat(6, 54.0, 56.5);
            $longitude = $this->faker->randomFloat(6, 20.5, 26.8);
            $isMainCity = $this->faker->boolean(20);
            $radiusDistance = $this->faker->numberBetween(5, 30);
        }

        $baseSlug = \Illuminate\Support\Str::slug($cityName);
        $slug = $baseSlug;

        // Make slug unique if needed
        $counter = 1;
        while (\App\Models\City::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        $entityId = $slug.'-lt';
        $normalisedName = $slug.'-lt';

        return [
            'entity_id' => $entityId,
            'slug' => $slug,
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
            'normalised_name' => $normalisedName,
            'is_main_city' => $isMainCity,
            'subregion' => $isMainCity ? null : $this->faker->randomElement(['Vilnius County', 'Kaunas County', 'Klaipėda County', 'Šiauliai County', 'Panevėžys County']),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'type' => $this->faker->randomElement(['city', 'town', 'municipality']),
            'radius_distance' => $radiusDistance,
            'radius_unit' => 'km',
            'main_city_id' => null, // Will be set by relationships if needed
        ];
    }

    /**
     * Configure the factory to create a main city.
     */
    public function mainCity(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_main_city' => true,
                'subregion' => null,
                'radius_distance' => $this->faker->numberBetween(15, 30),
                'type' => 'city',
            ];
        });
    }

    /**
     * Configure the factory to create a subregion of a main city.
     */
    public function subregionOf(City $mainCity): static
    {
        return $this->state(function (array $attributes) use ($mainCity) {
            return [
                'is_main_city' => false,
                'main_city_id' => $mainCity->id,
                'country_id' => $mainCity->country_id,
                'subregion' => $mainCity->name.' County',
                'latitude' => $mainCity->latitude + $this->faker->randomFloat(4, -0.1, 0.1),
                'longitude' => $mainCity->longitude + $this->faker->randomFloat(4, -0.1, 0.1),
                'radius_distance' => $this->faker->numberBetween(5, 15),
                'type' => $this->faker->randomElement(['town', 'municipality', 'district']),
            ];
        });
    }

    /**
     * Configure the factory for a specific country.
     */
    public function inCountry(Country $country): static
    {
        return $this->state(function (array $attributes) use ($country) {
            // Adjust coordinates based on country
            $coordinates = match (strtoupper($country->code)) {
                'LV' => ['lat' => [56.0, 58.0], 'lng' => [21.0, 28.0]], // Latvia
                'EE' => ['lat' => [57.5, 59.7], 'lng' => [21.8, 28.2]], // Estonia
                'PL' => ['lat' => [49.0, 54.8], 'lng' => [14.1, 24.1]], // Poland
                default => ['lat' => [54.0, 56.5], 'lng' => [20.5, 26.8]], // Lithuania default
            };

            return [
                'country_id' => $country->id,
                'latitude' => $this->faker->randomFloat(6, $coordinates['lat'][0], $coordinates['lat'][1]),
                'longitude' => $this->faker->randomFloat(6, $coordinates['lng'][0], $coordinates['lng'][1]),
                'entity_id' => $attributes['slug'].'-'.strtolower($country->code),
                'normalised_name' => $attributes['slug'].'-'.strtolower($country->code),
            ];
        });
    }
}
