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
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->country();
        
        return [
            'code' => $this->faker->unique()->countryCode(),
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'normalised_name' => strtolower($name),
            'active' => true,
        ];
    }
}
