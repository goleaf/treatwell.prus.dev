<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\Image;
use App\Models\Location;
use App\Models\OpeningHour;
use App\Models\Procedure;
use App\Models\Rating;
use App\Models\Treatment;
use App\Models\Venue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class TreatwellSyncService
{
    private const XSSI_PREFIX = ")]}', ";

    /**
     * Reset all venue related data.
     */
    public function resetData(): void
    {
        DB::transaction(function () {
            DB::table('city_venue')->delete();
            DB::table('procedure_venue')->delete();
            DB::table('city_procedure')->delete();
            if (Schema::hasTable('city_treatment')) {
                DB::table('city_treatment')->delete();
            }

            OpeningHour::query()->delete();
            Image::query()->delete();
            Treatment::query()->delete();
            Rating::query()->delete();
            Location::query()->delete();
            Venue::query()->delete();
            City::query()->delete();
        });
    }

    /**
     * Import venues from the Treatwell API.
     *
     * @param  array<int, string>  $cities
     * @param  array<int, string>  $procedures
     * @return array<string, int>
     */
    public function importFromApi(array $cities, array $procedures = [], int $pageSize = 20, ?int $maxPages = null): array
    {
        $stats = $this->baseStats();

        $cities = $this->normaliseList($cities);
        $procedures = $this->normaliseList($procedures);

        if (empty($cities)) {
            $cities = $this->normaliseList(config('treatwell.default_cities', []));
        }

        $procedureSlugs = empty($procedures) ? [null] : $procedures;

        foreach ($cities as $citySlug) {
            foreach ($procedureSlugs as $procedureSlug) {
                $page = 0;
                $totalPages = null;

                do {
                    $response = $this->fetchApiPage($citySlug, $procedureSlug, $page, $pageSize);
                    $stats['api_requests']++;

                    if ($response === null) {
                        break;
                    }

                    $totalPages = $response['pagination']['totalPages'] ?? $totalPages;
                    $results = $response['results'] ?? [];

                    foreach ($results as $result) {
                        if (($result['type'] ?? null) !== 'venue' || empty($result['data'])) {
                            continue;
                        }

                        $this->storeVenue($result['data'], 'api', $citySlug, $procedureSlug, $stats);
                    }

                    $page++;

                    if ($maxPages !== null && $page >= $maxPages) {
                        break;
                    }
                } while ($totalPages === null || $page < $totalPages);
            }
        }

        return $stats;
    }

    /**
     * Import venues from JSON files.
     *
     * @param  array<int, string>  $paths
     * @return array<string, int>
     */
    public function importFromJsonFiles(array $paths): array
    {
        $stats = $this->baseStats();
        $files = $this->collectJsonFiles($paths);

        foreach ($files as $file) {
            try {
                $contents = File::get($file);
                $payload = $this->decodeJsonPayload($contents);

                if (! $payload) {
                    $stats['files_failed']++;

                    continue;
                }

                $results = $payload['results'] ?? [];

                if (isset($payload['type']) && $payload['type'] === 'venue') {
                    $results[] = $payload;
                }

                foreach ($results as $result) {
                    $data = $result['data'] ?? $result;
                    if (! is_array($data)) {
                        continue;
                    }

                    $this->storeVenue($data, 'json', null, null, $stats);
                }

                $stats['files_processed']++;
            } catch (Throwable $exception) {
                $stats['files_failed']++;
            }
        }

        return $stats;
    }

    /**
     * Fetch and decode a paginated API response.
     */
    private function fetchApiPage(string $citySlug, ?string $procedureSlug, int $page, int $pageSize): ?array
    {
        $browseUri = $this->buildBrowseUri($citySlug, $procedureSlug);
        $query = [
            'page' => $page,
            'pageSize' => $pageSize,
            'currentBrowseUri' => $browseUri,
        ];

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => 'TreatwellSyncBot/1.0',
                ])
                ->get(config('treatwell.api_base_url'), $query);

            if (! $response->successful()) {
                return null;
            }

            return $this->decodeJsonPayload($response->body());
        } catch (Throwable $exception) {
            return null;
        }
    }

    /**
     * Store a single venue record with all related entities.
     *
     * @param  array<string, mixed>  $venueData
     * @param  array<string, int>  $stats
     */
    private function storeVenue(array $venueData, string $source, ?string $citySlug, ?string $procedureSlug, array &$stats): void
    {
        if (! isset($venueData['id']) && ! isset($venueData['external_id'])) {
            return;
        }

        DB::transaction(function () use ($venueData, $source, $citySlug, $procedureSlug, &$stats) {
            $externalId = (string) ($venueData['id'] ?? $venueData['external_id']);
            $name = $venueData['name'] ?? ('Venue '.$externalId);

            $desktopUri = $this->resolveUri($venueData, 'desktop');
            $mobileUri = $this->resolveUri($venueData, 'mobile');
            $appUri = $this->resolveUri($venueData, 'app');
            $url = $desktopUri ?? $mobileUri ?? ($venueData['url'] ?? null);

            $slug = Venue::extractSlugFromUrl($url ?? '') ?: Str::slug($name.'-'.$externalId);

            $payload = [
                'name' => $name,
                'description' => $venueData['description'] ?? null,
                'type_id' => Arr::get($venueData, 'type.id'),
                'type_name' => Arr::get($venueData, 'type.name'),
                'normalised_name' => Arr::get($venueData, 'type.normalisedName'),
                'desktop_uri' => $desktopUri,
                'mobile_uri' => $mobileUri,
                'app_uri' => $appUri,
                'is_new_venue' => (bool) ($venueData['newVenue'] ?? $venueData['is_new_venue'] ?? false),
                'raw_data' => $venueData,
                'slug' => $slug,
                'url' => $url,
                'source' => $source,
                'external_id' => $externalId,
            ];

            $venue = Venue::updateOrCreate(
                ['external_id' => $externalId],
                $payload
            );

            if ($venue->wasRecentlyCreated) {
                $stats['venues_created']++;
            } else {
                $stats['venues_updated']++;
            }

            $city = null;
            if (! empty($venueData['location']) && is_array($venueData['location'])) {
                $city = $this->processLocation($venue, $venueData['location'], $citySlug, $stats);
            } elseif (! empty($venueData['city'])) {
                $city = $this->processCityReference($venueData['city'], $stats);
                if ($city) {
                    $venue->cities()->syncWithoutDetaching($city->id);
                }
            }

            if (! empty($venueData['rating'])) {
                $this->processRating($venue, (array) $venueData['rating']);
                $stats['ratings_upserted']++;
            }

            if (! empty($venueData['openingHours'])) {
                $stats['opening_hours_upserted'] += $this->processOpeningHours($venue, $venueData['openingHours']);
            }

            $images = $venueData['images'] ?? [];
            if (empty($images) && ! empty($venueData['primaryImage'])) {
                $images = [$venueData['primaryImage']];
            }
            if (! empty($images)) {
                $stats['images_upserted'] += $this->processImages($venue, $images, $venueData['primaryImage'] ?? null);
            }

            $menuHighlights = $venueData['menuHighlights'] ?? [];
            if (empty($menuHighlights) && ! empty($venueData['treatments'])) {
                $menuHighlights = $this->normaliseTreatments($venueData['treatments']);
            }
            if (! empty($menuHighlights)) {
                $stats['treatments_upserted'] += $this->processTreatments($venue, $menuHighlights, $city);
            }

            $this->processProcedures($venue, $venueData, $city, $procedureSlug, $stats);
        });
    }

    /**
     * Process location information and return the associated city.
     */
    private function processLocation(Venue $venue, array $locationData, ?string $citySlugOverride, array &$stats): ?City
    {
        $tree = $locationData['tree'] ?? null;
        $citySlug = $citySlugOverride;
        $cityName = null;
        $countryCode = null;
        $latitude = null;
        $longitude = null;

        if (is_array($tree)) {
            $citySlug = $tree['normalisedName'] ?? $citySlug ?? Str::slug($tree['name'] ?? '');
            $cityName = $tree['name'] ?? $citySlug;
            $countryCode = $tree['countryCode'] ?? null;
            $latitude = Arr::get($tree, 'point.lat');
            $longitude = Arr::get($tree, 'point.lon');
        }

        if (! $citySlug && isset($locationData['name'])) {
            $citySlug = Str::slug($locationData['name']);
            $cityName = $locationData['name'];
        }

        if (! $citySlug) {
            return null;
        }

        $country = null;
        if ($countryCode) {
            $country = Country::firstOrCreate(
                ['code' => strtoupper($countryCode)],
                ['name' => strtoupper($countryCode)]
            );
        }

        $city = City::firstOrCreate(
            ['slug' => $citySlug],
            [
                'name' => $cityName ?? Str::title(str_replace('-', ' ', $citySlug)),
                'normalised_name' => $citySlug,
                'country_id' => $country?->id,
            ]
        );

        if ($city->wasRecentlyCreated) {
            $stats['cities_created']++;
        }

        $city->update([
            'country_id' => $country?->id ?? $city->country_id,
            'latitude' => $latitude ?? $city->latitude,
            'longitude' => $longitude ?? $city->longitude,
        ]);

        $addressLines = Arr::get($locationData, 'address.addressLines', []);
        $postalCode = Arr::get($locationData, 'address.postalCode');

        $location = $venue->location()->updateOrCreate(
            [],
            [
                'city_id' => $city->id,
                'postal_code' => $postalCode,
                'address_line1' => $addressLines[0] ?? null,
                'address_line2' => $addressLines[1] ?? null,
                'latitude' => Arr::get($locationData, 'point.lat') ?? Arr::get($locationData, 'coordinates.lat'),
                'longitude' => Arr::get($locationData, 'point.lon') ?? Arr::get($locationData, 'coordinates.lon'),
                'map_zoom' => Arr::get($locationData, 'map.zoom'),
            ]
        );

        $venue->fill([
            'address' => $location->address_line1,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'location_id' => $location->id,
        ])->save();

        $venue->cities()->syncWithoutDetaching($city->id);

        $stats['locations_upserted']++;

        return $city;
    }

    /**
     * Process simple city references from JSON payloads.
     */
    private function processCityReference(array|string $cityData, array &$stats): ?City
    {
        if (is_string($cityData)) {
            $slug = Str::slug($cityData);
            $city = City::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $cityData,
                    'normalised_name' => $slug,
                ]
            );

            if ($city->wasRecentlyCreated) {
                $stats['cities_created']++;
            }

            return $city;
        }

        if (! is_array($cityData)) {
            return null;
        }

        $slug = $cityData['slug'] ?? $cityData['id'] ?? Str::slug($cityData['name'] ?? '');
        if (! $slug) {
            return null;
        }

        $city = City::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $cityData['name'] ?? Str::title(str_replace('-', ' ', $slug)),
                'normalised_name' => $slug,
            ]
        );

        if ($city->wasRecentlyCreated) {
            $stats['cities_created']++;
        }

        return $city;
    }

    /**
     * Attach procedures to the venue and related city.
     */
    private function processProcedures(Venue $venue, array $venueData, ?City $city, ?string $procedureSlug, array &$stats): void
    {
        $procedureName = Arr::get($venueData, 'type.name');
        $procedureSlug = $procedureSlug ?? Arr::get($venueData, 'type.normalisedName');

        if (! $procedureName) {
            return;
        }

        $slug = $procedureSlug ? Str::slug($procedureSlug) : Str::slug($procedureName);

        $procedure = Procedure::firstOrCreate(
            ['slug' => $slug],
            ['name' => $procedureName]
        );

        $venue->procedures()->syncWithoutDetaching($procedure->id);
        $stats['procedures_attached']++;

        if ($city) {
            $city->procedures()->syncWithoutDetaching($procedure->id);
        }
    }

    /**
     * Process rating data for a venue.
     */
    private function processRating(Venue $venue, array $ratingData): void
    {
        $dimensions = $ratingData['dimensions'] ?? [];
        $cleanliness = $this->findDimension($dimensions, 'Švara');
        $staff = $this->findDimension($dimensions, 'Personalas');
        $atmosphere = $this->findDimension($dimensions, 'Atmosfera');

        Rating::updateOrCreate(
            ['venue_id' => $venue->id],
            [
                'weighted_average' => $ratingData['weightedAverage'] ?? $ratingData['average'] ?? null,
                'count' => $ratingData['count'] ?? 0,
                'cleanliness_avg' => $cleanliness['average'] ?? null,
                'cleanliness_count' => $cleanliness['count'] ?? 0,
                'staff_avg' => $staff['average'] ?? null,
                'staff_count' => $staff['count'] ?? 0,
                'atmosphere_avg' => $atmosphere['average'] ?? null,
                'atmosphere_count' => $atmosphere['count'] ?? 0,
                'display_average' => $ratingData['displayAverage'] ?? null,
            ]
        );
    }

    /**
     * Replace opening hour records for the venue.
     */
    private function processOpeningHours(Venue $venue, array $hours): int
    {
        $venue->openingHours()->delete();
        $count = 0;

        foreach ($hours as $hour) {
            $venue->openingHours()->create([
                'day_of_week' => strtolower($hour['dayOfWeek'] ?? ''),
                'opening_time' => $hour['from'] ?? null,
                'closing_time' => $hour['to'] ?? null,
                'is_open' => (bool) ($hour['open'] ?? true),
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Replace image records for the venue.
     */
    private function processImages(Venue $venue, array $images, ?array $primaryImage): int
    {
        $venue->images()->delete();
        $primaryId = $primaryImage['id'] ?? null;
        $count = 0;

        foreach ($images as $image) {
            $uris = $image['uris'] ?? [];
            $venue->images()->create([
                'external_id' => $image['id'] ?? null,
                'uri_small' => $uris['360x240'] ?? ($image['url'] ?? null),
                'uri_medium' => $uris['720x480'] ?? null,
                'uri_large' => $uris['1080x720'] ?? ($uris['960x540'] ?? null),
                'uri_xlarge' => $uris['1280x800'] ?? null,
                'is_primary' => $primaryId && ($image['id'] ?? null) === $primaryId,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Replace treatment records for the venue.
     */
    private function processTreatments(Venue $venue, array $menuHighlights, ?City $city): int
    {
        $venue->treatments()->delete();
        $count = 0;

        foreach ($menuHighlights as $entry) {
            if (($entry['type'] ?? null) !== 'treatment') {
                continue;
            }

            $data = $entry['data'] ?? [];
            if (empty($data)) {
                continue;
            }

            $treatment = $venue->treatments()->create([
                'external_id' => $data['id'] ?? null,
                'name' => $data['name'] ?? 'Treatment',
                'slug' => Str::slug($data['name'] ?? uniqid('treatment_')),
                'min_price' => Arr::get($data, 'priceRange.minSalePriceAmount'),
                'max_price' => Arr::get($data, 'priceRange.maxSalePriceAmount'),
                'min_duration' => Arr::get($data, 'durationRange.minDurationMinutes'),
                'max_duration' => Arr::get($data, 'durationRange.maxDurationMinutes'),
                'category_id' => $data['primaryTreatmentCategoryGroupId'] ?? null,
                'category_name' => $data['primaryTreatmentCategoryGroupName'] ?? null,
                'options' => $data['optionGroups'] ?? null,
            ]);

            if ($city) {
                $city->treatments()->syncWithoutDetaching($treatment->id);
            }

            $count++;
        }

        return $count;
    }

    /**
     * Decode Treatwell JSON, handling the XSSI prefix.
     */
    private function decodeJsonPayload(string $payload): ?array
    {
        $payload = trim($payload);
        if (Str::startsWith($payload, self::XSSI_PREFIX)) {
            $payload = substr($payload, strlen(self::XSSI_PREFIX));
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Build the browse URI for a city/procedure combination.
     */
    private function buildBrowseUri(string $citySlug, ?string $procedureSlug): string
    {
        if ($procedureSlug) {
            return '/salonai/procedura-'.$procedureSlug.'/kur-'.$citySlug.'/';
        }

        return '/salonai/kur-'.$citySlug.'/';
    }

    /**
     * Resolve URIs from various payload shapes.
     */
    private function resolveUri(array $data, string $type): ?string
    {
        return match ($type) {
            'desktop' => Arr::get($data, 'uri.desktopUri') ?? $data['desktopUri'] ?? $data['desktop_uri'] ?? null,
            'mobile' => Arr::get($data, 'uri.mobileUri') ?? $data['mobileUri'] ?? $data['mobile_uri'] ?? null,
            'app' => Arr::get($data, 'uri.appUri') ?? $data['appUri'] ?? $data['app_uri'] ?? null,
            default => null,
        };
    }

    /**
     * Locate a rating dimension by name.
     *
     * @param  array<int, array<string, mixed>>  $dimensions
     * @return array<string, mixed>
     */
    private function findDimension(array $dimensions, string $name): array
    {
        foreach ($dimensions as $dimension) {
            if (($dimension['name'] ?? null) === $name) {
                return $dimension;
            }
        }

        return [];
    }

    /**
     * Normalise treatment payloads from simplified JSON files.
     *
     * @param  array<int, array<string, mixed>>  $treatments
     * @return array<int, array<string, mixed>>
     */
    private function normaliseTreatments(array $treatments): array
    {
        return array_map(function (array $treatment): array {
            return [
                'type' => 'treatment',
                'data' => [
                    'id' => $treatment['id'] ?? null,
                    'name' => $treatment['name'] ?? null,
                    'priceRange' => [
                        'minSalePriceAmount' => $treatment['min_price'] ?? $treatment['price'] ?? null,
                        'maxSalePriceAmount' => $treatment['max_price'] ?? $treatment['price'] ?? null,
                    ],
                    'durationRange' => [
                        'minDurationMinutes' => $treatment['min_duration'] ?? $treatment['duration'] ?? null,
                        'maxDurationMinutes' => $treatment['max_duration'] ?? $treatment['duration'] ?? null,
                    ],
                    'optionGroups' => $treatment['options'] ?? null,
                    'primaryTreatmentCategoryGroupId' => $treatment['category_id'] ?? null,
                    'primaryTreatmentCategoryGroupName' => $treatment['category_name'] ?? null,
                ],
            ];
        }, $treatments);
    }

    /**
     * Gather JSON files from provided paths.
     *
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    private function collectJsonFiles(array $paths): array
    {
        $files = [];

        foreach ($paths as $path) {
            $absolute = $this->resolvePath($path);

            if (! $absolute || ! File::exists($absolute)) {
                continue;
            }

            if (File::isDirectory($absolute)) {
                foreach (File::files($absolute) as $file) {
                    if (Str::endsWith($file->getFilename(), '.json')) {
                        $files[] = $file->getPathname();
                    }
                }
            } elseif (Str::endsWith($absolute, '.json')) {
                $files[] = $absolute;
            }
        }

        return array_values(array_unique($files));
    }

    /**
     * Build the base stats array.
     */
    private function baseStats(): array
    {
        return [
            'venues_created' => 0,
            'venues_updated' => 0,
            'cities_created' => 0,
            'locations_upserted' => 0,
            'procedures_attached' => 0,
            'ratings_upserted' => 0,
            'opening_hours_upserted' => 0,
            'images_upserted' => 0,
            'treatments_upserted' => 0,
            'api_requests' => 0,
            'files_processed' => 0,
            'files_failed' => 0,
        ];
    }

    /**
     * Normalise an array of slugs by trimming empty values.
     *
     * @param  array<int, string>  $values
     * @return array<int, string>
     */
    private function normaliseList(array $values): array
    {
        return array_values(array_filter(array_map(fn ($value) => Str::slug(trim((string) $value)), $values)));
    }

    /**
     * Resolve relative paths against the base path.
     */
    private function resolvePath(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if ($this->isAbsolutePath($path)) {
            return File::exists($path) ? $path : null;
        }

        $normalised = str_replace('\\', '/', $path);

        foreach ($this->candidatePaths($normalised) as $candidate) {
            if ($candidate && File::exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Determine if the provided path is absolute.
     */
    private function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        return Str::startsWith($path, ['/', '\\'])
            || preg_match('/^[A-Z]:\\\\/i', $path) === 1;
    }

    /**
     * Build candidate filesystem locations for a relative path input.
     *
     * @return array<int, string>
     */
    private function candidatePaths(string $path): array
    {
        $candidates = [];

        if (Str::startsWith($path, 'storage/')) {
            $relative = ltrim(Str::after($path, 'storage/'), '/');

            if ($relative !== '') {
                if (Str::startsWith($relative, 'app/')) {
                    $suffix = ltrim(Str::after($relative, 'app/'), '/');

                    if ($suffix !== '') {
                        $candidates[] = storage_path('app/private/'.$suffix);
                        $candidates[] = storage_path('app/public/'.$suffix);
                        $candidates[] = storage_path('app/'.$suffix);
                    }
                }

                $candidates[] = storage_path($relative);
            }
        }

        $candidates[] = base_path($path);

        return array_values(array_unique(array_filter($candidates)));
    }
}
