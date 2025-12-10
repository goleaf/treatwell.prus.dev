<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\Image;
use App\Models\Location;
use App\Models\OpeningHour;
use App\Models\Procedure;
use App\Models\Rating;
use App\Models\Service;
use App\Models\Treatment;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating sample data...');

        // Create test users
        $this->createTestUsers();

        // Create procedures first (they're referenced by venues)
        $this->createProcedures();

        // Get existing countries and cities from CountrySeeder
        $countries = Country::with('cities')->get();

        if ($countries->isEmpty()) {
            $this->command->warn('No countries found. Running CountrySeeder first...');
            $this->call(CountrySeeder::class);
            $countries = Country::with('cities')->get();
        }

        // Create additional venues with full relationships
        $this->createVenuesWithRelationships($countries);

        $this->command->info('Sample data created successfully!');
    }

    /**
     * Create test users for authentication and testing.
     */
    private function createTestUsers(): void
    {
        $this->command->info('Creating test users...');

        // Admin user
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Regular test user
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Create additional sample users only if we don't have many users yet
        if (User::count() < 10) {
            User::factory(8)->create();
        }
    }

    /**
     * Create sample procedures across all categories.
     */
    private function createProcedures(): void
    {
        $this->command->info('Creating procedures...');

        // Check if procedures already exist
        if (Procedure::count() > 0) {
            $this->command->info('Procedures already exist, skipping creation...');

            return;
        }

        // Create specific procedures to avoid duplicates
        $procedureData = [
            ['name' => 'Women\'s Haircut', 'category' => 'Hair', 'description' => 'Professional haircut and styling for women'],
            ['name' => 'Men\'s Haircut', 'category' => 'Hair', 'description' => 'Classic and modern haircuts for men'],
            ['name' => 'Hair Coloring', 'category' => 'Hair', 'description' => 'Full color, highlights, and color correction services'],
            ['name' => 'Balayage', 'category' => 'Hair', 'description' => 'Hand-painted highlighting technique for natural-looking color'],
            ['name' => 'Classic Manicure', 'category' => 'Nails', 'description' => 'Traditional nail care with polish application'],
            ['name' => 'Gel Manicure', 'category' => 'Nails', 'description' => 'Long-lasting gel polish manicure'],
            ['name' => 'Classic Pedicure', 'category' => 'Nails', 'description' => 'Complete foot and nail care treatment'],
            ['name' => 'Nail Extensions', 'category' => 'Nails', 'description' => 'Artificial nail extensions for length and strength'],
            ['name' => 'Classic Facial', 'category' => 'Facial', 'description' => 'Basic cleansing and moisturizing facial treatment'],
            ['name' => 'Deep Cleansing Facial', 'category' => 'Facial', 'description' => 'Intensive pore cleansing and extraction treatment'],
            ['name' => 'Anti-Aging Facial', 'category' => 'Facial', 'description' => 'Advanced treatment targeting signs of aging'],
            ['name' => 'Chemical Peel', 'category' => 'Facial', 'description' => 'Exfoliating treatment using chemical solutions'],
            ['name' => 'Body Massage', 'category' => 'Body', 'description' => 'Relaxing full-body massage therapy'],
            ['name' => 'Body Scrub', 'category' => 'Body', 'description' => 'Exfoliating treatment for smooth skin'],
            ['name' => 'Body Wrap', 'category' => 'Body', 'description' => 'Detoxifying and moisturizing body treatment'],
            ['name' => 'Cellulite Treatment', 'category' => 'Body', 'description' => 'Specialized treatment to reduce cellulite appearance'],
            ['name' => 'Swedish Massage', 'category' => 'Massage', 'description' => 'Classic relaxation massage with long strokes'],
            ['name' => 'Deep Tissue Massage', 'category' => 'Massage', 'description' => 'Intensive massage for muscle tension relief'],
            ['name' => 'Sports Massage', 'category' => 'Massage', 'description' => 'Targeted massage for athletes and active individuals'],
            ['name' => 'Prenatal Massage', 'category' => 'Massage', 'description' => 'Gentle massage for expecting mothers'],
            ['name' => 'Eyebrow Shaping', 'category' => 'Beauty', 'description' => 'Professional eyebrow trimming and shaping'],
            ['name' => 'Eyebrow Tinting', 'category' => 'Beauty', 'description' => 'Color enhancement for eyebrows'],
            ['name' => 'Eyelash Extensions', 'category' => 'Beauty', 'description' => 'Individual lash application for volume and length'],
            ['name' => 'Lash Lift', 'category' => 'Beauty', 'description' => 'Natural eyelash curling and lifting treatment'],
        ];

        foreach ($procedureData as $data) {
            Procedure::create([
                'name' => $data['name'],
                'slug' => \Illuminate\Support\Str::slug($data['name']),
                'description' => $data['description'],
                'category' => $data['category'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * Create venues with full relationships and sample data.
     */
    private function createVenuesWithRelationships($countries): void
    {
        $this->command->info('Creating venues with relationships...');

        $procedures = Procedure::all();

        foreach ($countries as $country) {
            foreach ($country->cities as $city) {
                // Get existing venues for this city or create more
                $existingVenues = $city->venues;
                $additionalVenuesNeeded = max(0, 5 - $existingVenues->count());

                // Create additional venues if needed
                if ($additionalVenuesNeeded > 0) {
                    $newVenues = Venue::factory()
                        ->count($additionalVenuesNeeded)
                        ->for($city)
                        ->create();

                    $allVenues = $existingVenues->merge($newVenues);
                } else {
                    $allVenues = $existingVenues;
                }

                // Add relationships and data to all venues
                foreach ($allVenues as $venue) {
                    $this->createVenueRelationships($venue, $city, $procedures);
                }
            }
        }
    }

    /**
     * Create all relationships and data for a venue.
     */
    private function createVenueRelationships(Venue $venue, City $city, $procedures): void
    {
        // Create location details
        if (! $venue->location) {
            Location::factory()
                ->for($venue)
                ->for($city)
                ->create([
                    'address_line1' => $venue->address,
                    'latitude' => $venue->latitude,
                    'longitude' => $venue->longitude,
                ]);
        }

        // Create rating details
        if (! $venue->ratingDetails) {
            Rating::factory()
                ->for($venue)
                ->create([
                    'display_average' => $venue->rating,
                    'count' => $venue->rating_count,
                ]);
        }

        // Create opening hours
        if ($venue->openingHours->isEmpty()) {
            $this->createOpeningHours($venue);
        }

        // Create images
        if ($venue->images->isEmpty()) {
            Image::factory()
                ->count(rand(3, 8))
                ->for($venue)
                ->create();
        }

        // Create treatments
        if ($venue->treatments->isEmpty()) {
            try {
                Treatment::factory()
                    ->count(rand(8, 15))
                    ->for($venue)
                    ->create();
            } catch (\Exception $e) {
                // Skip if there are unique constraint violations
                $this->command->warn("Skipping treatments for venue {$venue->id} due to constraint violations");
            }
        }

        // Create services
        if ($venue->services->isEmpty()) {
            try {
                // Regular services
                Service::factory()
                    ->count(rand(6, 12))
                    ->for($venue)
                    ->create();

                // Featured services
                Service::factory()
                    ->count(rand(2, 4))
                    ->for($venue)
                    ->featured()
                    ->create();

                // Premium services
                Service::factory()
                    ->count(rand(1, 2))
                    ->for($venue)
                    ->premium()
                    ->create();
            } catch (\Exception $e) {
                // Skip if there are unique constraint violations
                $this->command->warn("Skipping services for venue {$venue->id} due to constraint violations");
            }
        }

        // Attach procedures to venue
        if ($venue->procedures->isEmpty() && $procedures->isNotEmpty()) {
            $maxProcedures = min(rand(5, 12), $procedures->count());
            $randomProcedures = $procedures->random($maxProcedures);
            $venue->procedures()->attach($randomProcedures);
        }

        // Attach venue to city (many-to-many relationship)
        if (! $venue->cities->contains($city)) {
            $venue->cities()->attach($city);
        }
    }

    /**
     * Create opening hours for a venue.
     */
    private function createOpeningHours(Venue $venue): void
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($days as $day) {
            // Most venues are closed on Sunday, some on Monday
            $isClosed = ($day === 'Sunday' && rand(1, 100) <= 70) ||
                       ($day === 'Monday' && rand(1, 100) <= 30);

            if ($isClosed) {
                OpeningHour::factory()
                    ->for($venue)
                    ->closed()
                    ->create(['day_of_week' => $day]);
            } else {
                // Weekend hours might be different
                $isWeekend = in_array($day, ['Saturday', 'Sunday']);

                OpeningHour::factory()
                    ->for($venue)
                    ->when($isWeekend, fn ($factory) => $factory->extendedHours())
                    ->create(['day_of_week' => $day]);
            }
        }
    }
}
