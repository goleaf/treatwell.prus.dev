<?php

namespace App\Repositories;

use App\Models\City;
use App\Models\Procedure;
use App\Models\Venue;
use Illuminate\Support\Facades\Log;

class VenueRepository
{
    /**
     * Parse and store venue data from XML file
     *
     * @param string $xmlFile
     * @param string $source
     * @return int
     */
    public function parseAndStoreFromXmlFile(string $xmlFile, string $source = 'sitemap2'): int
    {
        $count = 0;
        
        if (!file_exists($xmlFile)) {
            Log::error("XML file not found: $xmlFile");
            return $count;
        }

        try {
            $xml = simplexml_load_file($xmlFile);
            
            if (!$xml) {
                Log::error("Failed to parse XML file: $xmlFile");
                return $count;
            }
            
            foreach ($xml->url as $url) {
                $urlString = (string) $url->loc;
                
                if (empty($urlString)) {
                    continue;
                }
                
                // Process venue data (sitemap2) - direct venue URLs
                if ($source === 'sitemap2' && strpos($urlString, 'salonas') !== false) {
                    $this->processVenueUrl($urlString, $source);
                    $count++;
                }
                
                // Process procedure/city data (sitemap1)
                if ($source === 'sitemap1' && strpos($urlString, 'procedura-') !== false && strpos($urlString, 'kur-') !== false) {
                    $this->processProcedureCityUrl($urlString);
                    $count++;
                }
            }
        } catch (\Exception $e) {
            Log::error("Error processing XML file: " . $e->getMessage());
        }
        
        return $count;
    }
    
    /**
     * Process venue URL from sitemap2
     *
     * @param string $url
     * @param string $source
     * @return Venue
     */
    private function processVenueUrl(string $url, string $source): Venue
    {
        $slug = Venue::extractSlugFromUrl($url);
        
        if (empty($slug)) {
            return new Venue();
        }
        
        return Venue::updateOrCreate(
            ['slug' => $slug],
            [
                'url' => $url,
                'source' => $source,
                'name' => $this->formatName($slug),
            ]
        );
    }
    
    /**
     * Process procedure/city URL from sitemap1
     *
     * @param string $url
     * @return void
     */
    private function processProcedureCityUrl(string $url): void
    {
        $procedureSlug = Procedure::extractSlugFromUrl($url);
        $citySlug = City::extractSlugFromUrl($url);
        
        if (empty($procedureSlug) || empty($citySlug)) {
            return;
        }
        
        // Create or update procedure
        $procedure = Procedure::updateOrCreate(
            ['slug' => $procedureSlug],
            ['name' => $this->formatName($procedureSlug)]
        );
        
        // Create or update city
        $city = City::updateOrCreate(
            ['slug' => $citySlug],
            ['name' => $this->formatName($citySlug)]
        );
        
        // Create relationship between procedure and city
        $city->procedures()->syncWithoutDetaching([$procedure->id]);
    }
    
    /**
     * Format a name from slug
     *
     * @param string $slug
     * @return string
     */
    private function formatName(string $slug): string
    {
        $name = str_replace('-', ' ', $slug);
        return ucwords($name);
    }
} 