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
     */
    public function definition(): array
    {
        // Lithuanian street names and patterns
        $streetTypes = ['g.', 'al.', 'skg.', 'pl.', 'tak.'];
        $streetNames = [
            'Gedimino', 'Vilniaus', 'Laisvės', 'Savanorių', 'Kauno', 'Klaipėdos',
            'Vytauto', 'Mindaugo', 'Jogailos', 'Algirdo', 'Kęstučio', 'Birutės',
            'Žvėryno', 'Antakalnio', 'Šeškinės', 'Lazdynų', 'Pašilaičių',
        ];

        $streetType = $this->faker->randomElement($streetTypes);
        $streetName = $this->faker->randomElement($streetNames);
        $streetNumber = $this->faker->numberBetween(1, 150);
        $addressLine1 = $streetName.' '.$streetType.' '.$streetNumber;

        // Optional apartment/unit number
        $addressLine2 = $this->faker->boolean(30) ?
            'buto '.$this->faker->numberBetween(1, 50) : null;

        // Lithuanian postal codes (5 digits, starting with specific patterns)
        $postalCodePrefixes = ['01', '02', '03', '04', '05', '08', '09', '10', '11', '44', '45', '46', '47', '48', '49', '50', '51', '52', '53', '54', '55', '56', '57', '58', '59', '60', '61', '62', '63', '64', '65', '66', '67', '68', '69', '70', '71', '72', '73', '74', '75', '76', '77', '78', '79', '80', '81', '82', '83', '84', '85', '86', '87', '88', '89', '90', '91', '92', '93', '94', '95', '96', '97', '98', '99'];
        $postalCode = $this->faker->randomElement($postalCodePrefixes).$this->faker->numerify('###');

        return [
            'venue_id' => Venue::factory(),
            'city_id' => City::factory(),
            'name' => $this->faker->boolean(20) ? $this->faker->randomElement(['Main Location', 'Branch Office', 'Studio', 'Salon Floor']) : null,
            'description' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
            'postal_code' => $postalCode,
            'address_line1' => $addressLine1,
            'address_line2' => $addressLine2,
            'latitude' => $this->faker->randomFloat(6, 54.0, 56.5),
            'longitude' => $this->faker->randomFloat(6, 20.5, 26.8),
            'map_zoom' => $this->faker->numberBetween(14, 18),
            'capacity' => $this->faker->boolean(40) ? $this->faker->numberBetween(5, 50) : null,
            'is_active' => $this->faker->boolean(95),
        ];
    }

    /**
     * Configure the factory for a specific venue and city.
     */
    public function forVenueInCity(Venue $venue, City $city): static
    {
        return $this->state(function (array $attributes) use ($venue, $city) {
            return [
                'venue_id' => $venue->id,
                'city_id' => $city->id,
                'latitude' => $city->latitude + $this->faker->randomFloat(4, -0.02, 0.02),
                'longitude' => $city->longitude + $this->faker->randomFloat(4, -0.02, 0.02),
            ];
        });
    }

    /**
     * Configure the factory to create a main location.
     */
    public function main(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Main Location',
                'description' => 'Primary business location',
                'capacity' => $this->faker->numberBetween(10, 30),
                'is_active' => true,
            ];
        });
    }

    /**
     * Configure the factory to create a branch location.
     */
    public function branch(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Branch Office',
                'description' => 'Secondary business location',
                'capacity' => $this->faker->numberBetween(5, 15),
            ];
        });
    }

    /**
     * Configure the factory to create an inactive location.
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
                'description' => 'Temporarily closed location',
            ];
        });
    }

    /**
     * Configure the factory with specific coordinates.
     */
    public function withCoordinates(float $latitude, float $longitude): static
    {
        return $this->state(function (array $attributes) use ($latitude, $longitude) {
            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        });
    }

    /**
     * Configure the factory to create a location with full address details.
     */
    public function withFullAddress(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Main Location',
                'description' => 'Primary business location with full facilities',
                'address_line2' => 'buto '.$this->faker->numberBetween(1, 50),
                'capacity' => $this->faker->numberBetween(10, 50),
            ];
        });
    }

    /**
     * Configure the factory to create a location with high capacity.
     */
    public function highCapacity(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'capacity' => $this->faker->numberBetween(30, 100),
                'name' => 'Large Facility',
                'description' => 'Spacious location with high capacity',
            ];
        });
    }
}
