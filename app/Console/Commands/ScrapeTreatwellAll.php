<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Country;
use App\Models\Image;
use App\Models\Location;
use App\Models\OpeningHour;
use App\Models\Rating;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ScrapeTreatwellAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:treatwell-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape all Treatwell data for all Lithuanian cities in one command';

    /**
     * List of Lithuanian cities to scrape
     */
    protected $cities = [
        'vilnius-lt',
        'kaunas-lt',
        'klaipeda-lt',
        'siauliai-lt',
        'panevezys-lt',
        'alytus-lt',
        'marijampole-lt',
        'mazeikiai-lt',
        'jonava-lt',
        'utena-lt',
        'kedainiai-lt',
        'telsiai-lt',
        'visaginas-lt',
        'taurage-lt',
        'ukmerge-lt',
        'plunge-lt',
        'kretinga-lt',
        'palanga-lt',
        'radviliškis-lt',
        'druskininkai-lt',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting comprehensive scraping for all Treatwell data...');

        // Setup Lithuania as the main country
        $country = Country::firstOrCreate(
            ['code' => 'LT'],
            [
                'name' => 'Lithuania',
                'normalised_name' => 'lithuania',
                'active' => true,
            ]
        );

        $this->info("Country setup complete. Country ID: {$country->id}");

        // Process each city
        $lastCityIndex = array_key_last($this->cities);

        foreach ($this->cities as $index => $location) {
            $this->info('============================');
            $this->info("Starting scraping for location: {$location}");

            $this->scrapeLocation($location, $country);

            $this->throttleBetweenCities($index, $lastCityIndex);
        }

        // Display final stats
        $this->displayStats();

        $this->info('============================');
        $this->info('All scraping has been completed!');

        return 0;
    }

    protected function throttleBetweenCities(int $currentIndex, int $lastIndex): void
    {
        if ($currentIndex === $lastIndex) {
            return;
        }

        if (app()->environment('testing')) {
            return;
        }

        $this->info('Waiting before moving to the next city...');
        $this->sleepBetweenCities();
    }

    protected function sleepBetweenCities(): void
    {
        sleep(5);
    }

    /**
     * Scrape data for a specific location
     */
    protected function scrapeLocation(string $location, Country $country): void
    {
        $page = 1;
        $totalPages = null;

        do {
            $this->info("Fetching page {$page}".($totalPages ? " of {$totalPages}" : ''));

            $response = $this->fetchPage($location, $page);

            if (! $response || ! isset($response['results'])) {
                $this->error("Failed to fetch data from page {$page}");

                return;
            }

            if ($totalPages === null && isset($response['pagination']['totalPages'])) {
                $totalPages = $response['pagination']['totalPages'];
                $this->info("Total pages to scrape: {$totalPages}");
            }

            // Process results
            $this->processResults($response['results'], $country);

            $page++;

            // Prevent making too many requests too quickly
            if (! app()->environment('testing')) {
                sleep(1);
            }

        } while ((! $totalPages || $page <= $totalPages));

        $this->info("Scraping completed for location: {$location}!");
    }

    /**
     * Fetch a page of results from the Treatwell API
     */
    protected function fetchPage(string $location, int $page): ?array
    {
        $url = 'https://www.treatwell.lt/api/v1/page/browse';

        try {
            $response = Http::get($url, [
                'page' => $page,
                'currentBrowseUri' => "/salonai/kur-{$location}/",
            ]);

            $this->info("Fetching: {$url}?page={$page}&currentBrowseUri=/salonai/kur-{$location}/");

            if ($response->successful()) {
                $data = $response->json();

                // Log pagination information
                if (isset($data['pagination'])) {
                    $pagination = $data['pagination'];
                    $this->info("Pagination: Page {$pagination['page']} of {$pagination['totalPages']}, showing {$pagination['from']}-".
                        ($pagination['from'] + count($data['results']) - 1)." of {$pagination['totalElements']} venues");
                }

                return $data;
            } else {
                $this->error('API request failed with status: '.$response->status());

                return null;
            }
        } catch (\Exception $e) {
            $this->error('Exception occurred: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Process venue results from the API
     */
    protected function processResults(array $results, Country $country): void
    {
        foreach ($results as $result) {
            if ($result['type'] !== 'venue' || ! isset($result['data'])) {
                continue;
            }

            $venueData = $result['data'];

            $persistVenue = function () use ($venueData, $country) {
                // Create or update the venue
                $venue = $this->processVenue($venueData);

                // Process location data
                if (isset($venueData['location'])) {
                    $this->processLocation($venue, $venueData['location'], $country);
                }

                // Process rating data
                if (isset($venueData['rating'])) {
                    $this->processRating($venue, $venueData['rating']);
                }

                // Process opening hours
                if (isset($venueData['openingHours'])) {
                    $this->processOpeningHours($venue, $venueData['openingHours']);
                }

                // Process images
                if (isset($venueData['images'])) {
                    $this->processImages($venue, $venueData['images'], $venueData['primaryImage'] ?? null);
                } elseif (isset($venueData['primaryImage'])) {
                    $this->processImages($venue, [$venueData['primaryImage']], $venueData['primaryImage']);
                }

                // Process treatments/menu highlights
                if (isset($venueData['menuHighlights'])) {
                    $this->processTreatments($venue, $venueData['menuHighlights']);
                }
            };

            if (app()->environment('testing')) {
                $persistVenue();
            } else {
                DB::transaction($persistVenue);
            }

            $this->line("Processed venue: {$venueData['name']}");
        }
    }

    /**
     * Display stats about the database after scraping
     */
    protected function displayStats(): void
    {
        $this->newLine();
        $this->info('Database Stats:');
        $this->info('  Countries: '.Country::count());
        $this->info('  Cities: '.City::count());
        $this->info('  Venues: '.Venue::count());
        $this->info('  Locations: '.Location::count());
        $this->info('  Ratings: '.Rating::count());
        $this->info('  Opening Hours: '.OpeningHour::count());
        $this->info('  Images: '.Image::count());
        $this->info('  Treatments: '.Treatment::count());
    }

    /**
     * Process venue data
     */
    protected function processVenue(array $venueData): Venue
    {
        $slug = $this->buildVenueSlug($venueData);

        $venue = Venue::updateOrCreate(
            ['external_id' => $venueData['id']],
            [
                'name' => $venueData['name'],
                'description' => $venueData['description'] ?? null,
                'type_id' => $venueData['type']['id'] ?? null,
                'type_name' => $venueData['type']['name'] ?? null,
                'normalised_name' => $venueData['type']['normalisedName'] ?? null,
                'desktop_uri' => $venueData['uri']['desktopUri'] ?? null,
                'mobile_uri' => $venueData['uri']['mobileUri'] ?? null,
                'app_uri' => $venueData['uri']['appUri'] ?? null,
                'is_new_venue' => $venueData['newVenue'] ?? false,
                'raw_data' => $venueData,
                'slug' => $slug,
                'url' => $this->buildVenueUrl($venueData, $slug),
            ]
        );

        return $venue;
    }

    protected function buildVenueSlug(array $venueData): string
    {
        $slug = '';

        $desktopUri = $venueData['uri']['desktopUri'] ?? null;
        if (is_string($desktopUri) && $desktopUri !== '') {
            $segments = array_values(array_filter(explode('/', trim($desktopUri, '/'))));
            if (! empty($segments)) {
                $slug = end($segments);
            }
        }

        if ($slug === '' && isset($venueData['name'])) {
            $slug = Str::slug($venueData['name']);
        }

        if ($slug === '' && isset($venueData['id'])) {
            $slug = 'venue-'.(string) $venueData['id'];
        }

        return $slug !== '' ? $slug : Str::uuid()->toString();
    }

    protected function buildVenueUrl(array $venueData, ?string $slug = null): string
    {
        $desktopUri = $venueData['uri']['desktopUri'] ?? null;

        if (is_string($desktopUri) && $desktopUri !== '') {
            if (str_starts_with($desktopUri, 'http://') || str_starts_with($desktopUri, 'https://')) {
                return $desktopUri;
            }

            return rtrim('https://www.treatwell.lt', '/').'/'.ltrim($desktopUri, '/');
        }

        $slug ??= $this->buildVenueSlug($venueData);

        return rtrim('https://www.treatwell.lt', '/').'/'.$slug;
    }

    protected function buildCitySlug(array $cityData): string
    {
        if (! empty($cityData['normalisedName'])) {
            return $cityData['normalisedName'];
        }

        if (! empty($cityData['id'])) {
            return (string) $cityData['id'];
        }

        if (! empty($cityData['name'])) {
            return Str::slug($cityData['name']);
        }

        return Str::uuid()->toString();
    }

    /**
     * Process location data
     */
    protected function processLocation(Venue $venue, array $locationData, Country $country): void
    {
        // Process city data if available
        $city = null;
        if (isset($locationData['tree'])) {
            $cityData = $locationData['tree'];

            // Check if we have subregion information
            $subregion = null;
            $isMainCity = true;
            $mainCityId = null;

            // Extract subregion from the name if format is "Subregion, City"
            $cityName = $cityData['name'];
            if (str_contains($cityName, ',')) {
                [$subregionName, $mainCityName] = array_map('trim', explode(',', $cityName, 2));

                // The part after the comma is considered the main city
                $isMainCity = false;

                // Find the main city by name
                $mainCity = City::where('name', $mainCityName)
                    ->where('is_main_city', true)
                    ->first();

                if ($mainCity) {
                    $mainCityId = $mainCity->id;
                }

                $subregion = $subregionName;
            } else {
                // This is likely a main city
                $isMainCity = true;
            }

            $city = City::updateOrCreate(
                ['entity_id' => $cityData['id']],
                [
                    'country_id' => $country->id,
                    'name' => $cityData['name'],
                    'slug' => $this->buildCitySlug($cityData),
                    'normalised_name' => $cityData['normalisedName'] ?? null,
                    'subregion' => $subregion,
                    'latitude' => $cityData['point']['lat'] ?? null,
                    'longitude' => $cityData['point']['lon'] ?? null,
                    'type' => $cityData['type'] ?? 'city',
                    'is_main_city' => $isMainCity,
                    'main_city_id' => $mainCityId,
                    'radius_distance' => $cityData['radius']['distance'] ?? null,
                    'radius_unit' => $cityData['radius']['distanceUnit'] ?? null,
                ]
            );

            // If this is a new main city, look for any existing subregions that should point to it
            if ($isMainCity && $city->wasRecentlyCreated) {
                $this->associateSubregions($city);
            }
        }

        // Address data
        $addressLine1 = null;
        $addressLine2 = null;
        $postalCode = null;

        if (isset($locationData['address'])) {
            $address = $locationData['address'];
            $postalCode = $address['postalCode'] ?? null;

            if (isset($address['addressLines']) && is_array($address['addressLines'])) {
                $addressLine1 = $address['addressLines'][0] ?? null;
                $addressLine2 = $address['addressLines'][1] ?? null;
            }
        }

        // Create or update location
        Location::updateOrCreate(
            ['venue_id' => $venue->id],
            [
                'city_id' => $city ? $city->id : null,
                'postal_code' => $postalCode,
                'address_line1' => $addressLine1,
                'address_line2' => $addressLine2,
                'latitude' => $locationData['point']['lat'] ?? null,
                'longitude' => $locationData['point']['lon'] ?? null,
                'map_zoom' => $locationData['map']['zoom'] ?? null,
            ]
        );
    }

    /**
     * Associate existing subregions with a main city
     */
    protected function associateSubregions(City $mainCity): void
    {
        // Look for cities that might be subregions of this main city
        $potentialSubregions = City::where('name', 'like', "%, {$mainCity->name}")
            ->where('is_main_city', false)
            ->whereNull('main_city_id')
            ->get();

        foreach ($potentialSubregions as $subregion) {
            $this->info("Associating subregion {$subregion->name} with main city {$mainCity->name}");
            $subregion->update(['main_city_id' => $mainCity->id]);
        }
    }

    /**
     * Process rating data
     */
    protected function processRating(Venue $venue, array $ratingData): void
    {
        $cleanlinessAvg = null;
        $cleanlinessCount = 0;
        $staffAvg = null;
        $staffCount = 0;
        $atmosphereAvg = null;
        $atmosphereCount = 0;

        if (isset($ratingData['dimensions']) && is_array($ratingData['dimensions'])) {
            foreach ($ratingData['dimensions'] as $dimension) {
                if ($dimension['name'] === 'Švara') {
                    $cleanlinessAvg = $dimension['average'] ?? null;
                    $cleanlinessCount = $dimension['count'] ?? 0;
                } elseif ($dimension['name'] === 'Personalas') {
                    $staffAvg = $dimension['average'] ?? null;
                    $staffCount = $dimension['count'] ?? 0;
                } elseif ($dimension['name'] === 'Atmosfera') {
                    $atmosphereAvg = $dimension['average'] ?? null;
                    $atmosphereCount = $dimension['count'] ?? 0;
                }
            }
        }

        Rating::updateOrCreate(
            ['venue_id' => $venue->id],
            [
                'weighted_average' => $ratingData['weightedAverage'] ?? null,
                'count' => $ratingData['count'] ?? 0,
                'cleanliness_avg' => $cleanlinessAvg,
                'cleanliness_count' => $cleanlinessCount,
                'staff_avg' => $staffAvg,
                'staff_count' => $staffCount,
                'atmosphere_avg' => $atmosphereAvg,
                'atmosphere_count' => $atmosphereCount,
                'display_average' => $ratingData['displayAverage'] ?? null,
            ]
        );
    }

    /**
     * Process opening hours data
     */
    protected function processOpeningHours(Venue $venue, array $openingHoursData): void
    {
        // First remove existing opening hours for the venue to avoid duplicates
        $venue->openingHours()->delete();

        // Add new opening hours
        foreach ($openingHoursData as $hour) {
            OpeningHour::create([
                'venue_id' => $venue->id,
                'day_of_week' => $hour['dayOfWeek'] ?? '',
                'opening_time' => $hour['from'] ?? null,
                'closing_time' => $hour['to'] ?? null,
                'is_open' => $hour['open'] ?? false,
            ]);
        }
    }

    /**
     * Process images data
     */
    protected function processImages(Venue $venue, array $imagesData, ?array $primaryImage = null): void
    {
        // First remove existing images for the venue to avoid duplicates
        $venue->images()->delete();

        // Keep track of primary image ID
        $primaryImageId = $primaryImage['id'] ?? null;

        // Add new images
        foreach ($imagesData as $image) {
            Image::create([
                'venue_id' => $venue->id,
                'external_id' => $image['id'] ?? null,
                'uri_small' => $image['uris']['360x240'] ?? null,
                'uri_medium' => $image['uris']['720x480'] ?? null,
                'uri_large' => $image['uris']['1080x720'] ?? null,
                'uri_xlarge' => $image['uris']['1280x800'] ?? null,
                'is_primary' => ($primaryImageId && isset($image['id']) && $image['id'] == $primaryImageId),
            ]);
        }
    }

    /**
     * Process treatments/menu highlights
     */
    protected function processTreatments(Venue $venue, array $treatmentsData): void
    {
        // First remove existing treatments for the venue to avoid duplicates
        $venue->treatments()->delete();

        // Add new treatments
        foreach ($treatmentsData as $treatment) {
            if ($treatment['type'] !== 'treatment' || ! isset($treatment['data'])) {
                continue;
            }

            $data = $treatment['data'];

            // Extract price info
            $minPrice = null;
            $maxPrice = null;

            if (isset($data['priceRange'])) {
                $minPrice = $data['priceRange']['minSalePriceAmount'] ?? null;
                $maxPrice = $data['priceRange']['maxSalePriceAmount'] ?? null;
            }

            // Extract duration info
            $minDuration = null;
            $maxDuration = null;

            if (isset($data['durationRange'])) {
                $minDuration = $data['durationRange']['minDurationMinutes'] ?? null;
                $maxDuration = $data['durationRange']['maxDurationMinutes'] ?? null;
            }

            Treatment::create([
                'venue_id' => $venue->id,
                'external_id' => $data['id'] ?? null,
                'name' => $data['name'] ?? '',
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'min_duration' => $minDuration,
                'max_duration' => $maxDuration,
                'category_id' => $data['primaryTreatmentCategoryId'] ?? null,
                'category_name' => null, // Not available in the provided sample
                'options' => $data['optionGroups'] ?? null,
            ]);
        }
    }
}
