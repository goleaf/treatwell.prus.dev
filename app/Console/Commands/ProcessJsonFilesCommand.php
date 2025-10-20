<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Venue;
use App\Models\City;
use App\Models\Location;
use App\Models\Treatment;
use App\Models\Procedure;
use App\Models\Image;
use App\Models\Rating;
use App\Models\OpeningHour;

class ProcessJsonFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'json:process
                            {directory=app/json : Directory containing JSON files to process}
                            {--max-files=0 : Maximum number of files to process (0 = all)}
                            {--batch-size=20 : Number of files to process in a batch}
                            {--force : Force processing even if files were already processed}
                            {--dry-run : Run without saving to database}
                            {--debug : Show debug output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process multiple JSON files, analyze content and save to database with relations';

    /**
     * Statistics tracking
     */
    private $stats = [
        'files_processed' => 0,
        'files_skipped' => 0,
        'files_failed' => 0,
        'venues_created' => 0,
        'venues_updated' => 0,
        'cities_created' => 0,
        'locations_created' => 0,
        'treatments_created' => 0,
        'images_saved' => 0,
        'relations_created' => 0
    ];

    /**
     * Memory cache for entities to avoid repeated database queries
     */
    private $entityCache = [
        'venues' => [],
        'cities' => [],
        'treatments' => [],
        'procedures' => []
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting JSON file processing...');
        
        $startTime = microtime(true);
        $directory = $this->argument('directory');
        $maxFiles = (int) $this->option('max-files');
        $batchSize = (int) $this->option('batch-size');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        $debug = $this->option('debug');
        
        // Check if directory exists
        if (!Storage::exists($directory)) {
            $this->error("Directory {$directory} does not exist.");
            return 1;
        }
        
        // Get all JSON files in the directory
        $jsonFiles = $this->getJsonFiles($directory);
        $totalFiles = count($jsonFiles);
        
        if ($totalFiles === 0) {
            $this->warn("No JSON files found in {$directory}.");
            return 0;
        }
        
        $this->info("Found {$totalFiles} JSON files to process.");
        
        // Apply max files limit if specified
        if ($maxFiles > 0 && $totalFiles > $maxFiles) {
            $jsonFiles = array_slice($jsonFiles, 0, $maxFiles);
            $this->info("Processing only the first {$maxFiles} files.");
        }
        
        // Process files in batches
        $fileCount = count($jsonFiles);
        $batches = ceil($fileCount / $batchSize);
        
        $this->info("Processing files in {$batches} batches of {$batchSize}...");
        
        // Start a database transaction to improve performance
        if (!$dryRun) {
            DB::beginTransaction();
        }
        
        try {
            // Process each batch
            for ($i = 0; $i < $batches; $i++) {
                $batchFiles = array_slice($jsonFiles, $i * $batchSize, $batchSize);
                $this->processBatch($batchFiles, $directory, $force, $dryRun, $debug);
                
                // Show progress
                $processed = min(($i + 1) * $batchSize, $fileCount);
                $this->info("Processed {$processed}/{$fileCount} files.");
            }
            
            // Create relationships between entities after all data is loaded
            if (!$dryRun) {
                $this->info('Creating relationships between entities...');
                $this->createRelationships();
                
                // Commit the transaction
                DB::commit();
                $this->info('Database transaction committed successfully.');
            } else {
                $this->info('Dry run completed. No changes were made to the database.');
            }
            
            // Display statistics
            $executionTime = microtime(true) - $startTime;
            $this->displayStatistics($executionTime);
            
            return 0;
        } catch (\Exception $e) {
            // Roll back the transaction in case of errors
            if (!$dryRun) {
                DB::rollBack();
                $this->error('Database transaction rolled back due to errors.');
            }
            
            $this->error('Error during processing: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
    
    /**
     * Get all JSON files in the specified directory.
     *
     * @param string $directory
     * @return array
     */
    private function getJsonFiles(string $directory): array
    {
        $allFiles = Storage::files($directory);
        
        // Filter to only include JSON files
        return array_filter($allFiles, function ($file) {
            return Str::endsWith($file, '.json');
        });
    }
    
    /**
     * Process a batch of JSON files.
     *
     * @param array $files
     * @param string $directory
     * @param bool $force
     * @param bool $dryRun
     * @param bool $debug
     * @return void
     */
    private function processBatch(array $files, string $directory, bool $force, bool $dryRun, bool $debug): void
    {
        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->start();
        
        foreach ($files as $file) {
            $this->processJsonFile($file, $force, $dryRun, $debug);
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->line('');
    }
    
    /**
     * Process a single JSON file.
     *
     * @param string $filePath
     * @param bool $force
     * @param bool $dryRun
     * @param bool $debug
     * @return void
     */
    private function processJsonFile(string $filePath, bool $force, bool $dryRun, bool $debug): void
    {
        try {
            // Get file contents
            $jsonContent = Storage::get($filePath);
            
            // Parse JSON
            $data = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->debug("Error parsing JSON from {$filePath}: " . json_last_error_msg(), $debug);
                $this->stats['files_failed']++;
                return;
            }
            
            // Process the content based on its structure
            // Here we're assuming a structure similar to what you've been using in your tests
            if (isset($data['results']) && is_array($data['results'])) {
                foreach ($data['results'] as $result) {
                    if (isset($result['type']) && $result['type'] === 'venue' && isset($result['data'])) {
                        $this->processVenueData($result['data'], $dryRun, $debug);
                    }
                }
            } else {
                // Alternative format: direct venue data
                if (isset($data['id'])) {
                    $this->processVenueData($data, $dryRun, $debug);
                } else {
                    $this->debug("Unrecognized JSON structure in {$filePath}", $debug);
                    $this->stats['files_skipped']++;
                    return;
                }
            }
            
            $this->stats['files_processed']++;
        } catch (\Exception $e) {
            $this->debug("Error processing file {$filePath}: " . $e->getMessage(), $debug);
            $this->stats['files_failed']++;
        }
    }
    
    /**
     * Process venue data from a JSON file.
     *
     * @param array $venueData
     * @param bool $dryRun
     * @param bool $debug
     * @return void
     */
    private function processVenueData(array $venueData, bool $dryRun, bool $debug): void
    {
        if (!isset($venueData['id']) || !isset($venueData['name'])) {
            $this->debug("Skipping venue data without ID or name", $debug);
            return;
        }
        
        $externalId = $venueData['id'];
        
        // Check if venue already exists
        $venue = null;
        if (!$dryRun) {
            if (isset($this->entityCache['venues'][$externalId])) {
                $venue = $this->entityCache['venues'][$externalId];
            } else {
                $venue = Venue::where('external_id', $externalId)->first();
                if ($venue) {
                    $this->entityCache['venues'][$externalId] = $venue;
                }
            }
        }
        
        // Create or update venue
        if (!$venue && !$dryRun) {
            $venue = new Venue();
            $venue->external_id = $externalId;
            $venue->name = $venueData['name'];
            $venue->slug = $venueData['slug'] ?? Str::slug($venueData['name']);
            $venue->description = $venueData['description'] ?? null;
            $venue->url = $venueData['url'] ?? null;
            $venue->address = $venueData['address'] ?? null;
            $venue->phone = $venueData['phone'] ?? null;
            $venue->email = $venueData['email'] ?? null;
            $venue->website = $venueData['website'] ?? null;
            $venue->source = 'json_import';
            
            // Save latitude/longitude if directly on venue
            if (isset($venueData['latitude']) && isset($venueData['longitude'])) {
                $venue->latitude = $venueData['latitude'];
                $venue->longitude = $venueData['longitude'];
            }
            
            $venue->save();
            $this->entityCache['venues'][$externalId] = $venue;
            $this->stats['venues_created']++;
            
            $this->debug("Created venue: {$venue->name}", $debug);
        } elseif ($venue && !$dryRun) {
            // Update existing venue
            $venue->name = $venueData['name'];
            if (isset($venueData['description'])) $venue->description = $venueData['description'];
            if (isset($venueData['url'])) $venue->url = $venueData['url'];
            if (isset($venueData['address'])) $venue->address = $venueData['address'];
            if (isset($venueData['phone'])) $venue->phone = $venueData['phone'];
            if (isset($venueData['email'])) $venue->email = $venueData['email'];
            if (isset($venueData['website'])) $venue->website = $venueData['website'];
            
            $venue->save();
            $this->stats['venues_updated']++;
            
            $this->debug("Updated venue: {$venue->name}", $debug);
        }
        
        if (!$dryRun && $venue) {
            // Process location data
            if (isset($venueData['location'])) {
                $this->processLocation($venueData['location'], $venue, $debug);
            }
            
            // Process city data
            if (isset($venueData['city'])) {
                $this->processCity($venueData['city'], $venue, $debug);
            }
            
            // Process treatments
            if (isset($venueData['treatments']) && is_array($venueData['treatments'])) {
                foreach ($venueData['treatments'] as $treatmentData) {
                    $this->processTreatment($treatmentData, $venue, $debug);
                }
            }
            
            // Process images
            if (isset($venueData['primaryImage'])) {
                $this->processImage($venueData['primaryImage'], $venue, 'primary', $debug);
            }
            
            if (isset($venueData['images']) && is_array($venueData['images'])) {
                foreach ($venueData['images'] as $imageData) {
                    $this->processImage($imageData, $venue, 'gallery', $debug);
                }
            }
            
            // Process opening hours
            if (isset($venueData['openingHours']) && is_array($venueData['openingHours'])) {
                foreach ($venueData['openingHours'] as $hourData) {
                    $this->processOpeningHour($hourData, $venue, $debug);
                }
            }
            
            // Process rating
            if (isset($venueData['rating'])) {
                $this->processRating($venueData['rating'], $venue, $debug);
            }
        }
    }
    
    /**
     * Process location data for a venue.
     *
     * @param array $locationData
     * @param Venue $venue
     * @param bool $debug
     * @return void
     */
    private function processLocation(array $locationData, Venue $venue, bool $debug): void
    {
        $location = Location::where('venue_id', $venue->id)->first() ?? new Location();
        
        $location->venue_id = $venue->id;
        $location->name = $locationData['name'] ?? null;
        
        // Process address
        if (isset($locationData['address'])) {
            if (isset($locationData['address']['addressLines']) && is_array($locationData['address']['addressLines'])) {
                $location->address_line1 = $locationData['address']['addressLines'][0] ?? null;
                $location->address_line2 = $locationData['address']['addressLines'][1] ?? null;
            }
            
            $location->postal_code = $locationData['address']['postalCode'] ?? null;
        }
        
        // Process coordinates
        if (isset($locationData['point']) || isset($locationData['coordinates'])) {
            $coordinates = $locationData['point'] ?? $locationData['coordinates'];
            
            if (isset($coordinates['lat']) || isset($coordinates['latitude'])) {
                $location->latitude = $coordinates['lat'] ?? $coordinates['latitude'];
            }
            
            if (isset($coordinates['lon']) || isset($coordinates['lng']) || isset($coordinates['longitude'])) {
                $location->longitude = $coordinates['lon'] ?? $coordinates['lng'] ?? $coordinates['longitude'];
            }
        }
        
        $location->save();
        
        // Update venue with location reference
        $venue->location_id = $location->id;
        
        // Also copy coordinates to venue for direct access
        if ($location->latitude && $location->longitude) {
            $venue->latitude = $location->latitude;
            $venue->longitude = $location->longitude;
        }
        
        $venue->save();
        
        $this->stats['locations_created']++;
        $this->debug("Processed location for venue {$venue->name}", $debug);
    }
    
    /**
     * Process city data for a venue.
     *
     * @param array|string $cityData
     * @param Venue $venue
     * @param bool $debug
     * @return void
     */
    private function processCity($cityData, Venue $venue, bool $debug): void
    {
        // Handle different formats of city data
        $cityName = '';
        $citySlug = '';
        
        if (is_array($cityData)) {
            $cityName = $cityData['name'] ?? '';
            $citySlug = $cityData['slug'] ?? $cityData['id'] ?? Str::slug($cityName);
        } elseif (is_string($cityData)) {
            $cityName = $cityData;
            $citySlug = Str::slug($cityName);
        } else {
            $this->debug("Invalid city data format for venue {$venue->name}", $debug);
            return;
        }
        
        if (empty($cityName)) {
            $this->debug("Empty city name for venue {$venue->name}", $debug);
            return;
        }
        
        // Check if city exists in cache
        $city = null;
        if (isset($this->entityCache['cities'][$citySlug])) {
            $city = $this->entityCache['cities'][$citySlug];
        } else {
            // Find city by slug or create new
            $city = City::firstOrCreate(
                ['slug' => $citySlug],
                [
                    'name' => $cityName,
                    'is_main_city' => false
                ]
            );
            
            $this->entityCache['cities'][$citySlug] = $city;
            $this->stats['cities_created']++;
        }
        
        // Link venue to city
        $venue->city_id = $city->id;
        $venue->save();
        
        // Create many-to-many relationship in pivot table
        if (!$venue->cities()->where('city_id', $city->id)->exists()) {
            $venue->cities()->attach($city->id);
            $this->stats['relations_created']++;
        }
        
        $this->debug("Linked venue {$venue->name} to city {$city->name}", $debug);
    }
    
    /**
     * Process treatment data for a venue.
     *
     * @param array $treatmentData
     * @param Venue $venue
     * @param bool $debug
     * @return void
     */
    private function processTreatment(array $treatmentData, Venue $venue, bool $debug): void
    {
        if (!isset($treatmentData['id']) || !isset($treatmentData['name'])) {
            $this->debug("Skipping treatment without ID or name for venue {$venue->name}", $debug);
            return;
        }
        
        $externalId = $treatmentData['id'];
        $slug = $treatmentData['slug'] ?? Str::slug($treatmentData['name']);
        
        // Check if treatment exists in cache
        $treatment = null;
        if (isset($this->entityCache['treatments'][$externalId])) {
            $treatment = $this->entityCache['treatments'][$externalId];
        } else {
            // Find treatment by external ID or create new
            $treatment = Treatment::firstOrCreate(
                ['external_id' => $externalId],
                [
                    'name' => $treatmentData['name'],
                    'price' => $treatmentData['price'] ?? null,
                    'duration' => $treatmentData['duration'] ?? null,
                ]
            );
            
            $this->entityCache['treatments'][$externalId] = $treatment;
            $this->stats['treatments_created']++;
        }
        
        // Create many-to-many relationship in pivot table
        if (!$venue->treatments()->where('treatment_id', $treatment->id)->exists()) {
            $venue->treatments()->attach($treatment->id);
            $this->stats['relations_created']++;
        }
        
        $this->debug("Linked treatment {$treatment->name} to venue {$venue->name}", $debug);
    }
    
    /**
     * Process image data for a venue.
     *
     * @param array $imageData
     * @param Venue $venue
     * @param string $type
     * @param bool $debug
     * @return void
     */
    private function processImage(array $imageData, Venue $venue, string $type, bool $debug): void
    {
        $imageUrl = '';
        
        // Handle different formats of image data
        if (isset($imageData['uris']) && is_array($imageData['uris'])) {
            // Get the largest image available
            if (isset($imageData['uris']['1280x800'])) {
                $imageUrl = $imageData['uris']['1280x800'];
            } elseif (isset($imageData['uris']['720x480'])) {
                $imageUrl = $imageData['uris']['720x480'];
            } elseif (isset($imageData['uris']['360x240'])) {
                $imageUrl = $imageData['uris']['360x240'];
            } else {
                // Get the first URI available
                $imageUrl = reset($imageData['uris']);
            }
        } elseif (isset($imageData['url'])) {
            $imageUrl = $imageData['url'];
        }
        
        if (empty($imageUrl)) {
            $this->debug("Empty image URL for venue {$venue->name}", $debug);
            return;
        }
        
        // Check if image already exists
        $existingImage = Image::where('venue_id', $venue->id)
            ->where('url', $imageUrl)
            ->first();
        
        if (!$existingImage) {
            $image = new Image();
            $image->venue_id = $venue->id;
            $image->url = $imageUrl;
            $image->type = $type;
            $image->save();
            
            $this->stats['images_saved']++;
            $this->debug("Saved {$type} image for venue {$venue->name}", $debug);
        }
    }
    
    /**
     * Process opening hour data for a venue.
     *
     * @param array $hourData
     * @param Venue $venue
     * @param bool $debug
     * @return void
     */
    private function processOpeningHour(array $hourData, Venue $venue, bool $debug): void
    {
        if (!isset($hourData['dayOfWeek'])) {
            $this->debug("Skipping opening hour without day of week for venue {$venue->name}", $debug);
            return;
        }
        
        // Convert day of week to numeric value (0-6, Sunday = 0)
        $dayMap = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6
        ];
        
        $day = $dayMap[strtolower($hourData['dayOfWeek'])] ?? -1;
        
        if ($day === -1) {
            $this->debug("Invalid day of week format: {$hourData['dayOfWeek']}", $debug);
            return;
        }
        
        // Check if opening hour already exists for this day
        $existingHour = OpeningHour::where('venue_id', $venue->id)
            ->where('day', $day)
            ->first();
        
        if ($existingHour) {
            // Update existing
            $existingHour->open_time = $hourData['from'] ?? '00:00:00';
            $existingHour->close_time = $hourData['to'] ?? '00:00:00';
            $existingHour->is_closed = !($hourData['open'] ?? true);
            $existingHour->save();
        } else {
            // Create new
            $hour = new OpeningHour();
            $hour->venue_id = $venue->id;
            $hour->day = $day;
            $hour->open_time = $hourData['from'] ?? '00:00:00';
            $hour->close_time = $hourData['to'] ?? '00:00:00';
            $hour->is_closed = !($hourData['open'] ?? true);
            $hour->save();
        }
        
        $this->debug("Processed opening hour for day {$day} for venue {$venue->name}", $debug);
    }
    
    /**
     * Process rating data for a venue.
     *
     * @param array $ratingData
     * @param Venue $venue
     * @param bool $debug
     * @return void
     */
    private function processRating(array $ratingData, Venue $venue, bool $debug): void
    {
        $ratingValue = $ratingData['weightedAverage'] ?? $ratingData['average'] ?? 0;
        $ratingCount = $ratingData['count'] ?? 0;
        
        if ($ratingValue <= 0) {
            $this->debug("Skipping invalid rating value for venue {$venue->name}", $debug);
            return;
        }
        
        // Check if rating already exists
        $existingRating = Rating::where('venue_id', $venue->id)->first();
        
        if ($existingRating) {
            // Update existing
            $existingRating->value = $ratingValue;
            $existingRating->count = $ratingCount;
            $existingRating->save();
        } else {
            // Create new
            $rating = new Rating();
            $rating->venue_id = $venue->id;
            $rating->value = $ratingValue;
            $rating->count = $ratingCount;
            $rating->save();
        }
        
        $this->debug("Processed rating {$ratingValue} ({$ratingCount} reviews) for venue {$venue->name}", $debug);
    }
    
    /**
     * Create relationships between entities after all data is loaded.
     *
     * @return void
     */
    private function createRelationships(): void
    {
        $this->info('Creating city-treatment relationships...');
        
        // Find all venues with both city and treatments
        $venues = Venue::whereNotNull('city_id')->get();
        
        $progress = $this->output->createProgressBar($venues->count());
        $progress->start();
        
        foreach ($venues as $venue) {
            // Get related treatments
            $treatments = $venue->treatments;
            
            // If venue has treatments, link them to the venue's city
            if ($treatments->count() > 0 && $venue->city_id) {
                foreach ($treatments as $treatment) {
                    // Create city-treatment relationship if it doesn't exist
                    if (!DB::table('city_treatment')
                        ->where('city_id', $venue->city_id)
                        ->where('treatment_id', $treatment->id)
                        ->exists()) {
                        
                        DB::table('city_treatment')->insert([
                            'city_id' => $venue->city_id,
                            'treatment_id' => $treatment->id,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        
                        $this->stats['relations_created']++;
                    }
                }
            }
            
            $progress->advance();
        }
        
        $progress->finish();
        $this->line('');
    }
    
    /**
     * Display debug message if debug is enabled.
     *
     * @param string $message
     * @param bool $debug
     * @return void
     */
    private function debug(string $message, bool $debug): void
    {
        if ($debug) {
            $this->line("<fg=gray>{$message}</>");
        }
    }
    
    /**
     * Display statistics after processing.
     *
     * @param float $executionTime
     * @return void
     */
    private function displayStatistics(float $executionTime): void
    {
        $this->info(str_repeat('=', 30));
        $this->info('Processing Statistics');
        $this->info(str_repeat('=', 30));
        
        $this->line("Files processed: {$this->stats['files_processed']}");
        $this->line("Files skipped: {$this->stats['files_skipped']}");
        $this->line("Files failed: {$this->stats['files_failed']}");
        $this->line("Venues created: {$this->stats['venues_created']}");
        $this->line("Venues updated: {$this->stats['venues_updated']}");
        $this->line("Cities created: {$this->stats['cities_created']}");
        $this->line("Locations created: {$this->stats['locations_created']}");
        $this->line("Treatments created: {$this->stats['treatments_created']}");
        $this->line("Images saved: {$this->stats['images_saved']}");
        $this->line("Relations created: {$this->stats['relations_created']}");
        $this->line("Execution time: " . round($executionTime, 2) . " seconds");
        $this->line("Peak memory usage: " . $this->formatBytes(memory_get_peak_usage(true)));
        $this->info(str_repeat('=', 30));
    }
    
    /**
     * Format bytes to human-readable format.
     *
     * @param int $bytes
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
} 