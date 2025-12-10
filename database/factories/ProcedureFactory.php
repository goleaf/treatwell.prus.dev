<?php

namespace Database\Factories;

use App\Models\Procedure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Procedure>
 */
class ProcedureFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Procedure::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $procedureData = [
            'Hair' => [
                'procedures' => [
                    'Women\'s Haircut' => 'Professional haircut and styling for women',
                    'Men\'s Haircut' => 'Classic and modern haircuts for men',
                    'Hair Coloring' => 'Full color, highlights, and color correction services',
                    'Balayage' => 'Hand-painted highlighting technique for natural-looking color',
                    'Ombre' => 'Gradient color technique from dark to light',
                    'Hair Extensions' => 'Length and volume enhancement with quality extensions',
                    'Keratin Treatment' => 'Smoothing treatment for frizzy and damaged hair',
                    'Perm' => 'Chemical treatment to create curls or waves',
                    'Hair Straightening' => 'Chemical or thermal hair straightening services',
                    'Scalp Treatment' => 'Therapeutic treatments for scalp health',
                ],
            ],
            'Nails' => [
                'procedures' => [
                    'Classic Manicure' => 'Traditional nail care with polish application',
                    'Gel Manicure' => 'Long-lasting gel polish manicure',
                    'Classic Pedicure' => 'Complete foot and nail care treatment',
                    'Gel Pedicure' => 'Pedicure with durable gel polish finish',
                    'Nail Extensions' => 'Artificial nail extensions for length and strength',
                    'Nail Art' => 'Creative nail designs and decorative techniques',
                    'French Manicure' => 'Classic white-tip nail styling',
                    'Russian Manicure' => 'Precise cuticle work using electric files',
                    'Nail Repair' => 'Restoration of damaged or broken nails',
                    'Paraffin Treatment' => 'Moisturizing paraffin wax treatment for hands and feet',
                ],
            ],
            'Facial' => [
                'procedures' => [
                    'Classic Facial' => 'Basic cleansing and moisturizing facial treatment',
                    'Deep Cleansing Facial' => 'Intensive pore cleansing and extraction treatment',
                    'Anti-Aging Facial' => 'Advanced treatment targeting signs of aging',
                    'Hydrating Facial' => 'Moisture-boosting treatment for dry skin',
                    'Acne Treatment' => 'Specialized treatment for acne-prone skin',
                    'Chemical Peel' => 'Exfoliating treatment using chemical solutions',
                    'Microdermabrasion' => 'Mechanical exfoliation for skin renewal',
                    'LED Light Therapy' => 'Light-based treatment for various skin concerns',
                    'Oxygen Facial' => 'Oxygenating treatment for radiant skin',
                    'Vitamin C Facial' => 'Brightening treatment with vitamin C infusion',
                ],
            ],
            'Body' => [
                'procedures' => [
                    'Body Massage' => 'Relaxing full-body massage therapy',
                    'Body Scrub' => 'Exfoliating treatment for smooth skin',
                    'Body Wrap' => 'Detoxifying and moisturizing body treatment',
                    'Cellulite Treatment' => 'Specialized treatment to reduce cellulite appearance',
                    'Body Contouring' => 'Non-invasive body shaping treatments',
                    'Spray Tan' => 'Professional sunless tanning application',
                    'Body Waxing' => 'Hair removal services for various body areas',
                    'Lymphatic Drainage' => 'Gentle massage to stimulate lymphatic system',
                    'Hot Stone Therapy' => 'Therapeutic massage using heated stones',
                    'Aromatherapy' => 'Essential oil-based relaxation treatments',
                ],
            ],
            'Massage' => [
                'procedures' => [
                    'Swedish Massage' => 'Classic relaxation massage with long strokes',
                    'Deep Tissue Massage' => 'Intensive massage for muscle tension relief',
                    'Sports Massage' => 'Targeted massage for athletes and active individuals',
                    'Prenatal Massage' => 'Gentle massage for expecting mothers',
                    'Couples Massage' => 'Simultaneous massage experience for two people',
                    'Reflexology' => 'Pressure point massage focusing on feet',
                    'Thai Massage' => 'Traditional stretching and pressure point massage',
                    'Shiatsu' => 'Japanese finger pressure massage technique',
                    'Trigger Point Therapy' => 'Focused treatment for muscle knots and tension',
                    'Chair Massage' => 'Quick relaxation massage in seated position',
                ],
            ],
            'Beauty' => [
                'procedures' => [
                    'Eyebrow Shaping' => 'Professional eyebrow trimming and shaping',
                    'Eyebrow Tinting' => 'Color enhancement for eyebrows',
                    'Eyelash Extensions' => 'Individual lash application for volume and length',
                    'Lash Lift' => 'Natural eyelash curling and lifting treatment',
                    'Lash Tinting' => 'Color enhancement for natural eyelashes',
                    'Microblading' => 'Semi-permanent eyebrow tattooing technique',
                    'Permanent Makeup' => 'Cosmetic tattooing for lips, eyes, and brows',
                    'Makeup Application' => 'Professional makeup for special occasions',
                    'Makeup Lessons' => 'Personal makeup instruction and techniques',
                    'Bridal Makeup' => 'Wedding day makeup with trial session',
                ],
            ],
        ];

        $category = $this->faker->randomElement(array_keys($procedureData));
        $categoryData = $procedureData[$category];
        $procedureName = $this->faker->randomElement(array_keys($categoryData['procedures']));
        $description = $categoryData['procedures'][$procedureName];

        $slug = \Illuminate\Support\Str::slug($procedureName);

        return [
            'name' => $procedureName,
            'slug' => $slug,
            'description' => $description,
            'category' => $category,
            'is_active' => $this->faker->boolean(95),
        ];
    }

    /**
     * Configure the factory for a specific category.
     */
    public function category(string $category): static
    {
        return $this->state(function (array $attributes) use ($category) {
            return [
                'category' => $category,
            ];
        });
    }

    /**
     * Configure the factory to create an inactive procedure.
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * Configure the factory for hair procedures.
     */
    public function hair(): static
    {
        return $this->category('Hair');
    }

    /**
     * Configure the factory for nail procedures.
     */
    public function nails(): static
    {
        return $this->category('Nails');
    }

    /**
     * Configure the factory for facial procedures.
     */
    public function facial(): static
    {
        return $this->category('Facial');
    }

    /**
     * Configure the factory for body procedures.
     */
    public function body(): static
    {
        return $this->category('Body');
    }

    /**
     * Configure the factory for massage procedures.
     */
    public function massage(): static
    {
        return $this->category('Massage');
    }

    /**
     * Configure the factory for beauty procedures.
     */
    public function beauty(): static
    {
        return $this->category('Beauty');
    }

    /**
     * Configure the factory for popular procedures.
     */
    public function popular(): static
    {
        $popularProcedures = [
            'Women\'s Haircut',
            'Men\'s Haircut',
            'Gel Manicure',
            'Classic Facial',
            'Swedish Massage',
            'Eyebrow Shaping',
        ];

        $procedureName = $this->faker->randomElement($popularProcedures);

        return $this->state(function (array $attributes) use ($procedureName) {
            return [
                'name' => $procedureName,
                'slug' => \Illuminate\Support\Str::slug($procedureName),
                'is_active' => true,
            ];
        });
    }
}
