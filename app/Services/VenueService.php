<?php

namespace App\Services;

use App\Models\City;
use App\Models\Venue;
use App\Repositories\VenueRepository;
use Illuminate\Support\Facades\Log;

class VenueService
{
    protected VenueRepository $venueRepository;

    public function __construct(VenueRepository $venueRepository)
    {
        $this->venueRepository = $venueRepository;
    }

    /**
     * Import venues from XML files
     *
     * @param string $sitemap1Path
     * @param string $sitemap2Path
     * @return array
     */
    public function importFromXmlFiles(string $sitemap1Path, string $sitemap2Path): array
    {
        $result = [
            'sitemap1_count' => 0,
            'sitemap2_count' => 0,
            'errors' => []
        ];

        try {
            // First, parse sitemap1 to get procedures and cities
            $result['sitemap1_count'] = $this->venueRepository->parseAndStoreFromXmlFile($sitemap1Path, 'sitemap1');
            
            // Then, parse sitemap2 to get venues
            $result['sitemap2_count'] = $this->venueRepository->parseAndStoreFromXmlFile($sitemap2Path, 'sitemap2');
            
            // Match venues with cities based on URL patterns
            $this->matchVenuesWithCities();
            
        } catch (\Exception $e) {
            $result['errors'][] = "Error importing venues: " . $e->getMessage();
            Log::error("Venue import error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Match venues with cities based on URL patterns
     *
     * @return void
     */
    private function matchVenuesWithCities(): void
    {
        $cities = City::all();
        $venues = Venue::all();

        foreach ($venues as $venue) {
            // For now, let's assume all venues are in all cities
            // This is a simplified approach - in a real scenario, you might 
            // need to use API calls or more complex matching logic
            
            foreach ($cities as $city) {
                $venue->cities()->syncWithoutDetaching([$city->id]);
            }
        }
    }

    /**
     * Get all venues with optional filters
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllVenues(array $filters = [])
    {
        $query = Venue::query();
        
        if (!empty($filters['city_id'])) {
            $query->whereHas('cities', function ($q) use ($filters) {
                $q->where('cities.id', $filters['city_id']);
            });
        }
        
        if (!empty($filters['procedure_id'])) {
            $query->whereHas('procedures', function ($q) use ($filters) {
                $q->where('procedures.id', $filters['procedure_id']);
            });
        }
        
        return $query->get();
    }
} 