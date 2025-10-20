<?php

namespace Database\Factories;

use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

class TreatmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Treatment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $categoryNames = [
            'Haircut', 'Massage', 'Manicure', 'Pedicure', 'Facial', 
            'Hair Color', 'Waxing', 'Eyebrow & Eyelash', 'Body Treatment',
            'Nail Extensions', 'Men\'s Grooming'
        ];
        
        $treatmentNamesByCategory = [
            'Haircut' => ['Women\'s Haircut', 'Men\'s Haircut', 'Children\'s Haircut', 'Haircut & Styling'],
            'Massage' => ['Swedish Massage', 'Deep Tissue Massage', 'Hot Stone Massage', 'Sports Massage', 'Aromatherapy Massage'],
            'Manicure' => ['Classic Manicure', 'Gel Manicure', 'Spa Manicure', 'French Manicure'],
            'Pedicure' => ['Classic Pedicure', 'Gel Pedicure', 'Spa Pedicure', 'Medical Pedicure'],
            'Facial' => ['Classic Facial', 'Deep Cleansing Facial', 'Anti-Aging Facial', 'Hydrating Facial'],
            'Hair Color' => ['Full Color', 'Highlights', 'Balayage', 'Ombre', 'Root Touch-Up'],
            'Waxing' => ['Leg Waxing', 'Arm Waxing', 'Bikini Waxing', 'Brazilian Waxing', 'Back Waxing'],
            'Eyebrow & Eyelash' => ['Eyebrow Tinting', 'Eyelash Extensions', 'Lash Lift', 'Brow Lamination'],
            'Body Treatment' => ['Body Scrub', 'Body Wrap', 'Cellulite Treatment', 'Slimming Treatment'],
            'Nail Extensions' => ['Acrylic Nails', 'Gel Extensions', 'Nail Art', 'Nail Repair'],
            'Men\'s Grooming' => ['Beard Trim', 'Shave', 'Men\'s Facial', 'Men\'s Hair Color']
        ];
        
        $category = $this->faker->randomElement($categoryNames);
        $treatmentNames = $treatmentNamesByCategory[$category] ?? ['Standard Treatment'];
        $name = $this->faker->randomElement($treatmentNames);
        
        $minPrice = $this->faker->numberBetween(10, 100);
        $maxPrice = $minPrice + $this->faker->numberBetween(0, 100);
        
        $minDuration = $this->faker->randomElement([15, 30, 45, 60, 90]);
        $maxDuration = $this->faker->boolean(30) ? ($minDuration + 30) : $minDuration;
        
        // Set options as JSON string only if needed for SQLite compatibility
        $options = json_encode([
            [
                'id' => 1,
                'name' => 'Duration',
                'options' => [
                    ['id' => 1, 'name' => $minDuration . ' minutes', 'price' => $minPrice],
                    ['id' => 2, 'name' => $maxDuration . ' minutes', 'price' => $maxPrice]
                ]
            ]
        ]);
        
        return [
            'venue_id' => Venue::factory(),
            'external_id' => $this->faker->unique()->numerify('T######'),
            'name' => $name,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'min_duration' => $minDuration,
            'max_duration' => $maxDuration,
            'category_id' => $this->faker->numberBetween(1, 20),
            'category_name' => $category,
            'options' => $options
        ];
    }
} 