<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class CountryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Country::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Realistic countries with proper codes
        $countries = [
            'Lithuania' => 'LT',
            'Latvia' => 'LV',
            'Estonia' => 'EE',
            'Poland' => 'PL',
            'Germany' => 'DE',
            'United Kingdom' => 'GB',
            'France' => 'FR',
            'Spain' => 'ES',
            'Italy' => 'IT',
            'Netherlands' => 'NL',
            'Belgium' => 'BE',
            'Sweden' => 'SE',
            'Norway' => 'NO',
            'Denmark' => 'DK',
            'Finland' => 'FI',
            'Czech Republic' => 'CZ',
            'Austria' => 'AT',
            'Switzerland' => 'CH',
            'Ireland' => 'IE',
            'Portugal' => 'PT',
        ];

        // Use realistic country or generate fake one
        if ($this->faker->boolean(80)) {
            $name = $this->faker->randomElement(array_keys($countries));
            $code = $countries[$name];
        } else {
            $name = $this->faker->country();
            $code = $this->faker->unique()->countryCode();
        }

        $slug = \Illuminate\Support\Str::slug($name);
        $normalisedName = strtolower(str_replace([' ', '&', '\''], ['-', 'and', ''], $name));

        return [
            'code' => $code,
            'name' => $name,
            'slug' => $slug,
            'normalised_name' => $normalisedName,
            'active' => $this->faker->boolean(95),
        ];
    }

    /**
     * Configure the factory to create Lithuania.
     */
    public function lithuania(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'code' => 'LT',
                'name' => 'Lithuania',
                'slug' => 'lithuania',
                'normalised_name' => 'lithuania',
                'active' => true,
            ];
        });
    }

    /**
     * Configure the factory to create an active country.
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'active' => true,
            ];
        });
    }

    /**
     * Configure the factory to create an inactive country.
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'active' => false,
            ];
        });
    }

    /**
     * Configure the factory for Baltic countries.
     */
    public function baltic(): static
    {
        $balticCountries = [
            'Lithuania' => 'LT',
            'Latvia' => 'LV',
            'Estonia' => 'EE',
        ];

        $name = $this->faker->randomElement(array_keys($balticCountries));
        $code = $balticCountries[$name];

        return $this->state(function (array $attributes) use ($name, $code) {
            return [
                'code' => $code,
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name),
                'normalised_name' => strtolower($name),
                'active' => true,
            ];
        });
    }

    /**
     * Configure the factory for European Union countries.
     */
    public function eu(): static
    {
        $euCountries = [
            'Germany' => 'DE',
            'France' => 'FR',
            'Spain' => 'ES',
            'Italy' => 'IT',
            'Netherlands' => 'NL',
            'Belgium' => 'BE',
            'Poland' => 'PL',
            'Czech Republic' => 'CZ',
            'Austria' => 'AT',
            'Sweden' => 'SE',
            'Denmark' => 'DK',
            'Finland' => 'FI',
            'Ireland' => 'IE',
            'Portugal' => 'PT',
            'Lithuania' => 'LT',
            'Latvia' => 'LV',
            'Estonia' => 'EE',
        ];

        $name = $this->faker->randomElement(array_keys($euCountries));
        $code = $euCountries[$name];

        return $this->state(function (array $attributes) use ($name, $code) {
            return [
                'code' => $code,
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name),
                'normalised_name' => strtolower(str_replace([' ', '&', '\''], ['-', 'and', ''], $name)),
                'active' => true,
            ];
        });
    }
}
