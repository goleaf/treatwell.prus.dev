<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Venue;
use Illuminate\Console\Command;

class SaveSingleVenueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'venues:save-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save a single test venue to verify database connectivity';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Testing database connectivity...');

        try {
            // Check database connection
            $connection = \DB::connection()->getPdo();
            $this->info('Database connected: '.\DB::connection()->getDatabaseName());

            // Check if venues table exists
            if (\Schema::hasTable('venues')) {
                $this->info('Venues table exists');
            } else {
                $this->error('Venues table does not exist');

                return 1;
            }

            // Check table structure
            $columns = \Schema::getColumnListing('venues');
            $this->info('Venue table columns: '.implode(', ', $columns));

            // Create test city
            $city = City::firstOrCreate(
                ['slug' => 'test-city'],
                ['name' => 'Test City']
            );

            $this->info('City created/found with ID: '.$city->id);

            // Create test venue
            $venue = Venue::firstOrCreate(
                ['slug' => 'test-venue'],
                [
                    'name' => 'Test Venue',
                    'url' => 'https://example.com/test-venue',
                    'source' => 'treatwell_api',
                    'description' => 'This is a test venue',
                    'external_id' => '12345',
                    'raw_data' => json_encode(['test' => true]),
                ]
            );

            $this->info('Venue created/found with ID: '.$venue->id);

            // Link city to venue
            $venue->cities()->syncWithoutDetaching([$city->id]);
            $this->info('City linked to venue');

            // Verify
            $venues = Venue::all();
            $this->info('Total venues in database: '.$venues->count());
            foreach ($venues as $v) {
                $this->info("- {$v->id}: {$v->name} ({$v->slug})");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }
}
