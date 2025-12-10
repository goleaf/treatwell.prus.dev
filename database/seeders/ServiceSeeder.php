<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing venues or create some if none exist
        $venues = Venue::all();

        if ($venues->isEmpty()) {
            $venues = Venue::factory(5)->create();
        }

        // Create services for each venue
        $venues->each(function ($venue) {
            // Create 3-8 services per venue
            Service::factory()
                ->count(fake()->numberBetween(3, 8))
                ->for($venue)
                ->create();

            // Create 1-2 featured services per venue
            Service::factory()
                ->count(fake()->numberBetween(1, 2))
                ->for($venue)
                ->featured()
                ->create();

            // Create 1 premium service per venue
            Service::factory()
                ->for($venue)
                ->premium()
                ->create();
        });
    }
}
