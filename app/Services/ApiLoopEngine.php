<?php

namespace App\Services;

use App\Models\City;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiLoopEngine
{
    private string $baseApiUrl = 'https://www.treatwell.lt/api/v1/page/browse';
    private int $maxRetries = 3;
    private int $baseRetryDelay = 2; // seconds
    private float $jitterFactor = 0.25; // 25% jitter
    private int $rateLimitDelay = 1000000; // 1 second in microseconds
    private array $circuitBreaker = [
        'failure_count' => 0,
        'failure_threshold' => 5,
        'timeout_duration' => 60, // seconds
        'last_failure_time' => null,
        'state' => 'closed' // closed, open, half-open
    ];

    /**
     * Get all cities available for processing
     */
    public function getAllCities(): Collection
    {
        return City::all();
    }

    /**
     * Get API endpoints for a specific city
     */
    public function getApiEndpointsForCity(string $citySlug): array
    {
        // Generate API endpoints based on city slug
        // This mimics the pattern from FetchVenuesCommand where we build endpoints
        // from treatment/location combinations
        
        $endpoints = [];
        
        // Common treatment categories that might exist for any city
        $commonTreatments = [
            'plauku-kirpimas',
            'plauku-dazimas', 
            'manikuras',
            'pedikuras',
            'masazas',
            'veido-procedura',
            'antakiu-formavimas',
            'blakstienu-laminavimas'
        ];

        $commonOfferTypes = [
            'salonas',
            'namuose',
            'mobilus'
        ];

        // Generate endpoints for this city with common treatments
        foreach ($commonTreatments as $treatment) {
            foreach ($commonOfferTypes as $offerType) {
                $browseUri = "/salonai/procedura-{$treatment}/pasiulymo-tipas-{$offerType}/kur-{$citySlug}";
                
                $endpoints[] = [
                    'browse_uri' => $browseUri,
                    'treatment_slug' => $treatment,
                    'offer_type_slug' => $offerType,
                    'location_slug' => $citySlug,
                    'api_url' => $this->buildApiUrl($browseUri, 0)
                ];
            }
        }

        return $endpoints;
    }

    /**
     * Process a single API endpoint with comprehensive error handling
     */
    public function processApiEndpoint(string|array $endpoint, array $options = []): ?ApiResponse
    {
        // Check circuit breaker state
        if (!$this->isCircuitBreakerClosed()) {
            Log::warning('Circuit breaker is open, skipping API call', ['endpoint' => $endpoint]);
            return null;
        }

        $maxPages = $options['max_pages'] ?? 0;
        $page = $options['page'] ?? 0;
        
        // If endpoint is a browse_uri, convert to full API URL
        if (is_array($endpoint)) {
            $apiUrl = $endpoint['api_url'] ?? $this->buildApiUrl($endpoint['browse_uri'], $page);
        } else {
            $apiUrl = $this->buildApiUrl($endpoint, $page);
        }

        return $this->makeApiRequestWithRetry($apiUrl);
    }

    /**
     * Handle rate limiting with exponential backoff and jitter
     */
    public function handleRateLimit(int $retryAfter): void
    {
        $delay = $retryAfter > 0 ? $retryAfter : $this->calculateBackoffDelay(1);
        
        Log::info('Rate limit encountered, waiting', [
            'delay_seconds' => $delay,
            'retry_after' => $retryAfter
        ]);

        sleep($delay);
    }

    /**
     * Handle API errors with circuit breaker pattern
     */
    public function handleApiError(\Exception $exception): void
    {
        $this->recordCircuitBreakerFailure();
        
        Log::error('API error encountered', [
            'error' => $exception->getMessage(),
            'circuit_breaker_state' => $this->circuitBreaker['state'],
            'failure_count' => $this->circuitBreaker['failure_count']
        ]);

        // If we've hit the failure threshold, open the circuit breaker
        if ($this->circuitBreaker['failure_count'] >= $this->circuitBreaker['failure_threshold']) {
            $this->openCircuitBreaker();
        }
    }

    /**
     * Make API request with retry logic and rate limiting
     */
    private function makeApiRequestWithRetry(string $url): ?ApiResponse
    {
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                // Apply rate limiting delay
                if ($attempt > 1) {
                    usleep($this->rateLimitDelay);
                }

                $response = Http::timeout(30)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'User-Agent' => 'Mozilla/5.0 (compatible; CityDataParser/1.0)',
                    ])
                    ->get($url);

                // Handle rate limiting
                if ($response->status() === 429) {
                    $retryAfter = (int) $response->header('Retry-After', 5);
                    $this->handleRateLimit($retryAfter);
                    continue;
                }

                // Handle successful response
                if ($response->successful()) {
                    $this->recordCircuitBreakerSuccess();
                    return new ApiResponse($response->json(), $response->status());
                }

                // Handle client/server errors
                if ($response->status() >= 400) {
                    $exception = new \Exception("HTTP {$response->status()}: " . $response->body());
                    
                    if ($attempt === $this->maxRetries) {
                        $this->handleApiError($exception);
                        return null;
                    }
                    
                    // Wait before retry with exponential backoff
                    $delay = $this->calculateBackoffDelay($attempt);
                    sleep($delay);
                    continue;
                }

            } catch (\Exception $e) {
                if ($attempt === $this->maxRetries) {
                    $this->handleApiError($e);
                    return null;
                }

                // Wait before retry with exponential backoff
                $delay = $this->calculateBackoffDelay($attempt);
                sleep($delay);
            }
        }

        return null;
    }

    /**
     * Calculate exponential backoff delay with jitter
     */
    private function calculateBackoffDelay(int $attempt): int
    {
        $baseDelay = $this->baseRetryDelay * pow(2, $attempt - 1);
        $jitter = $baseDelay * $this->jitterFactor * (mt_rand() / mt_getrandmax() - 0.5);
        
        return max(1, (int) ($baseDelay + $jitter));
    }

    /**
     * Build API URL from browse URI and page
     */
    private function buildApiUrl(string $browseUri, int $page = 0): string
    {
        return $this->baseApiUrl . '?page=' . $page . '&currentBrowseUri=' . urlencode($browseUri);
    }

    /**
     * Check if circuit breaker is closed (allowing requests)
     */
    private function isCircuitBreakerClosed(): bool
    {
        if ($this->circuitBreaker['state'] === 'closed') {
            return true;
        }

        if ($this->circuitBreaker['state'] === 'open') {
            // Check if timeout period has passed
            $timeSinceLastFailure = time() - $this->circuitBreaker['last_failure_time'];
            
            if ($timeSinceLastFailure >= $this->circuitBreaker['timeout_duration']) {
                // Move to half-open state
                $this->circuitBreaker['state'] = 'half-open';
                Log::info('Circuit breaker moved to half-open state');
                return true;
            }
            
            return false;
        }

        // half-open state - allow one request to test
        return true;
    }

    /**
     * Record a circuit breaker failure
     */
    private function recordCircuitBreakerFailure(): void
    {
        $this->circuitBreaker['failure_count']++;
        $this->circuitBreaker['last_failure_time'] = time();
        
        if ($this->circuitBreaker['state'] === 'half-open') {
            // Failed during half-open, go back to open
            $this->openCircuitBreaker();
        }
    }

    /**
     * Record a circuit breaker success
     */
    private function recordCircuitBreakerSuccess(): void
    {
        if ($this->circuitBreaker['state'] === 'half-open') {
            // Success during half-open, close the circuit
            $this->circuitBreaker['state'] = 'closed';
            $this->circuitBreaker['failure_count'] = 0;
            Log::info('Circuit breaker closed after successful request');
        } elseif ($this->circuitBreaker['state'] === 'closed') {
            // Reset failure count on successful requests
            $this->circuitBreaker['failure_count'] = max(0, $this->circuitBreaker['failure_count'] - 1);
        }
    }

    /**
     * Open the circuit breaker
     */
    private function openCircuitBreaker(): void
    {
        $this->circuitBreaker['state'] = 'open';
        $this->circuitBreaker['last_failure_time'] = time();
        
        Log::warning('Circuit breaker opened due to repeated failures', [
            'failure_count' => $this->circuitBreaker['failure_count'],
            'timeout_duration' => $this->circuitBreaker['timeout_duration']
        ]);
    }

    /**
     * Get current circuit breaker state for monitoring
     */
    public function getCircuitBreakerState(): array
    {
        return $this->circuitBreaker;
    }

    /**
     * Reset circuit breaker (for testing or manual intervention)
     */
    public function resetCircuitBreaker(): void
    {
        $this->circuitBreaker = [
            'failure_count' => 0,
            'failure_threshold' => 5,
            'timeout_duration' => 60,
            'last_failure_time' => null,
            'state' => 'closed'
        ];
        
        Log::info('Circuit breaker manually reset');
    }
}

/**
 * Simple API Response wrapper
 */
class ApiResponse
{
    private array $data;
    private int $statusCode;

    public function __construct(array $data, int $statusCode)
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}