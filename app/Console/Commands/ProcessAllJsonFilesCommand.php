<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Venue;
use App\Models\City;
use App\Models\Location;
use App\Models\Procedure;
use App\Models\Treatment;
use App\Models\Rating;
use App\Models\OpeningHour;
use App\Models\Image;
use App\Models\Country;

class ProcessAllJsonFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'json:process 
                            {--batch-size=25 : Number of files to process in each batch}
                            {--max-files=0 : Maximum number of files to process (0 = all)}
                            {--clear-existing : Clear existing data before importing}
                            {--force : Force the operation without confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all JSON files in storage/app/xml directory, analyze in memory and save to database';

    /**
     * Statistics tracking
     */
    private array $stats = [
        'files_total' => 0,
        'files_processed' => 0,
        'files_skipped' => 0,
        'files_failed' => 0,
        'venues_found' => 0,
        'venues_saved' => 0,
        'treatments_saved' => 0,
        'cities_saved' => 0,
        'locations_saved' => 0,
        'procedures_saved' => 0,
        'images_saved' => 0,
        'opening_hours_saved' => 0,
        'ratings_saved' => 0,
        'relationships_created' => 0,
    ];

    // Track unique entities to avoid duplicates
    private array $uniqueVenues = [];
    private array $uniqueCities = [];
    private array $uniqueTreatments = [];
    private array $uniqueLocations = [];
    private array $uniqueProcedures = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting JSON file processing...');
        $startTime = microtime(true);

        // Get command options
        $batchSize = (int)$this->option('batch-size');
        $maxFiles = (int)$this->option('max-files');
        $clearExisting = $this->option('clear-existing');
        $force = $this->option('force');

        // Validate database schema
        if (!$this->validateSchema()) {
            $this->error("Schema validation failed. Please fix the issues and try again.");
            return 1;
        }

        // Clear existing data if requested
        if ($clearExisting) {
            if ($force || $this->confirm('Are you sure you want to clear all existing data?', false)) {
                $this->clearExistingData();
            } else {
                $this->info('Skipping clearing of existing data.');
            }
        }

        // Get all JSON files from storage/app/xml directory
        $jsonFiles = File::glob(storage_path('app/xml/api_response_*.json'));
        $totalFiles = count($jsonFiles);
        
        if ($totalFiles === 0) {
            $this->error("No JSON files found in storage/app/xml directory.");
            return 1;
        }

        $this->stats['files_total'] = $totalFiles;
        $this->info("Found {$totalFiles} JSON files to process");

        // Apply max files limit if specified
        if ($maxFiles > 0 && $totalFiles > $maxFiles) {
            $jsonFiles = array_slice($jsonFiles, 0, $maxFiles);
            $this->info("Limited to processing {$maxFiles} files");
        }

        // Process files in batches to manage memory
        $totalBatches = ceil(count($jsonFiles) / $batchSize);
        
        for ($batchNum = 0; $batchNum < $totalBatches; $batchNum++) {
            $batchStart = $batchNum * $batchSize;
            $batchFiles = array_slice($jsonFiles, $batchStart, $batchSize);
            
            $this->info("Processing batch " . ($batchNum + 1) . "/{$totalBatches} (" . count($batchFiles) . " files)...");
            
            try {
                // Start a database transaction for each batch
                DB::beginTransaction();
                
                // Process each file in the batch
                $progress = $this->output->createProgressBar(count($batchFiles));
                $progress->start();
                
                foreach ($batchFiles as $filePath) {
                    $this->processJsonFile($filePath);
                    $progress->advance();
                }
                
                $progress->finish();
                $this->line('');
                
                // Commit the transaction for this batch
                DB::commit();
                
                $this->info("Batch " . ($batchNum + 1) . " completed successfully.");
                $this->info("Memory usage: " . $this->formatBytes(memory_get_usage(true)));
                
                // Clean memory between batches
                $this->uniqueVenues = [];
                $this->uniqueCities = [];
                $this->uniqueTreatments = [];
                $this->uniqueLocations = [];
                $this->uniqueProcedures = [];
                
                // Force garbage collection
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error in batch " . ($batchNum + 1) . ": " . $e->getMessage());
                Log::error("JSON processing error: " . $e->getMessage(), [
                    'batch' => $batchNum + 1,
                    'exception' => $e
                ]);
                $this->stats['files_failed'] += count($batchFiles);
            }
        }

        // Create relationships
        $this->info("Creating relationships between entities...");
        $this->createRelationships();

        $executionTime = round(microtime(true) - $startTime, 2);
        
        // Display final statistics
        $this->displayStatistics($executionTime);
        
        return 0;
    }

    /**
     * Process a single JSON file
     * 
     * @param string $filePath Path to the JSON file
     * @return void
     */
    private function processJsonFile(string $filePath)
    {
        try {
            // Read JSON file content
            $jsonContent = File::get($filePath);
            
            // Remove the weird prefix if it exists
            if (strpos($jsonContent, ')]}\'') === 0) {
                $jsonContent = substr($jsonContent, 5);
            }
            
            $data = json_decode($jsonContent, true);
            
            if (!$data || json_last_error() !== JSON_ERROR_NONE) {
                $this->stats['files_skipped']++;
                Log::warning("Failed to parse JSON file: {$filePath}. Error: " . json_last_error_msg());
                return;
            }
            
            // Process the JSON data
            if (isset($data['results']) && is_array($data['results'])) {
                foreach ($data['results'] as $result) {
                    if ($result['type'] === 'venue' && isset($result['data'])) {
                        $this->processVenue($result['data']);
                    }
                }
            } elseif (isset($data['venues'])) {
                // Process venues
                foreach ($data['venues'] as $venueData) {
                    $this->processVenue($venueData);
                }
            } elseif (isset($data['businesses'])) {
                // Alternative format
                foreach ($data['businesses'] as $venueData) {
                    $this->processVenue($venueData);
                }
            }
            
            $this->stats['files_processed']++;
        } catch (\Exception $e) {
            $this->stats['files_failed']++;
            Log::error("Error processing file {$filePath}: " . $e->getMessage(), ['exception' => $e]);
        }
    }
    
    /**
     * Process venue data and save to database
     * 
     * @param array $venueData Venue data from JSON
     * @return void
     */
    private function processVenue(array $venueData)
    {
        // Extract venue ID or generate one if not present
        $venueId = $venueData['id'] ?? ($venueData['venueId'] ?? ($venueData['businessId'] ?? Str::uuid()));
        
        // Skip if we've already processed this venue
        if (isset($this->uniqueVenues[$venueId])) {
            return;
        }
        
        $this->stats['venues_found']++;
        $this->uniqueVenues[$venueId] = true;
        
        // Extract venue data
        $venueName = $venueData['name'] ?? ($venueData['venueName'] ?? ($venueData['businessName'] ?? 'Unknown'));
        $venueDescription = $venueData['description'] ?? ($venueData['venueDescription'] ?? '');
        $venueSlug = $venueData['slug'] ?? ($venueData['venueSlug'] ?? Str::slug($venueName));
        $venueUrl = $venueData['url'] ?? ($venueData['venueUrl'] ?? ($venueData['businessUrl'] ?? ''));
        
        // Save venue
        $venue = Venue::firstOrNew(['id' => $venueId]);
        $venue->name = $venueName;
        $venue->description = $venueDescription;
        $venue->slug = $venueSlug;
        $venue->url = $venueUrl;
        
        // Add other venue properties if they exist
        if (isset($venueData['location']['address']['addressLines'])) {
            $venue->address = implode(', ', $venueData['location']['address']['addressLines']);
        } else {
            $venue->address = $venueData['address'] ?? '';
        }
        
        $venue->phone = $venueData['phone'] ?? ($venueData['phoneNumber'] ?? '');
        $venue->email = $venueData['email'] ?? '';
        $venue->website = $venueData['website'] ?? '';
        $venue->source = 'json_import';
        
        if (isset($venueData['location']['point'])) {
            $venue->latitude = $venueData['location']['point']['lat'] ?? 0;
            $venue->longitude = $venueData['location']['point']['lon'] ?? 0;
        } elseif (isset($venueData['location'])) {
            $venue->latitude = $venueData['location']['latitude'] ?? ($venueData['latitude'] ?? 0);
            $venue->longitude = $venueData['location']['longitude'] ?? ($venueData['longitude'] ?? 0);
        }
        
        $venue->save();
        $this->stats['venues_saved']++;
        
        // Process location
        if (isset($venueData['location'])) {
            $this->processLocation($venueData['location'], $venue);
        }
        
        // Process city - extract from location tree if available
        if (isset($venueData['location']['tree'])) {
            $this->processCityFromTree($venueData['location']['tree'], $venue);
        } elseif (isset($venueData['city']) || isset($venueData['location']['city'])) {
            $cityData = $venueData['city'] ?? $venueData['location']['city'];
            $this->processCity($cityData, $venue);
        }
        
        // Process treatments - may be in different formats
        if (isset($venueData['treatments']) && is_array($venueData['treatments'])) {
            foreach ($venueData['treatments'] as $treatmentData) {
                $this->processTreatment($treatmentData, $venue);
            }
        } elseif (isset($venueData['services']) && is_array($venueData['services'])) {
            foreach ($venueData['services'] as $treatmentData) {
                $this->processTreatment($treatmentData, $venue);
            }
        }
        
        // Process images - handle both arrays and single objects
        if (isset($venueData['images']) && is_array($venueData['images'])) {
            foreach ($venueData['images'] as $imageData) {
                $this->processImage($imageData, $venue);
            }
        } elseif (isset($venueData['primaryImage'])) {
            $this->processImage($venueData['primaryImage'], $venue);
        }
        
        // Process opening hours
        if (isset($venueData['openingHours']) && is_array($venueData['openingHours'])) {
            foreach ($venueData['openingHours'] as $hourData) {
                $this->processOpeningHour($hourData, $venue);
            }
        }
        
        // Process ratings
        if (isset($venueData['rating'])) {
            $this->processRating($venueData['rating'], $venue);
        } elseif (isset($venueData['ratings'])) {
            $this->processRating($venueData['ratings'], $venue);
        }
    }
    
    /**
     * Process location data
     * 
     * @param array $locationData Location data
     * @param Venue $venue Related venue
     * @return void
     */
    private function processLocation(array $locationData, Venue $venue)
    {
        $locationId = $locationData['id'] ?? Str::uuid();
        
        if (isset($this->uniqueLocations[$locationId])) {
            return;
        }
        
        $this->uniqueLocations[$locationId] = true;
        
        $location = Location::firstOrNew(['id' => $locationId]);
        $location->name = $locationData['name'] ?? ($locationData['cityName'] ?? 'Unknown');
        $location->address = $locationData['address'] ?? '';
        $location->postal_code = $locationData['postalCode'] ?? ($locationData['zipCode'] ?? '');
        $location->latitude = $locationData['latitude'] ?? 0;
        $location->longitude = $locationData['longitude'] ?? 0;
        $location->save();
        
        $this->stats['locations_saved']++;
        
        // Link location to venue
        $venue->location_id = $location->id;
        $venue->save();
    }
    
    /**
     * Process city data
     * 
     * @param array|string $cityData City data
     * @param Venue $venue Related venue
     * @return void
     */
    private function processCity($cityData, Venue $venue)
    {
        if (is_string($cityData)) {
            $cityName = $cityData;
            $cityId = Str::slug($cityName);
        } else {
            $cityName = $cityData['name'] ?? 'Unknown';
            $cityId = $cityData['id'] ?? Str::slug($cityName);
        }
        
        if (isset($this->uniqueCities[$cityId])) {
            $city = City::where('id', $cityId)->orWhere('name', $cityName)->first();
        } else {
            $this->uniqueCities[$cityId] = true;
            
            $city = City::firstOrNew(['id' => $cityId]);
            $city->name = $cityName;
            $city->slug = Str::slug($cityName);
            $city->save();
            
            $this->stats['cities_saved']++;
        }
        
        if ($city) {
            // Link city to venue via pivot table
            if (!$venue->cities()->where('city_id', $city->id)->exists()) {
                $venue->cities()->attach($city->id);
                $this->stats['relationships_created']++;
            }
        }
    }
    
    /**
     * Process treatment data
     * 
     * @param array $treatmentData Treatment data
     * @param Venue $venue Related venue
     * @return void
     */
    private function processTreatment(array $treatmentData, Venue $venue)
    {
        $treatmentId = $treatmentData['id'] ?? Str::uuid();
        
        if (isset($this->uniqueTreatments[$treatmentId])) {
            $treatment = Treatment::where('id', $treatmentId)->first();
        } else {
            $this->uniqueTreatments[$treatmentId] = true;
            
            $treatment = Treatment::firstOrNew(['id' => $treatmentId]);
            $treatment->name = $treatmentData['name'] ?? 'Unknown';
            $treatment->description = $treatmentData['description'] ?? '';
            
            // Handle different price formats
            if (isset($treatmentData['price'])) {
                $treatment->price = $treatmentData['price'];
            } elseif (isset($treatmentData['priceRange']['minSalePrice']['salePriceAmount'])) {
                $treatment->price = $treatmentData['priceRange']['minSalePrice']['salePriceAmount'];
            } elseif (isset($treatmentData['priceRange']['minSalePriceAmount'])) {
                $treatment->price = $treatmentData['priceRange']['minSalePriceAmount'];
            } else {
                $treatment->price = 0;
            }
            
            // Handle different duration formats
            if (isset($treatmentData['duration'])) {
                $treatment->duration = $treatmentData['duration'];
            } elseif (isset($treatmentData['durationRange']['minDurationMinutes'])) {
                $treatment->duration = $treatmentData['durationRange']['minDurationMinutes'];
            } else {
                $treatment->duration = 0;
            }
            
            $treatment->slug = Str::slug($treatment->name);
            $treatment->save();
            
            $this->stats['treatments_saved']++;
        }
        
        if ($treatment) {
            // Link treatment to venue via the proper pivot table
            try {
                if (!DB::table('treatment_venue')->where('treatment_id', $treatment->id)->where('venue_id', $venue->id)->exists()) {
                    DB::table('treatment_venue')->insert([
                        'treatment_id' => $treatment->id,
                        'venue_id' => $venue->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $this->stats['relationships_created']++;
                }
            } catch (\Exception $e) {
                // Log error but continue processing
                Log::error("Error creating treatment-venue relationship: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Process image data
     * 
     * @param array|string $imageData Image data or URL
     * @param Venue $venue Related venue
     * @return void
     */
    private function processImage($imageData, Venue $venue)
    {
        // Handle different image formats
        if (is_string($imageData)) {
            $imageUrl = $imageData;
        } elseif (isset($imageData['url'])) {
            $imageUrl = $imageData['url'];
        } elseif (isset($imageData['uris'])) {
            // Get the largest resolution available
            $uris = $imageData['uris'];
            if (isset($uris['1280x800'])) {
                $imageUrl = $uris['1280x800'];
            } elseif (isset($uris['1080x720'])) {
                $imageUrl = $uris['1080x720'];
            } elseif (isset($uris['720x480'])) {
                $imageUrl = $uris['720x480'];
            } elseif (isset($uris['360x240'])) {
                $imageUrl = $uris['360x240'];
            } else {
                // If no predefined resolution is found, get the first URI
                $imageUrl = reset($uris);
            }
        } else {
            return; // No valid image URL found
        }
        
        if (empty($imageUrl)) {
            return;
        }
        
        $image = new Image();
        $image->venue_id = $venue->id;
        $image->url = $imageUrl;
        $image->type = 'venue';
        $image->save();
        
        $this->stats['images_saved']++;
    }
    
    /**
     * Process opening hour data
     * 
     * @param array $hourData Opening hour data
     * @param Venue $venue Related venue
     * @return void
     */
    private function processOpeningHour(array $hourData, Venue $venue)
    {
        $openingHour = new OpeningHour();
        $openingHour->venue_id = $venue->id;
        
        // Map day of week to numeric value
        $dayMap = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 0
        ];
        
        $day = $hourData['dayOfWeek'] ?? ($hourData['day'] ?? null);
        if (is_string($day) && isset($dayMap[strtolower($day)])) {
            $openingHour->day = $dayMap[strtolower($day)];
        } else {
            $openingHour->day = $day ?? 0;
        }
        
        $openingHour->open_time = $hourData['from'] ?? ($hourData['openTime'] ?? '00:00:00');
        $openingHour->close_time = $hourData['to'] ?? ($hourData['closeTime'] ?? '23:59:59');
        $openingHour->is_closed = !($hourData['open'] ?? true);
        $openingHour->save();
        
        $this->stats['opening_hours_saved']++;
    }
    
    /**
     * Process rating data
     * 
     * @param array|float $ratingData Rating data or value
     * @param Venue $venue Related venue
     * @return void
     */
    private function processRating($ratingData, Venue $venue)
    {
        if (is_numeric($ratingData)) {
            $ratingValue = $ratingData;
            $ratingCount = 0;
        } else {
            $ratingValue = $ratingData['weightedAverage'] ?? $ratingData['average'] ?? ($ratingData['value'] ?? 0);
            $ratingCount = $ratingData['count'] ?? 0;
        }
        
        $rating = Rating::firstOrNew(['venue_id' => $venue->id]);
        $rating->value = $ratingValue;
        $rating->count = $ratingCount;
        $rating->save();
        
        $this->stats['ratings_saved']++;
    }
    
    /**
     * Create relationships between entities
     * 
     * @return void
     */
    private function createRelationships()
    {
        $this->info("Creating city-treatment relationships...");
        
        // Create relationships between cities and treatments
        $venuesWithCitiesAndTreatments = Venue::with('cities', 'treatments')->get();
        
        $progress = $this->output->createProgressBar(count($venuesWithCitiesAndTreatments));
        $progress->start();
        
        foreach ($venuesWithCitiesAndTreatments as $venue) {
            foreach ($venue->cities as $city) {
                foreach ($venue->treatments as $treatment) {
                    // Create city-treatment relationships via the pivot table
                    if (!$city->treatments()->where('treatment_id', $treatment->id)->exists()) {
                        $city->treatments()->attach($treatment->id);
                        $this->stats['relationships_created']++;
                    }
                }
            }
            $progress->advance();
        }
        
        $progress->finish();
        $this->line('');
    }
    
    /**
     * Validate database schema
     * 
     * @return bool
     */
    private function validateSchema(): bool
    {
        $requiredTables = [
            'venues', 'cities', 'treatments', 'locations', 
            'opening_hours', 'images', 'ratings'
        ];
        
        $missingTables = [];
        
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $missingTables[] = $table;
            }
        }
        
        if (!empty($missingTables)) {
            $this->error("Missing required tables: " . implode(', ', $missingTables));
            return false;
        }
        
        return true;
    }
    
    /**
     * Clear existing data
     * 
     * @return void
     */
    private function clearExistingData()
    {
        $this->info("Clearing existing data...");
        
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Truncate tables in reverse dependency order
        Rating::truncate();
        OpeningHour::truncate();
        Image::truncate();
        
        // Clear pivot tables
        DB::table('city_treatment')->truncate();
        DB::table('city_venue')->truncate();
        DB::table('treatment_venue')->truncate();
        
        // Truncate main tables
        Treatment::truncate();
        Location::truncate();
        Venue::truncate();
        City::truncate();
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        $this->info("All existing data has been cleared.");
    }
    
    /**
     * Display statistics
     * 
     * @param float $executionTime Execution time in seconds
     * @return void
     */
    private function displayStatistics(float $executionTime)
    {
        $this->info('');
        $this->info('===== Processing Statistics =====');
        $this->info("Files found: {$this->stats['files_total']}");
        $this->info("Files processed: {$this->stats['files_processed']}");
        $this->info("Files skipped: {$this->stats['files_skipped']}");
        $this->info("Files failed: {$this->stats['files_failed']}");
        $this->info("Venues found: {$this->stats['venues_found']}");
        $this->info("Venues saved: {$this->stats['venues_saved']}");
        $this->info("Cities saved: {$this->stats['cities_saved']}");
        $this->info("Locations saved: {$this->stats['locations_saved']}");
        $this->info("Treatments saved: {$this->stats['treatments_saved']}");
        $this->info("Images saved: {$this->stats['images_saved']}");
        $this->info("Opening hours saved: {$this->stats['opening_hours_saved']}");
        $this->info("Ratings saved: {$this->stats['ratings_saved']}");
        $this->info("Relationships created: {$this->stats['relationships_created']}");
        $this->info("Execution time: {$executionTime} seconds");
        $this->info("Peak memory usage: " . $this->formatBytes(memory_get_peak_usage(true)));
        $this->info('=================================');
    }
    
    /**
     * Format bytes to human-readable format
     * 
     * @param int $bytes Number of bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Process city data from location tree
     * 
     * @param array $treeData Location tree data
     * @param Venue $venue Related venue
     * @return void
     */
    private function processCityFromTree(array $treeData, Venue $venue)
    {
        // Extract city name and country from tree data
        if (isset($treeData['name'])) {
            $nameParts = explode(',', $treeData['name']);
            $cityName = trim($nameParts[1] ?? $nameParts[0]); // Assume format is "Neighborhood, City"
            $countryCode = $treeData['countryCode'] ?? null;
            
            $cityId = Str::slug($cityName . '-' . $countryCode);
            
            if (isset($this->uniqueCities[$cityId])) {
                $city = City::where('id', $cityId)->orWhere('name', $cityName)->first();
            } else {
                $this->uniqueCities[$cityId] = true;
                
                $city = City::firstOrNew(['id' => $cityId]);
                $city->name = $cityName;
                $city->slug = Str::slug($cityName);
                
                if ($countryCode) {
                    // Find or create country
                    $country = Country::firstOrCreate(
                        ['code' => $countryCode],
                        ['name' => $countryCode] // Just use code as name for now
                    );
                    $city->country_id = $country->id;
                }
                
                $city->save();
                
                $this->stats['cities_saved']++;
            }
            
            if ($city) {
                // Link city to venue via pivot table
                if (!$venue->cities()->where('city_id', $city->id)->exists()) {
                    $venue->cities()->attach($city->id);
                    $this->stats['relationships_created']++;
                }
            }
        }
    }
} 