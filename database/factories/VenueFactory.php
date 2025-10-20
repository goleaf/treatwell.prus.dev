<?php

namespace Database\Factories;

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
     *
     * @return array
     */
    public function definition()
    {
        return [
            'external_id' => $this->faker->unique()->numerify('######'),
            'name' => $this->faker->company() . ' ' . $this->faker->randomElement(['Salon', 'Spa', 'Beauty', 'Hair']),
            'description' => $this->faker->paragraph(),
            'type_id' => $this->faker->numberBetween(1, 10),
            'type_name' => $this->faker->randomElement(['Hair Salon', 'Spa', 'Beauty Salon', 'Nail Salon', 'Massage']),
            'normalised_name' => function (array $attributes) {
                return strtolower(str_replace(' ', '-', $attributes['type_name']));
            },
            'desktop_uri' => '/salon/' . $this->faker->slug(),
            'mobile_uri' => '/salon/' . $this->faker->slug(),
            'app_uri' => '/salon/' . $this->faker->slug(),
            'is_new_venue' => $this->faker->boolean(20),
            'raw_data' => json_encode([
                'id' => $this->faker->numerify('######'),
                'name' => $this->faker->company(),
                'description' => $this->faker->paragraph(),
            ])
        ];
    }
} 