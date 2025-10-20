<?php

namespace App\Console\Commands;

use App\Models\Venue;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SimpleSaveVenueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'venues:save-fromapi 
                            {url : The API URL to parse and save}
                            {--debug : Show debug output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse Treatwell API URL and save venue data without complex relationships';

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
        }

        // Check if we have results
        if (! isset($response['results']) || ! is_array($response['results']) || empty($response['results'])) {
            $this->error('No results found in API response');

            return 1;
        }

        // Manually process the first venue result
        $venuesProcessed = 0;

        foreach ($response['results'] as $result) {
            // Check if this is a venue result
            if (! isset($result['type']) || $result['type'] !== 'venue' || ! isset($result['data'])) {
                continue;
            }

            $venueData = $result['data'];

            // Skip invalid venues
            if (! isset($venueData['id']) || ! isset($venueData['name'])) {
                continue;
            }

            $venueId = $venueData['id'];
            $venueName = trim($venueData['name']);

            // Extract URL
            $venueUrl = '';
            if (isset($venueData['seoUrl'])) {
                $venueUrl = "https://www.treatwell.lt{$venueData['seoUrl']}";
            } elseif (isset($venueData['url'])) {
                $venueUrl = "https://www.treatwell.lt{$venueData['url']}";
            } elseif (isset($venueData['links']) && isset($venueData['links']['self'])) {
                $venueUrl = "https://www.treatwell.lt{$venueData['links']['self']}";
            } else {
                // Generate a URL based on name as fallback
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $venueName));
                $venueUrl = "https://www.treatwell.lt/vieta/{$slug}-{$venueId}/";
            }

            // Generate a slug from the URL
            $slug = '';
            if (preg_match('/\/vieta\/([^\/]+)/', $venueUrl, $matches)) {
                $slug = $matches[1];
            } elseif (preg_match('/\/salonas\/([^\/]+)/', $venueUrl, $matches)) {
                $slug = $matches[1];
            } else {
                // Create a slug from the name if not found in URL
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $venueName));
            }

            $this->info("Processing venue: {$venueName}");
            $this->info("Slug: {$slug}");

            try {
                // Prepare venue data for saving (minimal version)
                $venueModelData = [
                    'name' => $venueName,
                    'slug' => $slug,
                    'url' => $venueUrl,
                    'source' => 'treatwell_api',
                    'external_id' => $venueId,
                    'description' => $venueData['description'] ?? null,
                    'is_new_venue' => $venueData['newVenue'] ?? false,
                    'raw_data' => json_encode($venueData, JSON_UNESCAPED_UNICODE),
                ];

                $this->info('Saving venue data...');

                // Save venue with minimal data first
                $venue = Venue::updateOrCreate(
                    ['slug' => $slug],
                    $venueModelData
                );

                $this->info("Venue saved with ID: {$venue->id}");
                $venuesProcessed++;

            } catch (Exception $e) {
                $this->error('Error saving venue: '.$e->getMessage());
                if ($this->debug) {
                    $this->error('Exception trace: '.$e->getTraceAsString());
                }
            }
        }

        $this->info("Processed and saved {$venuesProcessed} venues from the API response");

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
                        $this->error('JSON decode error: '.json_last_error_msg());

                        return null;
                    }

                    return $data;
                }

            } catch (Exception $e) {
                $this->error('Exception during API request: '.$e->getMessage());
            }

            $this->warn("Retrying in {$retryDelay} seconds...");
            sleep($retryDelay);
        }

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
}
