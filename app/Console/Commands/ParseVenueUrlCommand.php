<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Procedure;
use App\Models\Venue;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class ParseVenueUrlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'venues:parse-url 
                            {url : The API URL to parse}
                            {--debug : Show debug output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse a specific Treatwell API URL and save all information';

    /**
     * Debug mode flag
     */
    private bool $debug = false;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->debug = $this->option('debug');
        $apiUrl = $this->argument('url');

        $this->info("Parsing URL: {$apiUrl}");

        // Fetch data from API
        $response = $this->makeApiRequest($apiUrl);

        if (! $response) {
            $this->error('Failed to fetch data from API URL');

            return 1;
        }

        $this->info('Data fetched successfully!');

        // Extract endpoint information (treatment, location) from URL
        $browseUri = $this->extractBrowseUriFromUrl($apiUrl);
        $endpoint = $this->parseEndpoint($browseUri);

        if ($this->debug) {
            $this->info('Endpoint info:');
            foreach ($endpoint as $key => $value) {
                $this->line("  - {$key}: {$value}");
            }

            // Output summary of the response
            $this->info('Response summary:');
            $this->line('  - Results count: '.(isset($response['results']) ? count($response['results']) : 0));
            $this->line('  - Has pagination: '.(isset($response['pagination']) ? 'Yes' : 'No'));
            if (isset($response['pagination'])) {
                $this->line('  - Current page: '.($response['pagination']['currentPage'] ?? $response['pagination']['page'] ?? 'Unknown'));
                $this->line('  - Total pages: '.($response['pagination']['totalPages'] ?? 'Unknown'));
            }
        }

        // Dump the first result for inspection
        if ($this->debug && isset($response['results']) && ! empty($response['results'])) {
            $firstResult = $response['results'][0];
            $this->info('First result type: '.($firstResult['type'] ?? 'Unknown'));
            if (isset($firstResult['data'])) {
                $this->info('Data is present for the first result');
                $venueData = $firstResult['data'];
                $this->info('Venue ID: '.($venueData['id'] ?? 'Unknown'));
                $this->info('Venue Name: '.($venueData['name'] ?? 'Unknown'));
            }
        }

        // Process venues from API response
        $processedData = $this->processVenuesFromResponse($response, $endpoint);
        $venues = $processedData['venues'];

        $this->info('Found '.count($venues).' venues in response');

        if (count($venues) === 0) {
            $this->warn('No valid venues found in the response. Exiting.');

            return 0;
        }

        // Show database connection info
        try {
            $connection = \DB::connection()->getPdo();
            $this->info('Database connection established: '.\DB::connection()->getDatabaseName());
        } catch (\Exception $e) {
            $this->error('Database connection failed: '.$e->getMessage());

            return 1;
        }

        // Save venues to database
        $savedCount = $this->saveVenuesToDatabase($venues);
        $this->info("Saved {$savedCount} venues to database with full details");

        return 0;
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

                if ($response->successful()) {
                    // Get the response body
                    $body = $response->body();

                    if ($this->debug) {
                        $this->info('Response status: '.$response->status());
                        $this->info('Response size: '.strlen($body).' bytes');
                    }

                    // Check for XSSI protection prefix and remove it if present
                    $xssiPrefix = ")]}', ";
                    if (strpos($body, $xssiPrefix) === 0) {
                        $body = substr($body, strlen($xssiPrefix));
                        if ($this->debug) {
                            $this->info('Removed XSSI protection prefix from response');
                        }
                    }

                    // Decode JSON
                    $data = json_decode($body, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->error('JSON decode error: '.json_last_error_msg());

                        return null;
                    }

                    return $data;
                }

                $this->warn("Attempt {$attempt} failed to fetch URL (Status: {$response->status()})");

                if ($attempt < $maxRetries) {
                    $sleepTime = $retryDelay * $attempt;
                    $this->info("Retrying in {$sleepTime} seconds...");
                    sleep($sleepTime);
                }
            } catch (Exception $e) {
                $this->error('Exception during API request: '.$e->getMessage());

                if ($attempt < $maxRetries) {
                    $sleepTime = $retryDelay * $attempt;
                    $this->info("Retrying in {$sleepTime} seconds...");
                    sleep($sleepTime);
                }
            }
        }

        $this->error("Failed to fetch data after {$maxRetries} attempts");

        return null;
    }

    /**
     * Extract browse URI from full API URL
     */
    private function extractBrowseUriFromUrl(string $url): string
    {
        $params = [];
        parse_str(parse_url($url, PHP_URL_QUERY), $params);

        return isset($params['currentBrowseUri']) ?
            urldecode($params['currentBrowseUri']) :
            '/';
    }

    /**
     * Parse endpoint information from browse URI
     */
    private function parseEndpoint(string $browseUri): array
    {
        $endpoint = [
            'path' => $browseUri,
            'browse_uri' => $browseUri,
            'treatment' => '',
            'treatment_name' => 'Unknown',
            'offer_type' => '',
            'offer_type_name' => 'Unknown',
            'location' => '',
            'location_name' => 'Unknown',
        ];

        // Extract treatment, offer type, and location from path
        if (preg_match('#/salonai/procedura-([^/]+)/pasiulymo-tipas-([^/]+)/kur-([^/]+)/?#', $browseUri, $matches)) {
            $endpoint['treatment'] = $matches[1];
            $endpoint['offer_type'] = $matches[2];
            $endpoint['location'] = $matches[3];

            // Format names
            $endpoint['treatment_name'] = $this->formatName($matches[1]);
            $endpoint['offer_type_name'] = $this->formatName($matches[2]);
            $endpoint['location_name'] = $this->formatName($matches[3]);
        }

        return $endpoint;
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
     * Process venues from API response
     *
     * @return array Processed venues and stats
     */
    private function processVenuesFromResponse(array $response, array $endpoint): array
    {
        $venues = [];
        $invalidCount = 0;

        // Check if response has results
        if (! isset($response['results']) || ! is_array($response['results'])) {
            return ['venues' => $venues, 'invalid_count' => $invalidCount];
        }

        if ($this->debug) {
            $this->info('Processing '.count($response['results']).' results from response');
        }

        foreach ($response['results'] as $result) {
            // Check if this is a venue result
            if (! isset($result['type']) || $result['type'] !== 'venue' || ! isset($result['data'])) {
                continue;
            }

            $venue = $result['data'];

            // Skip invalid venues
            if (! isset($venue['id']) || ! isset($venue['name'])) {
                $invalidCount++;

                continue;
            }

            $venueId = $venue['id'];
            $venueName = trim($venue['name']);

            // Skip venues with test/demo names
            if ($this->isTestVenue($venueName)) {
                $invalidCount++;

                continue;
            }

            // Extract URL from response
            $venueUrl = $this->extractVenueUrl($venue, $venueId, $venueName);

            // Extract location data
            $cityName = $endpoint['location_name'] ?? 'Unknown';
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

                // Extract map data
                $mapZoom = null;
                if (isset($location['map']) && isset($location['map']['zoom'])) {
                    $mapZoom = $location['map']['zoom'];
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
                    'dimensions' => [],
                ];

                // Extract rating dimensions
                if (isset($ratingData['dimensions']) && is_array($ratingData['dimensions'])) {
                    foreach ($ratingData['dimensions'] as $dimension) {
                        if (isset($dimension['name']) && isset($dimension['average'])) {
                            $rating['dimensions'][] = [
                                'name' => $dimension['name'],
                                'average' => $dimension['average'],
                                'count' => $dimension['count'] ?? null,
                            ];
                        }
                    }
                }
            }

            // Extract opening hours
            $openingHours = [];
            if (isset($venue['openingHours']) && is_array($venue['openingHours'])) {
                foreach ($venue['openingHours'] as $hours) {
                    if (isset($hours['dayOfWeek']) && isset($hours['from']) && isset($hours['to'])) {
                        $openingHours[] = [
                            'day' => $hours['dayOfWeek'],
                            'open' => $hours['open'] ?? true,
                            'from' => $hours['from'],
                            'to' => $hours['to'],
                        ];
                    }
                }
            }

            // Extract images
            $images = [];
            if (isset($venue['images']) && is_array($venue['images'])) {
                foreach ($venue['images'] as $image) {
                    if (isset($image['id']) && isset($image['uris'])) {
                        $largestImage = null;
                        $largestSize = 0;

                        // Find the largest image
                        foreach ($image['uris'] as $size => $uri) {
                            [$width, $height] = explode('x', $size);
                            $totalSize = (int) $width * (int) $height;
                            if ($totalSize > $largestSize) {
                                $largestSize = $totalSize;
                                $largestImage = $uri;
                            }
                        }

                        if ($largestImage) {
                            $images[] = [
                                'id' => $image['id'],
                                'url' => $largestImage,
                            ];
                        }
                    }
                }
            }

            // Extract primary image
            $primaryImage = null;
            if (isset($venue['primaryImage']) && isset($venue['primaryImage']['uris'])) {
                $largestImage = null;
                $largestSize = 0;

                // Find the largest image
                foreach ($venue['primaryImage']['uris'] as $size => $uri) {
                    [$width, $height] = explode('x', $size);
                    $totalSize = (int) $width * (int) $height;
                    if ($totalSize > $largestSize) {
                        $largestSize = $totalSize;
                        $largestImage = $uri;
                    }
                }

                if ($largestImage) {
                    $primaryImage = $largestImage;
                }
            }

            // Extract treatment data if available
            $treatments = [];
            if (isset($venue['menuHighlights']) && is_array($venue['menuHighlights'])) {
                foreach ($venue['menuHighlights'] as $highlight) {
                    if (isset($highlight['type']) && $highlight['type'] === 'treatment' && isset($highlight['data'])) {
                        $treatmentData = $highlight['data'];
                        if (isset($treatmentData['name'])) {
                            $treatment = [
                                'id' => $treatmentData['id'] ?? null,
                                'name' => $treatmentData['name'],
                                'min_price' => null,
                                'max_price' => null,
                                'min_duration' => null,
                                'max_duration' => null,
                            ];

                            // Extract price range
                            if (isset($treatmentData['priceRange'])) {
                                $priceRange = $treatmentData['priceRange'];
                                $treatment['min_price'] = $priceRange['minSalePriceAmount'] ?? null;
                                $treatment['max_price'] = $priceRange['maxSalePriceAmount'] ?? null;
                            }

                            // Extract duration range
                            if (isset($treatmentData['durationRange'])) {
                                $durationRange = $treatmentData['durationRange'];
                                $treatment['min_duration'] = $durationRange['minDurationMinutes'] ?? null;
                                $treatment['max_duration'] = $durationRange['maxDurationMinutes'] ?? null;
                            }

                            $treatments[] = $treatment;
                        }
                    }
                }
            }

            // Add treatment from endpoint if not already in treatments
            if (isset($endpoint['treatment_name']) && $endpoint['treatment_name'] !== 'Unknown') {
                $endpointTreatmentName = $endpoint['treatment_name'];
                $found = false;

                foreach ($treatments as $treatment) {
                    if (strcasecmp($treatment['name'], $endpointTreatmentName) === 0) {
                        $found = true;
                        break;
                    }
                }

                if (! $found) {
                    $treatments[] = [
                        'id' => null,
                        'name' => $endpointTreatmentName,
                        'min_price' => null,
                        'max_price' => null,
                        'min_duration' => null,
                        'max_duration' => null,
                    ];
                }
            }

            // Build complete venue record
            $venueData = [
                'id' => $venueId,
                'name' => $venueName,
                'url' => $venueUrl,
                'city' => $cityName,
                'address' => $address,
                'postal_code' => $postalCode,
                'country_id' => $countryId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'map_zoom' => $mapZoom,
                'description' => $venue['description'] ?? null,
                'is_new_venue' => $venue['newVenue'] ?? false,
                'primary_image' => $primaryImage,
                'images' => $images,
                'rating' => $rating,
                'opening_hours' => $openingHours,
                'treatments' => $treatments,
                'treatment_names' => array_map(function ($treatment) {
                    return $treatment['name'];
                }, $treatments),
                'raw_data' => $venue, // Store the complete raw venue data
            ];

            $venues[] = $venueData;

            if ($this->debug) {
                $this->info("Processed venue: {$venueName} (ID: {$venueId})");
            }
        }

        return [
            'venues' => $venues,
            'invalid_count' => $invalidCount,
        ];
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
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $venueName));

        return "https://www.treatwell.lt/vieta/{$slug}-{$venueId}/";
    }

    /**
     * Check if venue name indicates a test/demo venue
     */
    private function isTestVenue(string $name): bool
    {
        $patterns = [
            'test', 'demo', 'example', 'sample', 'training', 'temporary',
            'do not book', 'do not use', 'neveikia', 'netikras',
        ];

        foreach ($patterns as $pattern) {
            if (stripos($name, $pattern) !== false) {
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
        $skippedCount = 0;

        foreach ($venues as $venueData) {
            // Check required fields
            if (empty($venueData['name']) || empty($venueData['url']) || empty($venueData['city'])) {
                $skippedCount++;
                if ($this->debug) {
                    $this->warn('Skipping venue: Missing required fields');
                }

                continue;
            }

            // Normalize data
            $venueData['name'] = trim($venueData['name']);
            $city = trim($venueData['city'] ?? 'Unknown');
            $url = trim($venueData['url']);

            if ($this->debug) {
                $this->info("Saving venue: {$venueData['name']} in {$city}");
            }

            try {
                // Find or create the city first
                $citySlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $city));

                $this->info("Checking if city exists with slug: {$citySlug}");
                $cityCount = City::where('slug', $citySlug)->count();
                $this->info("Found {$cityCount} cities with this slug");

                $cityModel = City::firstOrCreate(
                    ['slug' => $citySlug],
                    [
                        'name' => $city,
                        'latitude' => $venueData['latitude'] ?? null,
                        'longitude' => $venueData['longitude'] ?? null,
                        'country_id' => $venueData['country_id'] ?? null,
                    ]
                );

                if ($this->debug) {
                    $this->info("City: {$cityModel->name} (ID: {$cityModel->id})");
                }

                // Generate a slug from the URL
                $slug = '';
                if (preg_match('/\/vieta\/([^\/]+)/', $url, $matches)) {
                    $slug = $matches[1];
                } elseif (preg_match('/\/salonas\/([^\/]+)/', $url, $matches)) {
                    $slug = $matches[1];
                } else {
                    // Create a slug from the name if not found in URL
                    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $venueData['name']));
                }

                if ($this->debug) {
                    $this->info("Generated slug: {$slug}");
                }

                // Prepare venue data for saving
                $venueModelData = [
                    'name' => $venueData['name'],
                    'slug' => $slug,
                    'url' => $url,
                    'source' => 'treatwell_api',
                    'external_id' => $venueData['id'] ?? null,
                    'description' => $venueData['description'] ?? null,
                    'is_new_venue' => $venueData['is_new_venue'] ?? false,
                    'raw_data' => isset($venueData['raw_data']) ? json_encode($venueData['raw_data'], JSON_UNESCAPED_UNICODE) : json_encode($venueData, JSON_UNESCAPED_UNICODE),
                ];

                // Check venue table structure
                try {
                    $this->info('Checking venue table structure');
                    $columns = Schema::getColumnListing('venues');
                    $this->info('Venue table columns: '.implode(', ', $columns));
                } catch (\Exception $e) {
                    $this->error('Error checking venue table: '.$e->getMessage());
                }

                // Save venue - first check if it exists
                $this->info("Checking if venue exists with slug: {$slug}");
                $existingVenue = Venue::where('slug', $slug)->first();

                if ($existingVenue) {
                    $this->info("Updating existing venue with ID: {$existingVenue->id}");
                    $existingVenue->update($venueModelData);
                    $venue = $existingVenue;
                } else {
                    $this->info('Creating new venue');
                    try {
                        $venue = Venue::create($venueModelData);
                        $this->info("Created venue with ID: {$venue->id}");
                    } catch (\Exception $e) {
                        $this->error('Error creating venue: '.$e->getMessage());
                        throw $e;
                    }
                }

                // Process location data
                if (! empty($venueData['address'])) {
                    $locationData = [
                        'address_line1' => $venueData['address'],
                        'city_id' => $cityModel->id,
                        'postal_code' => $venueData['postal_code'] ?? null,
                        'latitude' => $venueData['latitude'] ?? null,
                        'longitude' => $venueData['longitude'] ?? null,
                        'map_zoom' => $venueData['map_zoom'] ?? null,
                    ];

                    // Update or create location
                    if ($venue->location) {
                        $venue->location->update($locationData);
                        if ($this->debug) {
                            $this->info("Updated existing location: ID {$venue->location->id}");
                        }
                    } else {
                        $location = $venue->location()->create($locationData);
                        if ($this->debug) {
                            $this->info("Created new location: ID {$location->id}");
                        }
                    }
                }

                // Process rating data
                if (! empty($venueData['rating'])) {
                    $ratingData = $venueData['rating'];
                    $ratingModelData = [
                        'average' => $ratingData['average'] ?? null,
                        'count' => $ratingData['count'] ?? null,
                        'dimensions' => json_encode($ratingData['dimensions'] ?? [], JSON_UNESCAPED_UNICODE),
                    ];

                    // Update or create rating
                    if ($venue->rating) {
                        $venue->rating->update($ratingModelData);
                        if ($this->debug) {
                            $this->info("Updated existing rating: ID {$venue->rating->id}");
                        }
                    } else {
                        $rating = $venue->rating()->create($ratingModelData);
                        if ($this->debug) {
                            $this->info("Created new rating: ID {$rating->id}");
                        }
                    }
                }

                // Process opening hours
                if (! empty($venueData['opening_hours'])) {
                    // Delete existing opening hours to avoid duplicates
                    $venue->openingHours()->delete();

                    if ($this->debug) {
                        $this->info('Processing '.count($venueData['opening_hours']).' opening hours');
                    }

                    // Add new opening hours
                    foreach ($venueData['opening_hours'] as $hours) {
                        $venue->openingHours()->create([
                            'day_of_week' => $hours['day'],
                            'is_open' => $hours['open'] ?? true,
                            'open_time' => $hours['from'],
                            'close_time' => $hours['to'],
                        ]);
                    }
                }

                // Process images
                if (! empty($venueData['images'])) {
                    // Get existing image IDs
                    $existingImageIds = $venue->images()->pluck('external_id')->toArray();

                    if ($this->debug) {
                        $this->info('Processing '.count($venueData['images']).' images');
                    }

                    foreach ($venueData['images'] as $imageData) {
                        // Skip if image already exists
                        if (in_array((string) $imageData['id'], $existingImageIds)) {
                            continue;
                        }

                        $venue->images()->create([
                            'external_id' => $imageData['id'],
                            'url' => $imageData['url'],
                            'is_primary' => $venueData['primary_image'] === $imageData['url'],
                        ]);
                    }

                    // If no primary image found in the saved images but we have a primary image URL
                    if (! empty($venueData['primary_image']) && ! $venue->images()->where('is_primary', true)->exists()) {
                        $venue->images()->create([
                            'external_id' => null,
                            'url' => $venueData['primary_image'],
                            'is_primary' => true,
                        ]);
                    }
                }

                // Process treatments
                $treatmentUpdated = false;
                if (! empty($venueData['treatments'])) {
                    if ($this->debug) {
                        $this->info('Processing '.count($venueData['treatments']).' treatments');
                    }

                    foreach ($venueData['treatments'] as $treatmentData) {
                        // Skip empty treatment names
                        if (empty($treatmentData['name'])) {
                            continue;
                        }

                        $treatmentName = trim($treatmentData['name']);

                        // First create or find the procedure
                        $treatmentSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $treatmentName));
                        $procedure = Procedure::firstOrCreate(
                            ['slug' => $treatmentSlug],
                            ['name' => $treatmentName]
                        );

                        if ($this->debug) {
                            $this->info("Procedure: {$procedure->name} (ID: {$procedure->id})");
                        }

                        // Link the procedure to the venue
                        $venue->procedures()->syncWithoutDetaching([$procedure->id]);

                        // Check if treatment exists for this venue
                        $treatment = $venue->treatments()->where('name', $treatmentName)->first();

                        // Prepare treatment data
                        $treatmentModelData = [
                            'venue_id' => $venue->id,
                            'name' => $treatmentName,
                            'external_id' => $treatmentData['id'] ?? null,
                            'min_price' => $treatmentData['min_price'] ?? null,
                            'max_price' => $treatmentData['max_price'] ?? null,
                            'min_duration' => $treatmentData['min_duration'] ?? null,
                            'max_duration' => $treatmentData['max_duration'] ?? null,
                        ];

                        // Update existing or create new treatment
                        if ($treatment) {
                            $treatment->update($treatmentModelData);
                            if ($this->debug) {
                                $this->info("Updated existing treatment: ID {$treatment->id}");
                            }
                        } else {
                            $newTreatment = $venue->treatments()->create($treatmentModelData);
                            if ($this->debug) {
                                $this->info("Created new treatment: ID {$newTreatment->id}");
                            }
                        }

                        $treatmentUpdated = true;
                    }
                }

                // For simple treatment names (if we don't have detailed treatment data)
                if (! $treatmentUpdated && ! empty($venueData['treatment_names'])) {
                    if ($this->debug) {
                        $this->info('Processing '.count($venueData['treatment_names']).' treatment names');
                    }

                    foreach ($venueData['treatment_names'] as $treatmentName) {
                        // Skip empty treatment names
                        if (empty($treatmentName)) {
                            continue;
                        }

                        $treatmentName = trim($treatmentName);

                        // Create or find the procedure
                        $treatmentSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $treatmentName));
                        $procedure = Procedure::firstOrCreate(
                            ['slug' => $treatmentSlug],
                            ['name' => $treatmentName]
                        );

                        if ($this->debug) {
                            $this->info("Procedure (from names): {$procedure->name} (ID: {$procedure->id})");
                        }

                        // Link the procedure to the venue
                        $venue->procedures()->syncWithoutDetaching([$procedure->id]);
                    }
                }

                // Link the city to the venue
                $venue->cities()->syncWithoutDetaching([$cityModel->id]);

                $savedCount++;

                $this->info("Successfully saved venue: {$venue->name} with full details");

            } catch (Exception $e) {
                $this->error("Error saving venue {$venueData['name']}: ".$e->getMessage());
                if ($this->debug) {
                    $this->error('Exception trace: '.$e->getTraceAsString());
                }
                $skippedCount++;
            }
        }

        if ($skippedCount > 0) {
            $this->info("Skipped {$skippedCount} invalid venues");
        }

        return $savedCount;
    }
}
