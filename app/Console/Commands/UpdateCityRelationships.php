<?php

namespace App\Console\Commands;

use App\Models\City;
use Illuminate\Console\Command;

class UpdateCityRelationships extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:city-relationships';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing cities to identify main cities and subregions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to update city relationships...');

        // Step 1: Mark all standard cities with no comma in name as main cities
        $this->info('Identifying main cities...');
        $mainCitiesCount = 0;

        City::whereRaw("name NOT LIKE '%,%'")->chunk(100, function ($cities) use (&$mainCitiesCount) {
            foreach ($cities as $city) {
                $city->update(['is_main_city' => true]);
                $mainCitiesCount++;
            }
        });

        $this->info("Marked {$mainCitiesCount} cities as main cities.");

        // Step 2: Process cities with commas as potential subregions
        $this->info('Processing subregions...');
        $subregionsCount = 0;
        $associatedCount = 0;

        City::whereRaw("name LIKE '%,%'")->chunk(100, function ($cities) use (&$subregionsCount, &$associatedCount) {
            foreach ($cities as $city) {
                // Update is_main_city flag
                $city->update(['is_main_city' => false]);
                $subregionsCount++;

                // Extract subregion and main city names
                [$subregionName, $mainCityName] = array_map('trim', explode(',', $city->name, 2));

                // Find the main city
                $mainCity = City::where('name', $mainCityName)
                    ->where('is_main_city', true)
                    ->first();

                if ($mainCity) {
                    // Update the subregion with main_city_id and subregion name
                    $city->update([
                        'main_city_id' => $mainCity->id,
                        'subregion' => $subregionName,
                    ]);

                    $associatedCount++;
                } else {
                    // Just set the subregion name
                    $city->update(['subregion' => $subregionName]);
                    $this->warn("Could not find main city for subregion: {$city->name}");
                }
            }
        });

        $this->info("Processed {$subregionsCount} subregions.");
        $this->info("Associated {$associatedCount} subregions with main cities.");

        // Final stats
        $this->newLine();
        $this->info('Final stats:');
        $this->info('  Main cities: '.City::where('is_main_city', true)->count());
        $this->info('  Subregions: '.City::where('is_main_city', false)->count());
        $this->info('  Subregions with main city assigned: '.City::whereNotNull('main_city_id')->count());

        $this->info('City relationships update completed successfully.');

        return 0;
    }
}
