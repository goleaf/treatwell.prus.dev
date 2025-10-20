<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Country;
use App\Models\Image;
use App\Models\Location;
use App\Models\OpeningHour;
use App\Models\Procedure;
use App\Models\Rating;
use App\Models\SitemapUrl;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FetchVenuesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'venues:fetch 
                          {--xml-url=https://www.treatwell.lt/site-map-venues-treatment-location-1.xml : URL of the XML file to download}
                          {--max-pages=0 : Maximum number of pages to process per endpoint (0 = all)}
                          {--max-endpoints=0 : Maximum number of endpoints to process (0 = all)}
                          {--max-venues=0 : Maximum number of venues to save (0 = all)}
                          {--debug : Show debug output}
                          {--no-db : Skip saving to database}
                          {--clear-existing : Clear existing venues before fetching}
                          {--url= : Process only a specific API URL}
                          {--reset-urls : Reset processing status of all URLs}
                          {--only-store-urls : Only download XML and store URLs}
                          {--only-process-urls : Only process stored URLs, skip downloading XML}
                          {--batch-size=100 : Number of URLs to process in a batch}
                          {--process-json : Process JSON files from storage/app/xml directory}
                          {--json-batch-size=20 : Number of JSON files to process in each batch}
                          {--max-json-files=0 : Maximum number of JSON files to process (0 = all)}
                          {--force : Force the operation without confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch venue data from Treatwell XML and API, and process stored JSON files';

    /**
     * Base API URL for Treatwell Lithuania
     */
    private string $apiBaseUrl = 'https://www.treatwell.lt/api/v1/page/browse';

    /**
     * Debug mode flag
     */
    private bool $debug = false;

    /**
     * Statistics tracking
     */
    private array $stats = [
        'xml_urls_total' => 0,
        'api_endpoints_total' => 0,
        'api_endpoints_processed' => 0,
        'api_requests_total' => 0,
        'api_requests_successful' => 0,
        'api_requests_failed' => 0,
        'venues_found' => 0,
        'venues_saved' => 0,
        'venues_skipped' => 0,
        'city_venue_links' => 0,
        'procedure_venue_links' => 0,
        'city_procedure_links' => 0,
        'json_files_total' => 0,
        'json_files_processed' => 0,
        'json_files_skipped' => 0,
        'json_files_failed' => 0,
        'json_venues_found' => 0,
        'json_venues_saved' => 0,
        'treatments_saved' => 0,
        'cities_saved' => 0,
        'locations_saved' => 0,
        'procedures_saved' => 0,
        'images_saved' => 0,
        'opening_hours_saved' => 0,
        'ratings_saved' => 0,
        'relationships_created' => 0,
    ];

    private array $uniqueVenueIds = [];

    private int $totalApiRequests = 0;

    private int $totalPagesProcessed = 0;

    private array $uniqueJsonVenues = [];

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
        $this->info('Starting venue fetch process...');
        $startTime = microtime(true);

        // Read command options
        $xmlUrl = $this->option('xml-url');
        $maxPages = (int) $this->option('max-pages');
        $maxEndpoints = (int) $this->option('max-endpoints');
        $maxVenues = (int) $this->option('max-venues');
        $saveToDb = ! $this->option('no-db');
        $clearExisting = $this->option('clear-existing');
        $resetUrls = $this->option('reset-urls');
        $onlyStoreUrls = $this->option('only-store-urls');
        $onlyProcessUrls = $this->option('only-process-urls');
        $batchSize = (int) $this->option('batch-size');
        $this->debug = $this->option('debug');
        $processJson = $this->option('process-json');
        $jsonBatchSize = (int) $this->option('json-batch-size');
        $maxJsonFiles = (int) $this->option('max-json-files');
        $force = $this->option('force');

        // Validate database schema if we're saving to DB
        if ($saveToDb && ! $this->validateSchema()) {
            $this->error('Schema validation failed. Please fix the issues and try again.');

            return 1;
        }

        // Clear existing venues if requested
        if ($clearExisting && $saveToDb) {
            if ($force || $this->confirm('Are you sure you want to clear all existing data?', false)) {
                $this->clearExistingData();
            } else {
                $this->info('Skipping clearing of existing data.');
            }
        }

        // Process JSON files if requested
        if ($processJson) {
            $this->info('Processing JSON files...');
            $this->processJsonFiles($jsonBatchSize, $maxJsonFiles);
        }
        // Skip XML/API processing if we're only processing JSON
        else {
            // Reset URL processing status if requested
            if ($resetUrls) {
                $this->resetUrlProcessingStatus();
            }

            // If a specific URL is provided, process only that URL
            if ($this->option('url')) {
                return $this->processSpecificUrl($this->option('url'));
            }

            // Step 1: Download XML and store URLs in database (unless we're only processing)
            if (! $onlyProcessUrls) {
                // Download and parse XML
                $this->info("Downloading XML file: {$xmlUrl}");
                $xmlContent = $this->downloadFile($xmlUrl);

                if (! $xmlContent) {
                    $this->error('Failed to download XML file. Aborting.');

                    return 1;
                }

                // Extract URLs from XML and store in database
                $this->info('Parsing XML content and storing URLs in database...');
                $urlCount = $this->extractAndStoreUrlsFromXml($xmlContent);

                $this->stats['xml_urls_total'] = $urlCount;
                $this->info("Extracted and stored {$urlCount} treatment location URLs from XML");

                if ($urlCount === 0) {
                    $this->error('No treatment location URLs found in XML. Aborting.');

                    return 1;
                }

                // If we're only storing URLs, exit here
                if ($onlyStoreUrls) {
                    $this->info('URLs stored in database. Use --only-process-urls to process them later.');

                    return 0;
                }
            }

            // Step 2: Process the URLs from database
            $this->info('Processing URLs from database...');

            // Get count of unprocessed URLs
            $totalUnprocessedUrls = SitemapUrl::where('is_processed', false)->count();
            $this->info("Found {$totalUnprocessedUrls} unprocessed URLs in database");

            if ($totalUnprocessedUrls === 0) {
                $this->info('No unprocessed URLs found in database. Consider using --reset-urls to reprocess all URLs.');

                return 0;
            }

            // Process URLs in batches
            $batchNumber = 1;
            $totalBatches = ceil($totalUnprocessedUrls / $batchSize);

            while (true) {
                // Get a batch of unprocessed URLs
                $urls = SitemapUrl::where('is_processed', false)
                    ->take($maxEndpoints > 0 ? min($batchSize, $maxEndpoints) : $batchSize)
                    ->get();

                if ($urls->isEmpty()) {
                    break;
                }

                $this->info("Processing batch {$batchNumber}/{$totalBatches} ({$urls->count()} URLs)...");

                // Process each URL in the batch
                $venues = $this->processUrlBatch($urls, $maxPages);
                $this->stats['venues_found'] = count($venues);

                $this->info("Found {$this->stats['venues_found']} unique venues from batch {$batchNumber}");

                if (! empty($venues) && $saveToDb) {
                    // Apply venue limit if specified
                    if ($maxVenues > 0 && count($venues) > $maxVenues) {
                        $this->info("Limiting to {$maxVenues} venues (out of {$this->stats['venues_found']})");
                        $venues = array_slice($venues, 0, $maxVenues);
                    }

                    // Save venues to database
                    $this->info('Saving venues to database...');
                    $savedCount = $this->saveVenuesToDatabase($venues);
                    $this->stats['venues_saved'] += $savedCount;
                    $this->stats['venues_skipped'] += count($venues) - $savedCount;
                }

                $batchNumber++;

                // Break if we've reached our max endpoints limit
                if ($maxEndpoints > 0 && $this->stats['api_endpoints_processed'] >= $maxEndpoints) {
                    $this->info("Reached maximum endpoint limit ({$maxEndpoints}). Stopping processing.");
                    break;
                }
            }
        }

        if ($saveToDb) {
            // Create relationships between entities
            $this->info('Creating relationships between entities...');
            $this->createRelationships();

            // Verify saved venues in database
            $dbVenueCount = Venue::count();
            $this->info("Total venues in database: {$dbVenueCount}");
        } else {
            $this->info('Skipping database save (--no-db option)');
        }

        $executionTime = round(microtime(true) - $startTime, 2);

        // Display final statistics
        $this->displayStatistics($executionTime);

        return 0;
    }

    /**
     * Download a file from URL
     *
     * @return string|null File contents or null on failure
     */
    private function downloadFile(string $url): ?string
    {
        $maxRetries = 3;
        $retryDelay = 2;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $response = Http::timeout(60)
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36')
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            $this->warn("Attempt {$attempt} failed to download {$url} (Status: {$response->status()})");

            if ($attempt < $maxRetries) {
                $sleepTime = $retryDelay * $attempt;
                $this->info("Retrying in {$sleepTime} seconds...");
                sleep($sleepTime);
            }
        }

        $this->error("Failed to download file after {$maxRetries} attempts: {$url}");

        return null;
    }

    /**
     * Extract URLs from XML content and store them in database
     *
     * @return int Number of URLs stored
     */
    private function extractAndStoreUrlsFromXml(string $xmlContent): int
    {
        // Load XML content
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if (! $xml) {
            $this->error('Failed to parse XML content');
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                $this->error($error->message);
            }
            libxml_clear_errors();

            return 0;
        }

        // Register namespaces if present
        $namespaces = $xml->getNamespaces(true);
        if (! empty($namespaces)) {
            foreach ($namespaces as $prefix => $namespace) {
                if (empty($prefix)) {
                    $prefix = 'default';
                }
                $xml->registerXPathNamespace($prefix, $namespace);
            }
        }

        $urlsAdded = 0;
        $urlsUpdated = 0;

        // Progress tracking
        $progressBar = null;
        $totalNodes = 0;

        // First count the total nodes for progress bar
        if (isset($xml->url)) {
            $totalNodes = count($xml->url);
        } elseif (! empty($namespaces)) {
            $urlNodes = $xml->xpath('//default:url');
            $totalNodes = count($urlNodes);
        }

        if ($totalNodes > 0) {
            $progressBar = $this->output->createProgressBar($totalNodes);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Added: %message%');
            $progressBar->setMessage('0');
            $progressBar->start();
        }

        // Extract and store URLs
        // First try without namespace
        if (isset($xml->url)) {
            foreach ($xml->url as $urlNode) {
                if (isset($urlNode->loc)) {
                    $loc = (string) $urlNode->loc;
                    if (! empty($loc) && $this->isTreatmentLocationUrl($loc)) {
                        $result = $this->storeUrlInDatabase($loc);
                        if ($result === 'added') {
                            $urlsAdded++;
                        } elseif ($result === 'updated') {
                            $urlsUpdated++;
                        }
                    }
                }

                if ($progressBar) {
                    $progressBar->setMessage((string) $urlsAdded);
                    $progressBar->advance();
                }
            }
        }
        // Then try with default namespace if no URLs found
        elseif (! empty($namespaces)) {
            $urlNodes = $xml->xpath('//default:url');
            foreach ($urlNodes as $urlNode) {
                $loc = (string) $urlNode->children($namespaces['default'])->loc;
                if (! empty($loc) && $this->isTreatmentLocationUrl($loc)) {
                    $result = $this->storeUrlInDatabase($loc);
                    if ($result === 'added') {
                        $urlsAdded++;
                    } elseif ($result === 'updated') {
                        $urlsUpdated++;
                    }
                }

                if ($progressBar) {
                    $progressBar->setMessage((string) $urlsAdded);
                    $progressBar->advance();
                }
            }
        }

        if ($progressBar) {
            $progressBar->finish();
            $this->newLine();
        }

        $this->info("Added {$urlsAdded} new URLs, updated {$urlsUpdated} existing URLs");

        libxml_clear_errors();

        return $urlsAdded + $urlsUpdated;
    }

    /**
     * Store a URL in the database
     *
     * @return string 'added', 'updated', or 'skipped'
     */
    private function storeUrlInDatabase(string $url): string
    {
        // Parse URL
        $parsedUrl = parse_url($url);
        if (! isset($parsedUrl['path'])) {
            return 'skipped';
        }

        $path = $parsedUrl['path'];

        // Extract treatment, offer type, and location from path
        if (preg_match('#/salonai/procedura-([^/]+)/pasiulymo-tipas-([^/]+)/kur-([^/]+)/?#', $path, $matches)) {
            $treatmentSlug = $matches[1];
            $offerTypeSlug = $matches[2];
            $locationSlug = $matches[3];

            // Create or update the URL in database
            $sitemapUrl = SitemapUrl::updateOrCreate(
                ['original_url' => $url],
                [
                    'path' => $path,
                    'browse_uri' => $path,
                    'treatment_slug' => $treatmentSlug,
                    'treatment_name' => $this->formatName($treatmentSlug),
                    'offer_type_slug' => $offerTypeSlug,
                    'offer_type_name' => $this->formatName($offerTypeSlug),
                    'location_slug' => $locationSlug,
                    'location_name' => $this->formatName($locationSlug),
                    'downloaded_at' => now(),
                ]
            );

            return $sitemapUrl->wasRecentlyCreated ? 'added' : 'updated';
        }

        return 'skipped';
    }

    /**
     * Reset processing status of all URLs in database
     */
    private function resetUrlProcessingStatus(): void
    {
        $count = SitemapUrl::where('is_processed', true)->update([
            'is_processed' => false,
            'venues_found' => 0,
            'api_requests' => 0,
            'pages_processed' => 0,
            'last_processed_at' => null,
            'error_message' => null,
        ]);

        $this->info("Reset processing status for {$count} URLs");
    }

    /**
     * Check if a URL is a treatment location URL
     */
    private function isTreatmentLocationUrl(string $url): bool
    {
        return
            strpos($url, '/procedura-') !== false &&
            strpos($url, '/kur-') !== false &&
            strpos($url, '/pasiulymo-tipas-') !== false;
    }

    /**
     * Generate API endpoints from treatment location URLs
     *
     * @return array Array of API endpoints
     */
    private function generateApiEndpoints(array $urls): array
    {
        $endpoints = [];
        $processedPaths = [];

        $progBar = $this->output->createProgressBar(count($urls));
        $progBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Venues: %message%');
        $progBar->setMessage('0');
        $progBar->start();

        foreach ($urls as $url) {
            // Parse URL
            $parsedUrl = parse_url($url);
            if (! isset($parsedUrl['path'])) {
                $progBar->advance();

                continue;
            }

            $path = $parsedUrl['path'];

            // Skip duplicate paths
            if (isset($processedPaths[$path])) {
                $progBar->advance();

                continue;
            }

            $processedPaths[$path] = true;

            // Extract treatment, offer type, and location from path
            if (preg_match('#/salonai/procedura-([^/]+)/pasiulymo-tipas-([^/]+)/kur-([^/]+)/?#', $path, $matches)) {
                $endpoint = [
                    'original_url' => $url,
                    'path' => $path,
                    'browse_uri' => $path,
                    'treatment' => $matches[1],
                    'offer_type' => $matches[2],
                    'location' => $matches[3],
                    'location_name' => $this->formatName($matches[3]),
                    'treatment_name' => $this->formatName($matches[1]),
                    'offer_type_name' => $this->formatName($matches[2]),
                ];

                $endpoints[] = $endpoint;
            }

            $progBar->advance();
        }

        $progBar->finish();
        $this->newLine();

        return $endpoints;
    }

    /**
     * Format slug into readable name
     */
    private function formatName(string $slug): string
    {
        // Remove common suffixes
        $slug = str_replace(['-lt', '-lietuva'], '', $slug);

        // Replace dashes with spaces and capitalize
        return ucwords(str_replace('-', ' ', $slug));
    }

    /**
     * Fetch venues from API
     *
     * @param  array  $endpoints  Array of API endpoints
     * @param  int  $maxPages  Maximum pages to fetch per endpoint (0 = all)
     * @return array Array of venues
     */
    private function fetchVenuesFromApi(array $endpoints, int $maxPages = 0): array
    {
        $venuesMap = [];

        $progBar = $this->output->createProgressBar(count($endpoints));
        $progBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Venues: %message%');
        $progBar->setMessage('0');
        $progBar->start();

        foreach ($endpoints as $endpointIndex => $endpoint) {
            $browseUri = $endpoint['browse_uri'];

            // Validate endpoint
            if (empty($browseUri)) {
                $progBar->advance();

                continue;
            }

            // Start with page 0
            $page = 0;
            $totalPages = 1; // Default assumption
            $hasMorePages = true;

            // Process each page for this endpoint
            while ($hasMorePages) {
                // Stop if we hit the max pages limit
                if ($maxPages > 0 && $page >= $maxPages) {
                    break;
                }

                // Build API URL
                $apiUrl = "{$this->apiBaseUrl}?page={$page}&currentBrowseUri=".urlencode($browseUri);
                $this->totalApiRequests++;

                // Make API request
                $response = $this->makeApiRequest($apiUrl);

                if ($response === null) {
                    $this->stats['api_requests_failed']++;

                    // If first page fails, skip this endpoint
                    if ($page === 0) {
                        break;
                    }

                    // For subsequent pages, just stop processing this endpoint
                    break;
                }

                $this->stats['api_requests_successful']++;
                $this->totalPagesProcessed++;

                // Validate response structure
                if (! is_array($response)) {
                    break;
                }

                // Get pagination information
                if (isset($response['pagination']) && is_array($response['pagination'])) {
                    $pagination = $response['pagination'];
                    $totalPages = isset($pagination['totalPages']) && is_numeric($pagination['totalPages'])
                        ? (int) $pagination['totalPages']
                        : 1;
                    $currentPage = isset($pagination['currentPage']) && is_numeric($pagination['currentPage'])
                        ? (int) $pagination['currentPage']
                        : (isset($pagination['page']) && is_numeric($pagination['page'])
                            ? (int) $pagination['page']
                            : 0);

                    // Check if we've reached the last page
                    $hasMorePages = ($currentPage < $totalPages - 1);
                } else {
                    // No pagination info, assume no more pages
                    $hasMorePages = false;
                }

                // Process venues from the response
                $this->processVenuesFromResponse($response, $endpoint, $venuesMap);
                $progBar->setMessage((string) count($venuesMap));

                $page++;

                // Add a delay between requests to avoid rate limiting
                usleep(300000); // 300ms delay
            }

            $this->stats['api_endpoints_processed']++;
            $progBar->advance();

            // Show stats periodically
            if ($endpointIndex > 0 && $endpointIndex % 20 === 0) {
                $foundVenues = count($venuesMap);
                $this->newLine(2);
                $this->info("Progress: {$this->stats['api_endpoints_processed']}/{$this->stats['api_endpoints_total']} endpoints, {$foundVenues} venues found");
                $this->newLine();
            }
        }

        $progBar->finish();
        $this->newLine();

        // Convert from associative to indexed array
        return array_values($venuesMap);
    }

    /**
     * Make API request with retry logic
     *
     * @return array|null Response data or null on failure
     */
    private function makeApiRequest(string $url): ?array
    {
        $maxRetries = 3;
        $retryDelay = 2;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            // Validate URL
            if (empty($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
                if ($this->debug) {
                    $this->error("Invalid API URL: {$url}");
                }

                return null;
            }

            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    ])
                    ->get($url);

                // Handle rate limiting
                if ($response->status() === 429) {
                    $sleepTime = 5 * $attempt;
                    $this->warn("Rate limited. Waiting {$sleepTime} seconds before retry.");
                    sleep($sleepTime);

                    continue;
                }

                // Handle successful response
                if ($response->successful()) {
                    // Get the response body
                    $body = $response->body();

                    // Check for XSSI protection prefix and remove it if present
                    $xssiPrefix = ")]}', ";
                    if (strpos($body, $xssiPrefix) === 0) {
                        $body = substr($body, strlen($xssiPrefix));
                    }

                    // Decode JSON
                    $data = json_decode($body, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        if ($this->debug) {
                            $this->warn('JSON decode error: '.json_last_error_msg());
                        }

                        if ($attempt < $maxRetries) {
                            $sleepTime = $retryDelay * $attempt;
                            sleep($sleepTime);
                        }

                        continue;
                    }

                    // Validate response is an array
                    if (! is_array($data)) {
                        if ($attempt < $maxRetries) {
                            $sleepTime = $retryDelay * $attempt;
                            sleep($sleepTime);
                        }

                        continue;
                    }

                    return $data;
                }

                // Handle error response
                if ($attempt < $maxRetries) {
                    $sleepTime = $retryDelay * $attempt;
                    sleep($sleepTime);
                }

            } catch (\Exception $e) {
                if ($this->debug) {
                    $this->error('API request exception: '.$e->getMessage());
                }

                if ($attempt < $maxRetries) {
                    $sleepTime = $retryDelay * $attempt;
                    sleep($sleepTime);
                }
            }
        }

        return null;
    }

    /**
     * Process venues from API response
     *
     * @param  SitemapUrl|array  $url
     * @param  array  &$venuesMap  Reference to venues map
     */
    private function processVenuesFromResponse(array $response, $url, array &$venuesMap): void
    {
        // Validate response format
        if (! isset($response['results']) || ! is_array($response['results'])) {
            return;
        }

        foreach ($response['results'] as $result) {
            // Check if this is a venue result
            if (! isset($result['type']) || $result['type'] !== 'venue' || ! isset($result['data'])) {
                continue;
            }

            $venue = $result['data'];

            // Skip invalid venues
            if (! isset($venue['id']) || ! isset($venue['name'])) {
                continue;
            }

            $venueId = $venue['id'];
            $venueName = trim($venue['name']);

            // Skip venues with test/demo names
            if ($this->isTestVenue($venueName)) {
                continue;
            }

            // Extract URL from response
            $venueUrl = $this->extractVenueUrl($venue, $venueId, $venueName);

            // Extract location data
            $cityName = is_object($url) && isset($url->location_name) ? $url->location_name : 'Unknown';
            $address = '';
            $postalCode = '';
            $latitude = null;
            $longitude = null;
            $countryId = null;

            if (isset($venue['location'])) {
                $location = $venue['location'];

                // Extract address
                if (isset($location['address'])) {
                    $addressData = $location['address'];
                    $postalCode = $addressData['postalCode'] ?? '';
                    $countryId = $addressData['countryId'] ?? null;

                    if (isset($addressData['addressLines']) && ! empty($addressData['addressLines'])) {
                        $address = $addressData['addressLines'][0];
                    }
                }

                // Extract coordinates
                if (isset($location['coordinates'])) {
                    $coordinates = $location['coordinates'];
                    $latitude = $coordinates['lat'] ?? null;
                    $longitude = $coordinates['lng'] ?? null;
                } elseif (isset($location['point'])) {
                    $coordinates = $location['point'];
                    $latitude = $coordinates['lat'] ?? null;
                    $longitude = $coordinates['lon'] ?? null;
                }
            }

            // Extract rating data
            $rating = null;
            $ratingCount = null;
            $ratingAverage = null;

            if (isset($venue['rating'])) {
                $ratingData = $venue['rating'];
                $ratingAverage = $ratingData['average'] ?? $ratingData['weightedAverage'] ?? null;
                $ratingCount = $ratingData['count'] ?? null;

                $rating = [
                    'average' => $ratingAverage,
                    'count' => $ratingCount,
                ];
            }

            // Extract primary image
            $primaryImage = null;

            if (isset($venue['primaryImage']) && isset($venue['primaryImage']['uris'])) {
                $largestImage = null;
                $largestSize = 0;

                // Find the largest image
                foreach ($venue['primaryImage']['uris'] as $size => $uri) {
                    if (strpos($size, 'x') !== false) {
                        [$width, $height] = explode('x', $size);
                        $totalSize = (int) $width * (int) $height;
                        if ($totalSize > $largestSize) {
                            $largestSize = $totalSize;
                            $largestImage = $uri;
                        }
                    }
                }

                if ($largestImage) {
                    $primaryImage = $largestImage;
                }
            }

            // Setup venue data (if new) or merge treatments (if existing)
            if (! isset($venuesMap[$venueId])) {
                // New venue record
                $venuesMap[$venueId] = [
                    'id' => $venueId,
                    'name' => $venueName,
                    'url' => $venueUrl,
                    'city' => $cityName,
                    'address' => $address,
                    'postal_code' => $postalCode,
                    'country_id' => $countryId,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'description' => $venue['description'] ?? null,
                    'primary_image' => $primaryImage,
                    'rating' => $rating,
                    'treatments' => [],
                ];
            }

            // Add treatment from URL if it exists
            if (is_object($url) && ! empty($url->treatment_name) && $url->treatment_name !== 'Unknown') {
                $treatmentName = $url->treatment_name;
                $treatmentExists = false;

                // Check if this treatment already exists
                foreach ($venuesMap[$venueId]['treatments'] as $existingTreatment) {
                    if ($existingTreatment['name'] === $treatmentName) {
                        $treatmentExists = true;
                        break;
                    }
                }

                // Add new treatment
                if (! $treatmentExists) {
                    $venuesMap[$venueId]['treatments'][] = [
                        'name' => $treatmentName,
                    ];
                }
            }
        }

    }

    /**
     * Extract venue URL from response data
     */
    private function extractVenueUrl(array $venue, string $venueId, string $venueName): string
    {
        // Check various possible URL locations in the API response
        if (isset($venue['seoUrl'])) {
            return "https://www.treatwell.lt{$venue['seoUrl']}";
        }

        if (isset($venue['url'])) {
            return "https://www.treatwell.lt{$venue['url']}";
        }

        if (isset($venue['links']) && isset($venue['links']['self'])) {
            return "https://www.treatwell.lt{$venue['links']['self']}";
        }

        // Generate a URL based on name as fallback
        $slug = Str::slug($venueName);

        return "https://www.treatwell.lt/vieta/{$slug}-{$venueId}/";
    }

    /**
     * Check if venue name indicates a test/demo venue
     */
    private function isTestVenue(string $name): bool
    {
        $nameLower = Str::lower($name);

        $prefixes = ['test', 'demo', 'example', 'sample', 'training', 'temporary'];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($nameLower, $prefix)) {
                return true;
            }
        }

        $phrases = ['do not book', 'do not use', 'neveikia', 'netikras'];

        foreach ($phrases as $phrase) {
            if (str_contains($nameLower, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Save venues to database
     *
     * @return int Number of venues saved
     */
    private function saveVenuesToDatabase(array $venues): int
    {
        $savedCount = 0;

        $progBar = $this->output->createProgressBar(count($venues));
        $progBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Saved: %message%');
        $progBar->setMessage('0');
        $progBar->start();

        foreach ($venues as $venueData) {
            try {
                // Check required fields
                if (empty($venueData['name']) || empty($venueData['url'])) {
                    $progBar->advance();

                    continue;
                }

                // Normalize data
                $venueName = trim($venueData['name']);
                $venueCity = trim($venueData['city'] ?? 'Unknown');
                $venueUrl = trim($venueData['url']);

                // Generate a slug from the URL
                $slug = '';
                if (preg_match('/\/vieta\/([^\/]+)/', $venueUrl, $matches)) {
                    $slug = $matches[1];
                } elseif (preg_match('/\/salonas\/([^\/]+)/', $venueUrl, $matches)) {
                    $slug = $matches[1];
                } else {
                    // Create a slug from the name if not found in URL
                    $slug = Str::slug($venueName);
                    if (! empty($venueData['id'])) {
                        $slug .= '-'.$venueData['id'];
                    }
                }

                // Begin transaction for atomic operations
                DB::beginTransaction();

                // Prepare venue data for saving
                $venueModelData = [
                    'name' => $venueName,
                    'slug' => $slug,
                    'url' => $venueUrl,
                    'source' => 'treatwell_api',
                    'external_id' => $venueData['id'] ?? null,
                    'description' => $venueData['description'] ?? null,
                    'raw_data' => json_encode($venueData, JSON_UNESCAPED_UNICODE),
                ];

                // Check if venue exists before attempting to create
                $venue = Venue::where('slug', $slug)->first();

                if (! $venue) {
                    // Create new venue
                    $venue = new Venue($venueModelData);
                    $venue->save();
                } else {
                    // Update existing venue
                    $venue->fill($venueModelData);
                    $venue->save();
                }

                // Find or create the city
                $citySlug = Str::slug($venueCity);

                // Create city if it doesn't exist
                $cityModel = City::firstOrCreate(
                    ['slug' => $citySlug],
                    ['name' => $venueCity]
                );

                // Link city to venue using direct query to avoid relationships
                $existingCityVenue = DB::table('city_venue')
                    ->where('city_id', $cityModel->id)
                    ->where('venue_id', $venue->id)
                    ->exists();

                if (! $existingCityVenue) {
                    DB::table('city_venue')->insert([
                        'city_id' => $cityModel->id,
                        'venue_id' => $venue->id,
                    ]);
                    $this->stats['city_venue_links']++;
                }

                // Process location data
                if (! empty($venueData['address'])) {
                    // Check if location exists
                    $location = DB::table('locations')
                        ->where('venue_id', $venue->id)
                        ->first();

                    $locationData = [
                        'venue_id' => $venue->id,
                        'city_id' => $cityModel->id,
                        'address_line1' => $venueData['address'],
                        'postal_code' => $venueData['postal_code'] ?? null,
                        'latitude' => $venueData['latitude'] ?? null,
                        'longitude' => $venueData['longitude'] ?? null,
                    ];

                    if (! $location) {
                        // Insert new location
                        $locationData['created_at'] = now();
                        $locationData['updated_at'] = now();
                        DB::table('locations')->insert($locationData);
                    } else {
                        // Update existing location
                        $locationData['updated_at'] = now();
                        DB::table('locations')
                            ->where('venue_id', $venue->id)
                            ->update($locationData);
                    }
                }

                // Process treatments
                if (! empty($venueData['treatments'])) {
                    foreach ($venueData['treatments'] as $treatmentData) {
                        $treatmentName = trim($treatmentData['name']);

                        // Skip empty treatment names
                        if (empty($treatmentName)) {
                            continue;
                        }

                        // Create or find the procedure
                        $treatmentSlug = Str::slug($treatmentName);
                        $procedure = Procedure::firstOrCreate(
                            ['slug' => $treatmentSlug],
                            ['name' => $treatmentName]
                        );

                        // Link procedure to venue
                        $existingProcedureVenue = DB::table('procedure_venue')
                            ->where('procedure_id', $procedure->id)
                            ->where('venue_id', $venue->id)
                            ->exists();

                        if (! $existingProcedureVenue) {
                            DB::table('procedure_venue')->insert([
                                'procedure_id' => $procedure->id,
                                'venue_id' => $venue->id,
                            ]);
                            $this->stats['procedure_venue_links']++;
                        }

                        // Create treatment for venue
                        $existingTreatment = DB::table('treatments')
                            ->where('venue_id', $venue->id)
                            ->where('name', $treatmentName)
                            ->first();

                        $treatmentModelData = [
                            'venue_id' => $venue->id,
                            'name' => $treatmentName,
                            'min_price' => $treatmentData['min_price'] ?? null,
                            'max_price' => $treatmentData['max_price'] ?? null,
                            'min_duration' => $treatmentData['min_duration'] ?? null,
                            'max_duration' => $treatmentData['max_duration'] ?? null,
                        ];

                        if (! $existingTreatment) {
                            // Insert new treatment
                            $treatmentModelData['created_at'] = now();
                            $treatmentModelData['updated_at'] = now();
                            DB::table('treatments')->insert($treatmentModelData);
                        } else {
                            // Update existing treatment
                            $treatmentModelData['updated_at'] = now();
                            DB::table('treatments')
                                ->where('venue_id', $venue->id)
                                ->where('name', $treatmentName)
                                ->update($treatmentModelData);
                        }
                    }
                }

                // Commit transaction
                DB::commit();

                $savedCount++;
                $progBar->setMessage((string) $savedCount);
            } catch (\Exception $e) {
                // Rollback transaction on error
                DB::rollBack();

                if ($this->debug) {
                    $this->error('Error saving venue: '.$e->getMessage());
                }
            }

            $progBar->advance();
        }

        $progBar->finish();
        $this->newLine();

        return $savedCount;
    }

    /**
     * Process a single specific URL
     */
    private function processSpecificUrl(string $url): int
    {
        $this->info("Processing specific URL: {$url}");

        // If this is not an API URL, convert it
        if (strpos($url, 'api/v1/page/browse') === false) {
            $url = $this->convertToApiUrl($url);
            $this->info("Converted to API URL: {$url}");
        }

        // Create a simple endpoint with just the URL
        $endpoints = [[
            'browse_uri' => $this->extractBrowseUriFromUrl($url),
            'original_url' => $url,
        ]];

        // Fetch data from API
        $venues = $this->fetchVenuesFromApi($endpoints);

        if (empty($venues)) {
            $this->error('No venues found at the specified URL');

            return 1;
        }

        $this->info('Found '.count($venues).' venues');

        // Save venues to database if not disabled
        if (! $this->option('no-db')) {
            $savedCount = $this->saveVenuesToDatabase($venues);
            $this->info("Saved {$savedCount} venues to database");
        } else {
            $this->info('Skipped saving to database (--no-db option)');
        }

        return 0;
    }

    /**
     * Convert a web URL to an API URL
     */
    private function convertToApiUrl(string $url): string
    {
        // Extract the path from the URL
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';

        // Return the API URL with path as browse URI parameter
        return $this->apiBaseUrl.'?page=0&currentBrowseUri='.urlencode($path);
    }

    /**
     * Extract browse URI from URL
     */
    private function extractBrowseUriFromUrl(string $url): string
    {
        $params = [];
        parse_str(parse_url($url, PHP_URL_QUERY), $params);

        return isset($params['currentBrowseUri'])
            ? urldecode($params['currentBrowseUri'])
            : parse_url($url, PHP_URL_PATH) ?? '/';
    }

    /**
     * Validate that all required database tables and columns exist
     *
     * @return bool True if valid, false otherwise
     */
    private function validateSchema(): bool
    {
        // Required tables
        $requiredTables = [
            'venues', 'cities', 'locations',
            'treatments', 'city_venue', 'city_treatment',
            'treatment_venue', 'opening_hours', 'images',
            'ratings', 'procedures', 'city_procedure',
        ];

        $missingTables = [];

        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                $missingTables[] = $table;
            }
        }

        if (count($missingTables) > 0) {
            $this->error('Schema validation failed. Missing tables: '.implode(', ', $missingTables));

            return false;
        }

        // Check if cities table has required columns
        if (! Schema::hasColumn('cities', 'name') ||
            ! Schema::hasColumn('cities', 'slug')) {
            $this->error('Schema validation failed. Cities table is missing required columns.');

            return false;
        }

        // Check if venues table has required columns
        if (! Schema::hasColumn('venues', 'name') ||
            ! Schema::hasColumn('venues', 'slug')) {
            $this->error('Schema validation failed. Venues table is missing required columns.');

            return false;
        }

        // Check if treatments table has required columns
        if (! Schema::hasColumn('treatments', 'name') ||
            ! Schema::hasColumn('treatments', 'slug')) {
            $this->error('Schema validation failed. Treatments table is missing required columns.');

            return false;
        }

        return true;
    }

    /**
     * Link procedures to cities based on stored sitemap URLs
     */
    private function linkProceduresToCities(): void
    {
        // Ensure city_procedure table exists
        if (! Schema::hasTable('city_procedure')) {
            $this->info('Creating city_procedure table...');
            Schema::create('city_procedure', function ($table) {
                $table->unsignedBigInteger('city_id');
                $table->unsignedBigInteger('procedure_id');
                $table->primary(['city_id', 'procedure_id']);

                $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
                $table->foreign('procedure_id')->references('id')->on('procedures')->onDelete('cascade');
            });
            $this->info('city_procedure table created successfully');
        }

        // First, get all unique city and procedure slugs from the database
        $this->info('Pre-creating cities and procedures from URLs...');

        // Get unique city slugs
        $citySlugs = SitemapUrl::valid()
            ->select('location_slug', 'location_name')
            ->distinct()
            ->get()
            ->pluck('location_name', 'location_slug')
            ->toArray();

        // Create cities
        $createdCities = 0;
        foreach ($citySlugs as $slug => $name) {
            // Skip empty slugs or names
            if (empty($slug) || empty($name)) {
                continue;
            }

            $city = City::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'is_main_city' => true,
                ]
            );

            if ($city->wasRecentlyCreated) {
                $createdCities++;
            }
        }
        $this->info("Created {$createdCities} cities from URL slugs");

        // Get unique procedure slugs
        $procedureSlugs = SitemapUrl::valid()
            ->select('treatment_slug', 'treatment_name')
            ->distinct()
            ->get()
            ->pluck('treatment_name', 'treatment_slug')
            ->toArray();

        // Create procedures
        $createdProcedures = 0;
        foreach ($procedureSlugs as $slug => $name) {
            // Skip empty slugs or names
            if (empty($slug) || empty($name)) {
                continue;
            }

            $procedure = Procedure::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );

            if ($procedure->wasRecentlyCreated) {
                $createdProcedures++;
            }
        }
        $this->info("Created {$createdProcedures} procedures from URL slugs");

        // Now link procedures to cities
        $this->info('Linking procedures to cities...');

        // Get all URLs with valid city and procedure information
        $urls = SitemapUrl::valid()
            ->whereNotNull('location_slug')
            ->whereNotNull('treatment_slug')
            ->get();

        $progressBar = $this->output->createProgressBar(count($urls));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Links: %message%');
        $progressBar->setMessage('0');
        $progressBar->start();

        $cityProcedureLinksCount = 0;

        foreach ($urls as $url) {
            $citySlug = $url->location_slug;
            $procedureSlug = $url->treatment_slug;

            if (! empty($citySlug) && ! empty($procedureSlug)) {
                $city = City::where('slug', $citySlug)->first();
                $procedure = Procedure::where('slug', $procedureSlug)->first();

                if ($city && $procedure) {
                    // Check if the relationship already exists
                    $exists = DB::table('city_procedure')
                        ->where('city_id', $city->id)
                        ->where('procedure_id', $procedure->id)
                        ->exists();

                    if (! $exists) {
                        DB::table('city_procedure')->insert([
                            'city_id' => $city->id,
                            'procedure_id' => $procedure->id,
                        ]);
                        $cityProcedureLinksCount++;
                        $progressBar->setMessage((string) $cityProcedureLinksCount);
                    }
                }
            }

            $progressBar->advance();
        }

        $this->stats['city_procedure_links'] = $cityProcedureLinksCount;

        $progressBar->finish();
        $this->newLine();
        $this->info("Procedure-city linking completed: {$this->stats['city_procedure_links']} links created");
    }

    /**
     * Clear existing venues and related data from the database
     */
    private function clearExistingData(): void
    {
        $this->info('Clearing existing data...');

        // Get database connection type
        $connection = DB::connection()->getDriverName();

        // Disable foreign key checks temporarily
        if ($connection === 'mysql' || $connection === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($connection === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        // Clear all tables in reverse dependency order
        // Use delete instead of truncate for SQLite compatibility
        if ($connection === 'sqlite') {
            DB::table('ratings')->delete();
            DB::table('opening_hours')->delete();
            DB::table('images')->delete();
            DB::table('city_treatment')->delete();
            DB::table('city_venue')->delete();
            DB::table('treatment_venue')->delete();
            DB::table('city_procedure')->delete();
            DB::table('treatments')->delete();
            DB::table('locations')->delete();
            DB::table('venues')->delete();
            DB::table('cities')->delete();
            DB::table('procedures')->delete();
        } else {
            // Use truncate for other database types
            Rating::truncate();
            OpeningHour::truncate();
            Image::truncate();
            DB::table('city_treatment')->truncate();
            DB::table('city_venue')->truncate();
            DB::table('treatment_venue')->truncate();
            DB::table('city_procedure')->truncate();
            Treatment::truncate();
            Location::truncate();
            Venue::truncate();
            City::truncate();
            Procedure::truncate();
        }

        // Re-enable foreign key checks
        if ($connection === 'mysql' || $connection === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } elseif ($connection === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        $this->info('All existing data has been cleared.');
    }

    /**
     * Display final statistics
     */
    private function displayStatistics(float $executionTime): void
    {
        $this->info('');
        $this->info('===== Processing Statistics =====');

        // XML and API stats
        $this->info("XML URLs processed: {$this->stats['xml_urls_total']}");
        $this->info("API endpoints processed: {$this->stats['api_endpoints_processed']}");
        $this->info("API requests: {$this->stats['api_requests_total']} (Success: {$this->stats['api_requests_successful']}, Failed: {$this->stats['api_requests_failed']})");
        $this->info("Venues from API: {$this->stats['venues_saved']} (Found: {$this->stats['venues_found']}, Skipped: {$this->stats['venues_skipped']})");

        // JSON processing stats
        $this->info("JSON files: {$this->stats['json_files_total']} (Processed: {$this->stats['json_files_processed']}, Failed: {$this->stats['json_files_failed']})");
        $this->info("Venues from JSON: {$this->stats['json_venues_saved']} (Found: {$this->stats['json_venues_found']})");
        $this->info("Cities saved: {$this->stats['cities_saved']}");
        $this->info("Locations saved: {$this->stats['locations_saved']}");
        $this->info("Treatments saved: {$this->stats['treatments_saved']}");
        $this->info("Procedures saved: {$this->stats['procedures_saved']}");
        $this->info("Images saved: {$this->stats['images_saved']}");
        $this->info("Opening hours saved: {$this->stats['opening_hours_saved']}");
        $this->info("Ratings saved: {$this->stats['ratings_saved']}");

        // Relationship stats
        $this->info("City-venue links: {$this->stats['city_venue_links']}");
        $this->info("Procedure-venue links: {$this->stats['procedure_venue_links']}");
        $this->info("City-procedure links: {$this->stats['city_procedure_links']}");
        $this->info("Additional relationships created: {$this->stats['relationships_created']}");

        // Final summary
        $this->info("Execution time: {$executionTime} seconds");
        $this->info('Peak memory usage: '.$this->formatBytes(memory_get_peak_usage(true)));
        $this->info('=================================');
    }

    /**
     * Process a batch of URLs
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $urls
     */
    private function processUrlBatch($urls, int $maxPages): array
    {
        $venuesMap = [];

        $progBar = $this->output->createProgressBar(count($urls));
        $progBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Venues: %message%');
        $progBar->setMessage('0');
        $progBar->start();

        foreach ($urls as $url) {
            // Set tracking variables for this URL
            $urlApiRequests = 0;
            $urlPagesProcessed = 0;
            $urlVenuesFound = 0;
            $errorMessage = null;

            try {
                // Start with page 0
                $page = 0;
                $totalPages = 1; // Default assumption
                $hasMorePages = true;

                // Process each page for this URL
                while ($hasMorePages) {
                    // Stop if we hit the max pages limit
                    if ($maxPages > 0 && $page >= $maxPages) {
                        break;
                    }

                    // Build API URL
                    $apiUrl = $url->getApiEndpoint($page);
                    $this->totalApiRequests++;
                    $urlApiRequests++;

                    // Make API request
                    $response = $this->makeApiRequest($apiUrl);

                    if ($response === null) {
                        $this->stats['api_requests_failed']++;

                        // If first page fails, skip this URL
                        if ($page === 0) {
                            $errorMessage = 'Failed to get response from API for first page';
                            break;
                        }

                        // For subsequent pages, just stop processing this URL
                        break;
                    }

                    $this->stats['api_requests_successful']++;
                    $this->totalPagesProcessed++;
                    $urlPagesProcessed++;

                    // Store the API response in the database if in debug mode
                    if ($this->debug && $page === 0) {
                        $url->api_response = json_encode($response, JSON_UNESCAPED_UNICODE);
                        $url->save();
                    }

                    // Validate response structure
                    if (! is_array($response)) {
                        $errorMessage = 'Invalid API response structure';
                        break;
                    }

                    // Get pagination information
                    if (isset($response['pagination']) && is_array($response['pagination'])) {
                        $pagination = $response['pagination'];
                        $totalPages = isset($pagination['totalPages']) && is_numeric($pagination['totalPages'])
                            ? (int) $pagination['totalPages']
                            : 1;
                        $currentPage = isset($pagination['currentPage']) && is_numeric($pagination['currentPage'])
                            ? (int) $pagination['currentPage']
                            : (isset($pagination['page']) && is_numeric($pagination['page'])
                                ? (int) $pagination['page']
                                : 0);

                        // Check if we've reached the last page
                        $hasMorePages = ($currentPage < $totalPages - 1);
                    } else {
                        // No pagination info, assume no more pages
                        $hasMorePages = false;
                    }

                    // Process venues from the response
                    $initialVenueCount = count($venuesMap);
                    $this->processVenuesFromResponse($response, $url, $venuesMap);
                    $urlVenuesFound += (count($venuesMap) - $initialVenueCount);
                    $progBar->setMessage((string) count($venuesMap));

                    $page++;

                    // Add a delay between requests to avoid rate limiting
                    usleep(300000); // 300ms delay
                }

                // Update URL with processing results
                $url->markAsProcessed($urlVenuesFound, $urlApiRequests, $urlPagesProcessed);

            } catch (\Exception $e) {
                // Mark URL as invalid if an exception occurred
                $errorMessage = $e->getMessage();
                $url->markAsInvalid($errorMessage);

                if ($this->debug) {
                    $this->error("Error processing URL {$url->original_url}: {$errorMessage}");
                }
            }

            $this->stats['api_endpoints_processed']++;
            $progBar->advance();
        }

        $progBar->finish();
        $this->newLine();

        // Convert from associative to indexed array
        return array_values($venuesMap);
    }

    /**
     * Process all JSON files in the storage/app/xml directory
     *
     * @param  int  $batchSize  Number of files to process in each batch
     * @param  int  $maxFiles  Maximum number of files to process (0 = all)
     */
    private function processJsonFiles(int $batchSize = 20, int $maxFiles = 0): void
    {
        // Get all JSON files from storage/app/xml directory
        $jsonFiles = File::glob(storage_path('app/xml/api_response_*.json'));

        if (empty($jsonFiles)) {
            $jsonFiles = [];
            $storageFiles = collect(
                array_merge(
                    Storage::disk('local')->files('app/xml'),
                    Storage::disk('local')->files('xml')
                )
            )
                ->filter(fn ($path) => Str::contains(basename($path), 'api_response_') && Str::endsWith($path, '.json'))
                ->map(fn ($path) => Storage::disk('local')->path($path))
                ->all();

            if (! empty($storageFiles)) {
                $jsonFiles = $storageFiles;
            }
        }

        $jsonFiles = array_values(array_unique($jsonFiles));
        $totalFiles = count($jsonFiles);

        if ($totalFiles === 0) {
            $this->error('No JSON files found in storage/app/xml directory.');

            return;
        }

        $this->stats['json_files_total'] = $totalFiles;
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

            $this->info('Processing batch '.($batchNum + 1)."/{$totalBatches} (".count($batchFiles).' files)...');

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

                $this->info('Batch '.($batchNum + 1).' completed successfully.');
                $this->info('Memory usage: '.$this->formatBytes(memory_get_usage(true)));

                // Clean memory between batches
                $this->uniqueJsonVenues = [];
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
                $this->error('Error in batch '.($batchNum + 1).': '.$e->getMessage());
                Log::error('JSON processing error: '.$e->getMessage(), [
                    'batch' => $batchNum + 1,
                    'exception' => $e,
                ]);
                $this->stats['json_files_failed'] += count($batchFiles);
            }
        }
    }

    /**
     * Process a single JSON file
     *
     * @param  string  $filePath  Path to the JSON file
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

            if (! $data || json_last_error() !== JSON_ERROR_NONE) {
                $this->stats['json_files_skipped']++;
                Log::warning("Failed to parse JSON file: {$filePath}. Error: ".json_last_error_msg());

                return;
            }

            // Process the JSON data
            if (isset($data['results']) && is_array($data['results'])) {
                foreach ($data['results'] as $result) {
                    if ($result['type'] === 'venue' && isset($result['data'])) {
                        $this->processJsonVenue($result['data']);
                    }
                }
            } elseif (isset($data['venues'])) {
                // Process venues
                foreach ($data['venues'] as $venueData) {
                    $this->processJsonVenue($venueData);
                }
            } elseif (isset($data['businesses'])) {
                // Alternative format
                foreach ($data['businesses'] as $venueData) {
                    $this->processJsonVenue($venueData);
                }
            }

            $this->stats['json_files_processed']++;
        } catch (\Exception $e) {
            $this->stats['json_files_failed']++;
            Log::error("Error processing file {$filePath}: ".$e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * Process venue data from JSON and save to database
     *
     * @param  array  $venueData  Venue data from JSON
     * @return void
     */
    private function processJsonVenue(array $venueData)
    {
        try {
            // Extract venue ID or generate one if not present
            $venueId = isset($venueData['id'])
                ? (string) $venueData['id']
                : (isset($venueData['venueId'])
                    ? (string) $venueData['venueId']
                    : (isset($venueData['businessId'])
                        ? (string) $venueData['businessId']
                        : (string) Str::uuid()));

            // Skip if we've already processed this venue
            if (isset($this->uniqueJsonVenues[$venueId])) {
                return;
            }

            $this->stats['json_venues_found']++;
            $this->uniqueJsonVenues[$venueId] = true;

            // Extract venue data
            $venueName = $venueData['name'] ?? ($venueData['venueName'] ?? ($venueData['businessName'] ?? 'Unknown'));
            $venueDescription = $venueData['description'] ?? ($venueData['venueDescription'] ?? '');
            $venueSlug = $venueData['slug'] ?? ($venueData['venueSlug'] ?? Str::slug($venueName));
            $venueUrl = $venueData['url'] ?? ($venueData['venueUrl'] ?? ($venueData['businessUrl'] ?? ''));

            // Create or find venue using external identifier
            $venue = Venue::firstOrNew(['external_id' => $venueId]);

            // Set venue properties
            $venue->name = $venueName;
            $venue->description = $venueDescription;
            $venue->slug = $venueSlug;
            $venue->url = $venueUrl;
            $venue->external_id = $venueId;

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

            // Make sure we save the venue regardless of whether it's new or existing
            $venue->save();

            $this->stats['json_venues_saved']++;
            $this->debug("Saved venue: {$venueName} (ID: {$venueId})");

            // Process location if present
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
            }

            if (isset($venueData['primaryImage'])) {
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
        } catch (\Exception $e) {
            $this->error('Error processing venue: '.$e->getMessage());
            Log::error('Venue processing error: '.$e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * Add a debug message if in debug mode
     */
    private function debug(string $message): void
    {
        if ($this->debug) {
            $this->info('[DEBUG] '.$message);
        }
    }

    /**
     * Process location data
     *
     * @param  array  $locationData  Location data
     * @param  Venue  $venue  Related venue
     * @return void
     */
    private function processLocation(array $locationData, Venue $venue)
    {
        $locationExternalId = isset($locationData['id']) ? (string) $locationData['id'] : null;
        $locationName = $locationData['name'] ?? ($locationData['cityName'] ?? 'Unknown');

        $locationCacheKey = $locationExternalId
            ? $locationExternalId
            : $venue->id.'|'.Str::slug($locationName ?: 'unknown');

        if (isset($this->uniqueLocations[$locationCacheKey])) {
            $location = Location::find($this->uniqueLocations[$locationCacheKey]);
        }

        if (! isset($location) || ! $location) {
            $lookup = $locationExternalId
                ? ['external_id' => $locationExternalId]
                : ['venue_id' => $venue->id, 'name' => $locationName];

            $location = Location::firstOrNew($lookup);
            $location->venue_id = $venue->id;
            $location->external_id = $locationExternalId;
            $location->name = $locationName;

            // Handle address fields - may be in different formats
            if (isset($locationData['address']['addressLines']) && is_array($locationData['address']['addressLines'])) {
                // Address comes as an array of lines
                $address = implode(', ', $locationData['address']['addressLines']);

                // Try to split into address_line1 and address_line2
                $addressLines = $locationData['address']['addressLines'];
                if (count($addressLines) > 0) {
                    $location->address_line1 = $addressLines[0];
                    if (count($addressLines) > 1) {
                        $location->address_line2 = $addressLines[1];
                    }
                }

                // Also save full address to the address field
                $location->address = $address;
            } elseif (is_string($locationData['address'] ?? null)) {
                // Address comes as a single string
                $location->address = $locationData['address'];
                $location->address_line1 = $locationData['address'];
            }

            $location->postal_code = $locationData['postalCode']
                ?? ($locationData['zipCode'] ?? ($locationData['address']['postalCode'] ?? ''));

            if (isset($locationData['point'])) {
                $location->latitude = $locationData['point']['lat'] ?? 0;
                $location->longitude = $locationData['point']['lon'] ?? 0;
            } elseif (isset($locationData['coordinates'])) {
                $location->latitude = $locationData['coordinates']['lat'] ?? 0;
                $location->longitude = $locationData['coordinates']['lng'] ?? 0;
            } else {
                $location->latitude = $locationData['latitude'] ?? 0;
                $location->longitude = $locationData['longitude'] ?? 0;
            }

            $wasNewLocation = ! $location->exists;
            $location->save();

            if ($wasNewLocation) {
                $this->stats['locations_saved']++;
            }

            $this->uniqueLocations[$locationCacheKey] = $location->id;
        }

        // Link location to venue
        $venue->location_id = $location->id;
        $venue->save();
    }

    /**
     * Process city data
     *
     * @param  array|string  $cityData  City data
     * @param  Venue  $venue  Related venue
     * @return void
     */
    private function processCity($cityData, Venue $venue)
    {
        $cityName = is_string($cityData) ? $cityData : ($cityData['name'] ?? 'Unknown');
        $citySlug = Str::slug($cityName);

        if (isset($this->uniqueCities[$citySlug])) {
            $city = $this->uniqueCities[$citySlug];
        }

        if (! isset($city) || ! $city) {
            $lookup = $citySlug !== ''
                ? ['slug' => $citySlug]
                : ['name' => $cityName];

            $city = City::firstOrNew($lookup);
            $city->name = $cityName;
            $city->slug = $citySlug;

            $wasNewCity = ! $city->exists;
            $city->save();

            if ($wasNewCity) {
                $this->stats['cities_saved']++;
            }

            $this->uniqueCities[$citySlug] = $city;
        }

        if ($city) {
            // Link city to venue via pivot table
            if (! DB::table('city_venue')->where('city_id', $city->id)->where('venue_id', $venue->id)->exists()) {
                DB::table('city_venue')->insert([
                    'city_id' => $city->id,
                    'venue_id' => $venue->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->stats['relationships_created']++;
            }
        }
    }

    /**
     * Process city data from location tree
     *
     * @param  array  $treeData  Location tree data
     * @param  Venue  $venue  Related venue
     * @return void
     */
    private function processCityFromTree(array $treeData, Venue $venue)
    {
        // Extract city name and country from tree data
        if (isset($treeData['name'])) {
            $nameParts = explode(',', $treeData['name']);
            $cityName = trim($nameParts[1] ?? $nameParts[0]); // Assume format is "Neighborhood, City"
            $countryCode = $treeData['countryCode'] ?? null;

            $citySlug = Str::slug($countryCode ? $cityName.'-'.$countryCode : $cityName);

            if (isset($this->uniqueCities[$citySlug])) {
                $city = $this->uniqueCities[$citySlug];
            }

            if (! isset($city) || ! $city) {
                $lookup = $citySlug !== ''
                    ? ['slug' => $citySlug]
                    : ['name' => $cityName];

                $city = City::firstOrNew($lookup);
                $city->name = $cityName;
                $city->slug = $citySlug;

                if ($countryCode) {
                    // Find or create country
                    $country = Country::firstOrCreate(
                        ['code' => $countryCode],
                        ['name' => $countryCode]
                    );
                    $city->country_id = $country->id;
                }

                $wasNewCity = ! $city->exists;
                $city->save();

                if ($wasNewCity) {
                    $this->stats['cities_saved']++;
                }

                $this->uniqueCities[$citySlug] = $city;
            }

            if ($city) {
                // Link city to venue via pivot table
                if (! DB::table('city_venue')->where('city_id', $city->id)->where('venue_id', $venue->id)->exists()) {
                    DB::table('city_venue')->insert([
                        'city_id' => $city->id,
                        'venue_id' => $venue->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->stats['relationships_created']++;
                }
            }
        }
    }

    /**
     * Process treatment data
     *
     * @param  array  $treatmentData  Treatment data
     * @param  Venue  $venue  Related venue
     * @return void
     */
    private function processTreatment(array $treatmentData, Venue $venue)
    {
        $treatmentId = $treatmentData['id']
            ?? $treatmentData['externalId']
            ?? $treatmentData['uuid']
            ?? null;

        if (! $treatmentId) {
            $treatmentId = $venue->id.'-'.Str::slug($treatmentData['name'] ?? (string) Str::uuid());
        }

        $treatmentId = (string) $treatmentId;
        $treatmentCacheKey = $treatmentId;

        if (isset($this->uniqueTreatments[$treatmentCacheKey])) {
            $treatment = $this->uniqueTreatments[$treatmentCacheKey];
        }

        if (! isset($treatment) || ! $treatment) {
            $treatment = Treatment::firstOrNew(['external_id' => $treatmentId]);
            $treatment->external_id = $treatmentId;
            $treatment->venue_id = $venue->id;
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

            $wasNewTreatment = ! $treatment->exists;
            $treatment->save();

            if ($wasNewTreatment) {
                $this->stats['treatments_saved']++;
            }

            $this->uniqueTreatments[$treatmentCacheKey] = $treatment;
        }

        if ($treatment) {
            // Link treatment to venue via the proper pivot table
            try {
                if (! DB::table('treatment_venue')->where('treatment_id', $treatment->id)->where('venue_id', $venue->id)->exists()) {
                    DB::table('treatment_venue')->insert([
                        'treatment_id' => $treatment->id,
                        'venue_id' => $venue->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->stats['relationships_created']++;
                }
            } catch (\Exception $e) {
                // Log error but continue processing
                Log::error('Error creating treatment-venue relationship: '.$e->getMessage());
            }
        }
    }

    /**
     * Process image data
     *
     * @param  array|string  $imageData  Image data or URL
     * @param  Venue  $venue  Related venue
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

        $image = new Image;
        $image->venue_id = $venue->id;
        $image->url = $imageUrl;
        $image->type = 'venue';
        $image->save();

        $this->stats['images_saved']++;
    }

    /**
     * Process opening hour data
     *
     * @param  array  $hourData  Opening hour data
     * @param  Venue  $venue  Related venue
     * @return void
     */
    private function processOpeningHour(array $hourData, Venue $venue)
    {
        $openingHour = new OpeningHour;
        $openingHour->venue_id = $venue->id;

        // Map day of week to numeric value
        $dayMap = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 0,
        ];

        $day = $hourData['dayOfWeek'] ?? ($hourData['day'] ?? null);
        if (is_string($day) && isset($dayMap[strtolower($day)])) {
            $openingHour->day = $dayMap[strtolower($day)];
        } else {
            $openingHour->day = $day ?? 0;
        }

        $openingHour->open_time = $hourData['from'] ?? ($hourData['openTime'] ?? '00:00:00');
        $openingHour->close_time = $hourData['to'] ?? ($hourData['closeTime'] ?? '23:59:59');
        $openingHour->is_closed = ! ($hourData['open'] ?? true);
        $openingHour->save();

        $this->stats['opening_hours_saved']++;
    }

    /**
     * Process rating data
     *
     * @param  array|float  $ratingData  Rating data or value
     * @param  Venue  $venue  Related venue
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
        // Create relationships between cities and treatments
        $this->info('Creating city-treatment relationships...');

        try {
            // Get all venues with their cities and treatments
            $venuesWithCitiesAndTreatments = Venue::with('cities', 'treatments')->get();

            if ($venuesWithCitiesAndTreatments->isEmpty()) {
                $this->warn('No venues with cities and treatments found. Skipping relationship creation.');

                return;
            }

            $progress = $this->output->createProgressBar(count($venuesWithCitiesAndTreatments));
            $progress->start();

            foreach ($venuesWithCitiesAndTreatments as $venue) {
                foreach ($venue->cities as $city) {
                    foreach ($venue->treatments as $treatment) {
                        // Create city-treatment relationships via the pivot table
                        try {
                            if (! DB::table('city_treatment')->where('city_id', $city->id)->where('treatment_id', $treatment->id)->exists()) {
                                DB::table('city_treatment')->insert([
                                    'city_id' => $city->id,
                                    'treatment_id' => $treatment->id,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                $this->stats['relationships_created']++;
                            }
                        } catch (\Exception $e) {
                            Log::error('Error creating city-treatment relationship: '.$e->getMessage());
                        }
                    }
                }
                $progress->advance();
            }

            $progress->finish();
            $this->line('');

            // Also call the original linkProceduresToCities method
            $this->info('Linking procedures to cities...');
            $this->linkProceduresToCities();
        } catch (\Exception $e) {
            $this->error('Error in relationship creation: '.$e->getMessage());
            Log::error('Relationship creation error: '.$e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * Format bytes to human-readable format
     *
     * @param  int  $bytes  Number of bytes
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
