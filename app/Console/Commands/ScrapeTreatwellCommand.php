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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ScrapeTreatwellCommand extends Command
{
    protected $signature = 'scrape:treatwell
                            {--city= : Scrape specific city by slug}
                            {--all-cities : Scrape all cities}
                            {--json-dir= : Process JSON files from directory}
                            {--limit-pages= : Limit pages per city}
                            {--skip-api : Skip API scraping, only process JSON files}';

    protected $description = 'Unified command to scrape Treatwell data from API and/or process JSON files';

    protected array $cities = [
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

    protected array $stats = [
        'venues_processed' => 0,
        'venues_created' => 0,
        'venues_updated' => 0,
        'cities_created' => 0,
        'locations_created' => 0,
        'treatments_created' => 0,
        'ratings_created' => 0,
        'images_created' => 0,
        'opening_hours_created' => 0,
        'json_files_processed' => 0,
        'json_files_failed' => 0,
        'api_pages_fetched' => 0,
    ];

    public function handle(): int
    {
        $this->info('Starting unified Treatwell scraping...');

        // Setup Lithuania as the main country
        $country = Country::firstOrCreate(
            ['code' => 'LT'],
            [
                'name' => 'Lithuania',
                'slug' => 'lithuania',
            ]
        );

        $this->info("Country setup complete. Country ID: {$country->id}");

        // Process JSON files if directory provided
        if ($jsonDir = $this->option('json-dir')) {
            $this->processJsonDirectory($jsonDir, $country);
        } elseif ($this->option('skip-api')) {
            $this->error('--skip-api requires --json-dir to be specified');

            return 1;
        }

        // Process API scraping if not skipped
        if (! $this->option('skip-api')) {
            if ($this->option('all-cities')) {
                $this->scrapeAllCities($country);
            } elseif ($city = $this->option('city')) {
                $this->scrapeLocation($city, $country);
            } else {
                $this->scrapeAllCities($country);
            }
        }

        $this->displayStats();
        $this->info('Scraping completed!');

        return 0;
    }

    protected function scrapeAllCities(Country $country): void
    {
        $this->info('Scraping all cities...');
        $this->newLine();

        // Try to discover additional cities from API
        $this->discoverCities();

        foreach ($this->cities as $index => $location) {
            $this->info('============================');
            $this->info('Processing city '.($index + 1).' of '.count($this->cities).": {$location}");

            $this->scrapeLocation($location, $country);

            if ($index < count($this->cities) - 1) {
                $this->info('Waiting before moving to the next city...');
                sleep(5);
            }
        }
    }

    protected function discoverCities(): void
    {
        try {
            $response = Http::get('https://www.treatwell.lt/api/v1/page/browse', [
                'page' => '1',
                'currentBrowseUri' => '/salonai/kur-lietuva/',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['filters']) && isset($data['filters']['location']) && isset($data['filters']['location']['options'])) {
                    foreach ($data['filters']['location']['options'] as $option) {
                        if (isset($option['normalisedName']) && ! in_array($option['normalisedName'], $this->cities)) {
                            $this->cities[] = $option['normalisedName'];
                            $this->info("Discovered city: {$option['normalisedName']}");
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->warn('Could not discover additional cities: '.$e->getMessage());
        }
    }

    protected function scrapeLocation(string $location, Country $country): void
    {
        $page = 1;
        $totalPages = null;
        $consecutiveErrors = 0;
        $maxConsecutiveErrors = 3;
        $limitPages = $this->option('limit-pages') ? (int) $this->option('limit-pages') : null;

        do {
            $this->info("Fetching page {$page}".($totalPages ? " of {$totalPages}" : ''));

            $response = $this->fetchPage($location, $page);

            if (! $response || ! isset($response['results'])) {
                $consecutiveErrors++;
                $this->error("Failed to fetch data from page {$page} (Error count: {$consecutiveErrors})");

                if ($consecutiveErrors >= $maxConsecutiveErrors) {
                    $this->error("Too many consecutive errors. Stopping scraping for location: {$location}");
                    break;
                }

                $page++;
                sleep(2);

                continue;
            }

            $consecutiveErrors = 0;
            $this->stats['api_pages_fetched']++;

            if ($totalPages === null && isset($response['pagination']['totalPages'])) {
                $totalPages = $response['pagination']['totalPages'];
                $this->info("Total pages to scrape: {$totalPages}");

                if ($limitPages && $limitPages > 0) {
                    $totalPages = min($totalPages, $limitPages);
                    $this->info("Limited to {$totalPages} pages");
                }
            }

            $this->processResults($response['results'], $country);

            $page++;

            if ($limitPages && $page > $limitPages) {
                break;
            }

            sleep(1);

        } while ((! $totalPages || $page <= $totalPages));
    }

    protected function fetchPage(string $location, int $page): ?array
    {
        $url = 'https://www.treatwell.lt/api/v1/page/browse';

        try {
            $response = Http::get($url, [
                'page' => $page,
                'currentBrowseUri' => "/salonai/kur-{$location}/",
            ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                $this->error('API request failed with status: ' . $response->status());

                return null;
            }
        } catch (\Exception $e) {
            $this->error('Exception occurred: ' . $e->getMessage());

            return null;
        }
    }

    protected function processResults(array $results, Country $country): void
    {
        foreach ($results as $result) {
            if ($result['type'] !== 'venue' || ! isset($result['data'])) {
                continue;
            }

            $venueData = $result['data'];
            
            // Process City first to get city_id
            $city = null;
            if (isset($venueData['location'])) {
                $city = $this->processCity($venueData['location'], $country);
            }

            try {
                $venue = $this->processVenue($venueData, $city ? $city->id : null);

                if (isset($venueData['location'])) {
                    $this->processVenueLocation($venue, $venueData['location'], $city);
                }

                if (isset($venueData['rating'])) {
                    $this->processRating($venue, $venueData['rating']);
                }

                if (isset($venueData['openingHours'])) {
                    $this->processOpeningHours($venue, $venueData['openingHours']);
                }

                if (isset($venueData['images'])) {
                    $this->processImages($venue, $venueData['images']);
                }

                if (isset($venueData['menuHighlights'])) {
                    $this->processTreatments($venue, $venueData['menuHighlights']);
                }
                
                $this->info("Venue processed: {$venue->name}");
                $this->stats['venues_processed']++;

            } catch (\Exception $e) {
                $this->error("Error processing venue {$venueData['name']}: " . $e->getMessage());
            }
        }
    }

    protected function processVenue(array $venueData, ?int $cityId): Venue
    {
        $address = null;
        if (isset($venueData['location']['address']['addressLines'])) {
            $address = implode(', ', $venueData['location']['address']['addressLines']);
        }

        $venue = Venue::updateOrCreate(
            ['external_id' => $venueData['id']],
            [
                'city_id' => $cityId,
                'name' => $venueData['name'],
                'address' => $address,
                'description' => $venueData['description'] ?? null,
                'type_id' => $venueData['type']['id'] ?? null,
                'type_name' => $venueData['type']['name'] ?? null,
                'normalised_name' => $venueData['type']['normalisedName'] ?? null,
                'desktop_uri' => $venueData['uri']['desktopUri'] ?? null,
                'mobile_uri' => $venueData['uri']['mobileUri'] ?? null,
                'app_uri' => $venueData['uri']['appUri'] ?? null,
                'is_new_venue' => $venueData['newVenue'] ?? false,
                'raw_data' => $venueData,
                'slug' => $this->generateSlug($venueData['name']),
            ]
        );

        if ($venue->wasRecentlyCreated) {
            $this->stats['venues_created']++;
        } else {
            $this->stats['venues_updated']++;
        }

        return $venue;
    }

    protected function processCity(array $locationData, Country $country): ?City
    {
        if (! isset($locationData['tree'])) {
            return null;
        }

        $cityData = $locationData['tree'];
        $subregion = null;
        $isMainCity = true;
        $mainCityId = null;

        $cityName = $cityData['name'];
        if (str_contains($cityName, ',')) {
            [$subregionName, $mainCityName] = array_map('trim', explode(',', $cityName, 2));
            $isMainCity = false;

            $mainCity = City::where('name', $mainCityName)
                ->where('is_main_city', true)
                ->first();

            if ($mainCity) {
                $mainCityId = $mainCity->id;
            }

            $subregion = $subregionName;
        }

        $city = City::updateOrCreate(
            ['entity_id' => $cityData['id']],
            [
                'country_id' => $country->id,
                'name' => $cityData['name'],
                'normalised_name' => $cityData['normalisedName'] ?? null,
                'subregion' => $subregion,
                'latitude' => $cityData['point']['lat'] ?? null,
                'longitude' => $cityData['point']['lon'] ?? null,
                'type' => $cityData['type'] ?? 'city',
                'is_main_city' => $isMainCity,
                'main_city_id' => $mainCityId,
                'radius_distance' => $cityData['radius']['distance'] ?? null,
                'radius_unit' => $cityData['radius']['distanceUnit'] ?? null,
                'slug' => $this->generateCitySlug($cityData['name']),
            ]
        );

        if ($city->wasRecentlyCreated) {
            $this->stats['cities_created']++;
        }

        if ($isMainCity && $city->wasRecentlyCreated) {
            $this->associateSubregions($city);
        }

        return $city;
    }

    protected function processVenueLocation(Venue $venue, array $locationData, ?City $city): void
    {
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

        $location = Location::updateOrCreate(
            ['venue_id' => $venue->id],
            [
                'city_id' => $city ? $city->id : null,
                'name' => $venue->name ?? 'Location',
                'postal_code' => $postalCode,
                'address_line1' => $addressLine1,
                'address_line2' => $addressLine2,
                'latitude' => $locationData['point']['lat'] ?? null,
                'longitude' => $locationData['point']['lon'] ?? null,
                'map_zoom' => $locationData['map']['zoom'] ?? null,
            ]
        );

        if ($location->wasRecentlyCreated) {
            $this->stats['locations_created']++;
        }
    }

    protected function associateSubregions(City $mainCity): void
    {
        $potentialSubregions = City::where('name', 'like', "%, {$mainCity->name}")
            ->where('is_main_city', false)
            ->whereNull('main_city_id')
            ->get();

        foreach ($potentialSubregions as $subregion) {
            $this->info("Associating subregion {$subregion->name} with main city {$mainCity->name}");
            $subregion->update(['main_city_id' => $mainCity->id]);
        }
    }

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

        $this->stats['ratings_created']++;
    }

    protected function processOpeningHours(Venue $venue, array $openingHoursData): void
    {
        $venue->openingHours()->delete();

        foreach ($openingHoursData as $hour) {
            OpeningHour::create([
                'venue_id' => $venue->id,
                'day_of_week' => $hour['dayOfWeek'] ?? '',
                'opening_time' => $hour['from'] ?? null,
                'closing_time' => $hour['to'] ?? null,
                'is_open' => $hour['open'] ?? false,
            ]);
            $this->stats['opening_hours_created']++;
        }
    }

    protected function processImages(Venue $venue, array $imagesData, ?array $primaryImage = null): void
    {
        $venue->images()->delete();

        $primaryImageId = $primaryImage['id'] ?? null;

        foreach ($imagesData as $image) {
            Image::create([
                'imageable_type' => Venue::class,
                'imageable_id' => $venue->id,
                'external_id' => $image['id'] ?? null,
                'uri_small' => $image['uris']['360x240'] ?? null,
                'uri_medium' => $image['uris']['720x480'] ?? null,
                'uri_large' => $image['uris']['1080x720'] ?? null,
                'uri_xlarge' => $image['uris']['1280x800'] ?? null,
                'is_primary' => ($primaryImageId && isset($image['id']) && $image['id'] == $primaryImageId),
            ]);
            $this->stats['images_created']++;
        }
    }

    protected function processTreatments(Venue $venue, array $treatmentsData): void
    {
        $venue->treatments()->delete();

        foreach ($treatmentsData as $treatment) {
            if ($treatment['type'] !== 'treatment' || ! isset($treatment['data'])) {
                continue;
            }

            $data = $treatment['data'];

            $minPrice = null;
            $maxPrice = null;

            if (isset($data['priceRange'])) {
                $minPrice = $data['priceRange']['minSalePriceAmount'] ?? null;
                $maxPrice = $data['priceRange']['maxSalePriceAmount'] ?? null;
            }

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
                'slug' => $this->generateSlug($data['name'] ?? ''),
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'min_duration' => $minDuration,
                'max_duration' => $maxDuration,
                'category_id' => $data['primaryTreatmentCategoryId'] ?? null,
                'category_name' => null,
                'options' => $data['optionGroups'] ?? null,
            ]);

            $this->stats['treatments_created']++;
        }
    }

    protected function processJsonDirectory(string $directory, Country $country): void
    {
        if (! File::exists($directory)) {
            $this->error("Directory does not exist: {$directory}");

            return;
        }

        $jsonFiles = File::files($directory);
        $jsonFiles = array_filter($jsonFiles, fn ($file) => $file->getExtension() === 'json');

        $totalFiles = count($jsonFiles);
        $this->info("Processing {$totalFiles} JSON files from {$directory}...");

        $progressBar = $this->output->createProgressBar($totalFiles);
        $progressBar->start();

        foreach ($jsonFiles as $file) {
            try {
                $this->processJsonFile($file->getPathname(), $country);
                $this->stats['json_files_processed']++;
            } catch (\Exception $e) {
                $this->stats['json_files_failed']++;
                $this->error("\nFailed to process {$file->getFilename()}: ".$e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    protected function processJsonFile(string $filePath, Country $country): void
    {
        $content = File::get($filePath);
        $data = json_decode($content, true);

        if (! $data) {
            return;
        }

        $venues = isset($data['venues']) ? $data['venues'] : [$data];

        foreach ($venues as $venueData) {
            try {
                DB::transaction(function () use ($venueData, $country) {
                    $venue = $this->processVenueFromJson($venueData);

                    if (isset($venueData['location'])) {
                        $this->processLocationFromJson($venue, $venueData['location'], $country);
                    }

                    if (isset($venueData['rating'])) {
                        $this->processRatingFromJson($venue, $venueData['rating']);
                    }

                    if (isset($venueData['opening_hours']) || isset($venueData['openingHours'])) {
                        $this->processOpeningHoursFromJson($venue, $venueData['opening_hours'] ?? $venueData['openingHours']);
                    }

                    if (isset($venueData['images'])) {
                        $this->processImagesFromJson($venue, $venueData['images']);
                    }

                    if (isset($venueData['treatments']) || isset($venueData['services'])) {
                        $this->processTreatmentsFromJson($venue, $venueData['treatments'] ?? $venueData['services']);
                    }
                });
            } catch (\Exception $e) {
                $this->error('Error processing venue from JSON: '.$e->getMessage());
            }
        }
    }

    protected function processVenueFromJson(array $venueData): Venue
    {
        $venue = Venue::updateOrCreate(
            ['external_id' => $venueData['id'] ?? null],
            [
                'name' => $venueData['name'] ?? '',
                'description' => $venueData['description'] ?? null,
                'slug' => $this->generateSlug($venueData['name'] ?? ''),
                'desktop_uri' => $venueData['url'] ?? $venueData['desktop_uri'] ?? null,
                'raw_data' => $venueData,
            ]
        );

        if ($venue->wasRecentlyCreated) {
            $this->stats['venues_created']++;
        } else {
            $this->stats['venues_updated']++;
        }

        return $venue;
    }

    protected function processLocationFromJson(Venue $venue, array $locationData, Country $country): void
    {
        $city = null;
        if (isset($locationData['city'])) {
            $cityName = is_array($locationData['city']) ? ($locationData['city']['name'] ?? null) : $locationData['city'];
            if ($cityName) {
                $city = City::firstOrCreate(
                    ['name' => $cityName, 'country_id' => $country->id],
                    ['slug' => $this->generateCitySlug($cityName)]
                );
                if ($city->wasRecentlyCreated) {
                    $this->stats['cities_created']++;
                }
            }
        }

        $location = Location::updateOrCreate(
            ['venue_id' => $venue->id],
            [
                'city_id' => $city ? $city->id : null,
                'name' => $venue->name ?? 'Location',
                'address_line1' => $locationData['address'] ?? $locationData['address_line1'] ?? null,
                'latitude' => $locationData['latitude'] ?? $locationData['lat'] ?? null,
                'longitude' => $locationData['longitude'] ?? $locationData['lon'] ?? null,
            ]
        );

        if ($location->wasRecentlyCreated) {
            $this->stats['locations_created']++;
        }
    }

    protected function processRatingFromJson(Venue $venue, array $ratingData): void
    {
        Rating::updateOrCreate(
            ['venue_id' => $venue->id],
            [
                'weighted_average' => $ratingData['average'] ?? $ratingData['weighted_average'] ?? null,
                'count' => $ratingData['count'] ?? $ratingData['total'] ?? 0,
            ]
        );

        $this->stats['ratings_created']++;
    }

    protected function processOpeningHoursFromJson(Venue $venue, array $openingHoursData): void
    {
        $venue->openingHours()->delete();

        foreach ($openingHoursData as $hour) {
            OpeningHour::create([
                'venue_id' => $venue->id,
                'day_of_week' => $hour['day_of_week'] ?? $hour['day'] ?? '',
                'opening_time' => $hour['open_time'] ?? $hour['open'] ?? null,
                'closing_time' => $hour['close_time'] ?? $hour['close'] ?? null,
                'is_open' => ! ($hour['is_closed'] ?? $hour['closed'] ?? false),
            ]);
            $this->stats['opening_hours_created']++;
        }
    }

    protected function processImagesFromJson(Venue $venue, array $imagesData): void
    {
        $venue->images()->delete();

        foreach ($imagesData as $index => $imageData) {
            Image::create([
                'imageable_type' => Venue::class,
                'imageable_id' => $venue->id,
                'external_id' => $imageData['id'] ?? $imageData['external_id'] ?? null,
                'uri_large' => $imageData['url'] ?? $imageData['uri_large'] ?? null,
                'uri_medium' => $imageData['uri_medium'] ?? null,
                'uri_small' => $imageData['uri_small'] ?? null,
                'is_primary' => $imageData['is_primary'] ?? ($index === 0),
            ]);
            $this->stats['images_created']++;
        }
    }

    protected function processTreatmentsFromJson(Venue $venue, array $treatmentsData): void
    {
        $venue->treatments()->delete();

        foreach ($treatmentsData as $treatmentData) {
            Treatment::create([
                'venue_id' => $venue->id,
                'name' => $treatmentData['name'] ?? $treatmentData['title'] ?? '',
                'slug' => $this->generateSlug($treatmentData['name'] ?? $treatmentData['title'] ?? ''),
                'duration' => $treatmentData['duration'] ?? $treatmentData['duration_minutes'] ?? null,
                'price' => $treatmentData['price'] ?? $treatmentData['price_from'] ?? null,
            ]);
            $this->stats['treatments_created']++;
        }
    }

    protected function generateSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Venue::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    protected function generateCitySlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (City::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    protected function displayStats(): void
    {
        $this->newLine();
        $this->info('===== Scraping Statistics =====');
        $this->info("Venues processed: {$this->stats['venues_processed']}");
        $this->info("  - Created: {$this->stats['venues_created']}");
        $this->info("  - Updated: {$this->stats['venues_updated']}");
        $this->info("Cities created: {$this->stats['cities_created']}");
        $this->info("Locations created: {$this->stats['locations_created']}");
        $this->info("Treatments created: {$this->stats['treatments_created']}");
        $this->info("Ratings created: {$this->stats['ratings_created']}");
        $this->info("Images created: {$this->stats['images_created']}");
        $this->info("Opening hours created: {$this->stats['opening_hours_created']}");
        $this->info("API pages fetched: {$this->stats['api_pages_fetched']}");
        $this->info("JSON files processed: {$this->stats['json_files_processed']}");
        if ($this->stats['json_files_failed'] > 0) {
            $this->warn("JSON files failed: {$this->stats['json_files_failed']}");
        }
        $this->info('================================');

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
}
