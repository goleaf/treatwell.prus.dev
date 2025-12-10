<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProcedureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create basic procedures for each category
        $categories = ['Hair', 'Nails', 'Facial', 'Body', 'Massage', 'Beauty'];

        foreach ($categories as $category) {
            \App\Models\Procedure::factory()
                ->count(rand(6, 10))
                ->category($category)
                ->create();
        }

        // Create some popular procedures
        \App\Models\Procedure::factory()
            ->count(8)
            ->popular()
            ->create();
    }
}
