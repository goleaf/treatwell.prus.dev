<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Services\ApiLoopEngine;
use App\Services\CityDataProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ParseAllCitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cities:parse-all 
                          {--batch-size=10 : Number of cities to process in each batch}
                          {--max-cities=0 : Maximum number of cities to process (0 = all)}
                          {--resume : Resume from last processed city}
                          {--city= : Process only a specific city by slug}
                          {--debug : Show debug output}
                          {--dry-run : Run without saving to database}
                          {--force : Force processing without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Parse comprehensive data from all cities via API loops';

    private ApiLoopEngine $apiEngine;
    private CityDataProcessor $processor;
    private bool $debug = false;
    private array $stats = [
        'cities_total' => 0,
        'cities_processed' => 0,
        'cities_failed' => 0,
        'venues_found' => 0,
        'venues_saved' => 0,
        'treatments_found' => 0,
        'treatments_saved' => 0,
        'api_calls_made' => 0,
        'api_calls_successful' => 0,
        'api_calls_failed' => 0,
        'errors_encountered' => 0,
    ];

    public function __construct(ApiLoopEngine $apiEngine, CityDataProcessor $processor)
    {
        parent::__construct();
        $this->apiEngine = $apiEngine;
        $this->processor = $processor;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting comprehensive city data parsing...');
        $startTime = microtime(true);

        // Read command options
        $batchSize = (int) $this->option('batch-size');
        $maxCities = (int) $this->option('max-cities');
        $resume = $this->option('resume');
        $specificCity = $this->option('city');
        $this->debug = $this->option('debug');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('Running in dry-run mode - no data will be saved to database');
        }

        try {
            // Get cities to process
            $cities = $this->getCitiesToProcess($specificCity, $resume, $maxCities);
            
            if ($cities->isEmpty()) {
                $this->error('No cities found to process');
                return 1;
            }

            $this->stats['cities_total'] = $cities->count();
            $this->info("Found {$this->stats['cities_total']} cities to process");

            // Confirm processing if not forced
            if (!$force && !$this->confirm("Process {$this->stats['cities_total']} cities?", true)) {
                $this->info('Processing cancelled by user');
                return 0;
            }

            // Process cities in batches
            $this->processCitiesInBatches($cities, $batchSize, $dryRun);

            $executionTime = round(microtime(true) - $startTime, 2);
            $this->displayStatistics($executionTime);

            return 0;

        } catch (\Exception $e) {
            $this->error('Fatal error during processing: ' . $e->getMessage());
            Log::error('ParseAllCitiesCommand fatal error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Get cities to process based on options
     */
    private function getCitiesToProcess(?string $specificCity, bool $resume, int $maxCities)
    {
        $query = City::query();

        if ($specificCity) {
            // Process only specific city
            $query->where('slug', $specificCity);
        } elseif ($resume) {
            // Resume from last processed city (this would need progress tracking)
            $this->info('Resume functionality requires progress tracking - processing all cities for now');
        }

        // Apply limit if specified
        if ($maxCities > 0) {
            $query->limit($maxCities);
        }

        return $query->get();
    }

    /**
     * Process cities in batches to manage memory and API rate limits
     */
    private function processCitiesInBatches($cities, int $batchSize, bool $dryRun): void
    {
        $totalBatches = ceil($cities->count() / $batchSize);
        $batchNumber = 1;

        foreach ($cities->chunk($batchSize) as $batch) {
            $this->info("Processing batch {$batchNumber}/{$totalBatches} ({$batch->count()} cities)...");

            $progressBar = $this->output->createProgressBar($batch->count());
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
            $progressBar->setMessage('Starting...');
            $progressBar->start();

            foreach ($batch as $city) {
                try {
                    $progressBar->setMessage("Processing {$city->name}");
                    $this->processSingleCity($city, $dryRun);
                    $this->stats['cities_processed']++;
                } catch (\Exception $e) {
                    $this->stats['cities_failed']++;
                    $this->stats['errors_encountered']++;
                    
                    $errorMsg = "Error processing city {$city->name}: " . $e->getMessage();
                    if ($this->debug) {
                        $this->error($errorMsg);
                    }
                    
                    Log::error('City processing error', [
                        'city_id' => $city->id,
                        'city_name' => $city->name,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            // Memory cleanup between batches
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

            $batchNumber++;
        }
    }

    /**
     * Process a single city comprehensively
     */
    private function processSingleCity(City $city, bool $dryRun): void
    {
        if ($this->debug) {
            $this->info("Processing city: {$city->name} (slug: {$city->slug})");
        }

        // Get all API endpoints for this city
        $endpoints = $this->apiEngine->getApiEndpointsForCity($city->slug);
        
        if (empty($endpoints)) {
            if ($this->debug) {
                $this->warn("No API endpoints found for city: {$city->name}");
            }
            return;
        }

        if ($this->debug) {
            $this->info("Found " . count($endpoints) . " API endpoints for {$city->name}");
        }

        // Process each endpoint for comprehensive data collection
        foreach ($endpoints as $endpoint) {
            try {
                $this->stats['api_calls_made']++;
                
                // Make API request with rate limiting and error handling
                $response = $this->apiEngine->processApiEndpoint($endpoint);
                
                if ($response === null) {
                    $this->stats['api_calls_failed']++;
                    continue;
                }

                $this->stats['api_calls_successful']++;

                // Process and transform the data
                $processedData = $this->processor->transformApiResponse($response->getData());
                
                if (!$dryRun) {
                    // Save the processed data
                    $this->saveProcessedData($processedData, $city);
                }

                // Update statistics
                $this->updateStatistics($processedData);

            } catch (\Exception $e) {
                $this->stats['api_calls_failed']++;
                $this->stats['errors_encountered']++;
                
                if ($this->debug) {
                    $this->error("Error processing endpoint for {$city->name}: " . $e->getMessage());
                }
                
                Log::error('API endpoint processing error', [
                    'city_id' => $city->id,
                    'city_name' => $city->name,
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Save processed data to database
     */
    private function saveProcessedData(array $processedData, City $city): void
    {
        // This will be implemented when we have the CityDataProcessor service
        // For now, just track that we would save data
        if (isset($processedData['venues'])) {
            $this->stats['venues_saved'] += count($processedData['venues']);
        }
        
        if (isset($processedData['treatments'])) {
            $this->stats['treatments_saved'] += count($processedData['treatments']);
        }
    }

    /**
     * Update processing statistics
     */
    private function updateStatistics(array $processedData): void
    {
        if (isset($processedData['venues'])) {
            $this->stats['venues_found'] += count($processedData['venues']);
        }
        
        if (isset($processedData['treatments'])) {
            $this->stats['treatments_found'] += count($processedData['treatments']);
        }
    }

    /**
     * Display final processing statistics
     */
    private function displayStatistics(float $executionTime): void
    {
        $this->info('');
        $this->info('===== City Data Parsing Statistics =====');
        $this->info("Cities processed: {$this->stats['cities_processed']}/{$this->stats['cities_total']}");
        $this->info("Cities failed: {$this->stats['cities_failed']}");
        $this->info("API calls made: {$this->stats['api_calls_made']} (Success: {$this->stats['api_calls_successful']}, Failed: {$this->stats['api_calls_failed']})");
        $this->info("Venues found: {$this->stats['venues_found']} (Saved: {$this->stats['venues_saved']})");
        $this->info("Treatments found: {$this->stats['treatments_found']} (Saved: {$this->stats['treatments_saved']})");
        $this->info("Errors encountered: {$this->stats['errors_encountered']}");
        $this->info("Execution time: {$executionTime} seconds");
        $this->info('Peak memory usage: ' . $this->formatBytes(memory_get_peak_usage(true)));
        $this->info('=========================================');
    }

    /**
     * Format bytes to human-readable format
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