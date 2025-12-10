<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncCityRelationships extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'city:sync-relationships {--city=* : Specific city slugs to sync} {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Sync all city relationships with related entities (services, images, ratings, opening hours, treatments)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $specificCities = $this->option('city');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('🏙️  Starting city relationships sync...');

        // Get cities to process
        $citiesQuery = City::query();
        if (!empty($specificCities)) {
            $citiesQuery->whereIn('slug', $specificCities);
        }
        
        $cities = $citiesQuery->with(['venues'])->get();

        if ($cities->isEmpty()) {
            $this->warn('No cities found to process.');
            return self::SUCCESS;
        }

        $this->info("Processing {$cities->count()} cities...");

        $totalStats = [
            'cities_processed' => 0,
            'venues_synced' => 0,
            'services_updated' => 0,
            'images_updated' => 0,
            'ratings_updated' => 0,
            'opening_hours_updated' => 0,
            'locations_updated' => 0,
            'treatments_synced' => 0,
        ];

        foreach ($cities as $city) {
            $this->info("📍 Processing city: {$city->name} ({$city->slug})");
            
            $cityStats = $this->syncCityRelationships($city, $isDryRun);
            
            // Add to totals
            foreach ($cityStats as $key => $value) {
                $totalStats[$key] = ($totalStats[$key] ?? 0) + $value;
            }
            
            $totalStats['cities_processed']++;
            
            $this->line("   ✅ Venues: {$cityStats['venues_synced']}, Services: {$cityStats['services_updated']}, Images: {$cityStats['images_updated']}, Ratings: {$cityStats['ratings_updated']}, Hours: {$cityStats['opening_hours_updated']}, Locations: {$cityStats['locations_updated']}, Treatments: {$cityStats['treatments_synced']}");
        }

        $this->newLine();
        $this->info('📊 Final Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Cities Processed', $totalStats['cities_processed']],
                ['Venues Synced', $totalStats['venues_synced']],
                ['Services Updated', $totalStats['services_updated']],
                ['Images Updated', $totalStats['images_updated']],
                ['Ratings Updated', $totalStats['ratings_updated']],
                ['Opening Hours Updated', $totalStats['opening_hours_updated']],
                ['Locations Updated', $totalStats['locations_updated']],
                ['Treatments Synced', $totalStats['treatments_synced']],
            ]
        );

        if ($isDryRun) {
            $this->info('🔍 DRY RUN COMPLETED - No actual changes were made');
        } else {
            $this->info('✅ City relationships sync completed successfully!');
        }

        return self::SUCCESS;
    }

    /**
     * Sync relationships for a specific city.
     */
    private function syncCityRelationships(City $city, bool $isDryRun = false): array
    {
        $stats = [
            'venues_synced' => 0,
            'services_updated' => 0,
            'images_updated' => 0,
            'ratings_updated' => 0,
            'opening_hours_updated' => 0,
            'locations_updated' => 0,
            'treatments_synced' => 0,
        ];

        if (!$isDryRun) {
            DB::beginTransaction();
        }

        try {
            // Process each venue in the city
            foreach ($city->venues as $venue) {
                $venueStats = $this->syncVenueRelationships($venue, $isDryRun);
                
                foreach ($venueStats as $key => $value) {
                    $stats[$key] += $value;
                }
                
                $stats['venues_synced']++;
            }

            // Sync city-level treatment relationships
            if (!$isDryRun) {
                $treatmentIds = $city->venues()->with('treatments')->get()
                    ->flatMap(function ($venue) {
                        return $venue->treatments->pluck('id');
                    })->unique();

                $existingTreatmentIds = $city->treatments()->pluck('treatment_id');
                $newTreatmentIds = $treatmentIds->diff($existingTreatmentIds);
                
                if ($newTreatmentIds->isNotEmpty()) {
                    $city->treatments()->syncWithoutDetaching($newTreatmentIds);
                    $stats['treatments_synced'] += $newTreatmentIds->count();
                }
            } else {
                // For dry run, just count what would be synced
                $treatmentIds = $city->venues()->with('treatments')->get()
                    ->flatMap(function ($venue) {
                        return $venue->treatments->pluck('id');
                    })->unique();
                
                $existingTreatmentIds = $city->treatments()->pluck('treatment_id');
                $newTreatmentIds = $treatmentIds->diff($existingTreatmentIds);
                $stats['treatments_synced'] += $newTreatmentIds->count();
            }

            if (!$isDryRun) {
                DB::commit();
            }

        } catch (\Exception $e) {
            if (!$isDryRun) {
                DB::rollBack();
            }
            
            $this->error("Error syncing city {$city->slug}: " . $e->getMessage());
            throw $e;
        }

        return $stats;
    }

    /**
     * Sync relationships for a specific venue.
     */
    private function syncVenueRelationships(Venue $venue, bool $isDryRun = false): array
    {
        $stats = [
            'services_updated' => 0,
            'images_updated' => 0,
            'ratings_updated' => 0,
            'opening_hours_updated' => 0,
            'locations_updated' => 0,
        ];

        if (!$venue->city_id) {
            return $stats;
        }

        if (!$isDryRun) {
            // Update services
            $servicesUpdated = $venue->services()->whereNull('city_id')->update(['city_id' => $venue->city_id]);
            $stats['services_updated'] += $servicesUpdated;

            // Update images
            $imagesUpdated = $venue->images()->whereNull('city_id')->update(['city_id' => $venue->city_id]);
            $stats['images_updated'] += $imagesUpdated;

            // Update ratings
            if ($venue->ratingDetails && !$venue->ratingDetails->city_id) {
                $venue->ratingDetails->update(['city_id' => $venue->city_id]);
                $stats['ratings_updated']++;
            }

            // Update opening hours
            $hoursUpdated = $venue->openingHours()->whereNull('city_id')->update(['city_id' => $venue->city_id]);
            $stats['opening_hours_updated'] += $hoursUpdated;

            // Update locations
            $locationsUpdated = $venue->locations()->whereNull('city_id')->update(['city_id' => $venue->city_id]);
            $stats['locations_updated'] += $locationsUpdated;
        } else {
            // For dry run, just count what would be updated
            $stats['services_updated'] += $venue->services()->whereNull('city_id')->count();
            $stats['images_updated'] += $venue->images()->whereNull('city_id')->count();
            
            if ($venue->ratingDetails && !$venue->ratingDetails->city_id) {
                $stats['ratings_updated']++;
            }
            
            $stats['opening_hours_updated'] += $venue->openingHours()->whereNull('city_id')->count();
            $stats['locations_updated'] += $venue->locations()->whereNull('city_id')->count();
        }

        return $stats;
    }
}