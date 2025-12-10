<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Procedure>
 */
class ProcedureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $procedures = [
            'Haircut', 'Hair Color', 'Highlights', 'Balayage', 'Perm', 'Hair Treatment',
            'Manicure', 'Pedicure', 'Gel Nails', 'Nail Art', 'Nail Extensions',
            'Facial', 'Deep Cleansing', 'Anti-Aging Treatment', 'Hydrating Facial',
            'Massage', 'Swedish Massage', 'Deep Tissue', 'Hot Stone Massage',
            'Waxing', 'Eyebrow Shaping', 'Eyelash Extensions', 'Lash Lift',
            'Body Treatment', 'Body Scrub', 'Cellulite Treatment',
        ];

        $name = $this->faker->randomElement($procedures);

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'description' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(['Hair', 'Nails', 'Facial', 'Body', 'Massage']),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
