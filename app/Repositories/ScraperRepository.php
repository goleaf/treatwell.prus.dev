<?php

namespace App\Repositories;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class ScraperRepository
{
    /**
     * Process an API response and handle errors
     *
     * @param Response|null $response The HTTP response
     * @param string $url The URL that was requested
     * @return array|null The response data or null if there was an error
     */
    public function processResponse(?Response $response, string $url): ?array
    {
        if (!$response) {
            Log::error("No response received from URL: {$url}");
            return null;
        }

        if (!$response->successful()) {
            Log::error("API request failed with status: " . $response->status() . " for URL: {$url}");
            return null;
        }

        $data = $response->json();
        
        if (!isset($data['results'])) {
            Log::error("API response missing 'results' key for URL: {$url}");
            return null;
        }
        
        return $data;
    }
    
    /**
     * Extract venue data from results
     *
     * @param array $results The API results
     * @return array An array of venue data
     */
    public function extractVenueData(array $results): array
    {
        $venueData = [];
        
        foreach ($results as $result) {
            if ($result['type'] === 'venue' && isset($result['data'])) {
                $venueData[] = $result['data'];
            }
        }
        
        return $venueData;
    }
    
    /**
     * Log pagination information
     *
     * @param array $data The API response data
     * @param string $url The URL that was requested
     * @return void
     */
    public function logPaginationInfo(array $data, string $url): void
    {
        if (isset($data['pagination'])) {
            $pagination = $data['pagination'];
            $message = "Pagination: Page {$pagination['page']} of {$pagination['totalPages']}, showing {$pagination['from']}-" . 
                ($pagination['from'] + count($data['results']) - 1) . " of {$pagination['totalElements']} venues";
            
            Log::info($message . " for URL: {$url}");
        }
    }
} 