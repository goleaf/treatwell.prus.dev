<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    /** @use HasFactory<\Database\Factories\CityFactory> */
    use CrudTrait, HasFactory;

    protected function casts(): array
    {
        return [
            'is_main_city' => 'boolean',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'radius_distance' => 'decimal:2',
        ];
    }

    protected $fillable = [
        'name',
        'slug',
        'normalised_name',
        'entity_id',
        'is_main_city',
        'subregion',
        'latitude',
        'longitude',
        'country_id',
        'main_city_id',
        'type',
        'radius_distance',
        'radius_unit',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function procedures(): BelongsToMany
    {
        return $this->belongsToMany(Procedure::class);
    }

    public function mainCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'main_city_id');
    }

    public function subregions(): HasMany
    {
        return $this->hasMany(City::class, 'main_city_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function treatments(): BelongsToMany
    {
        return $this->belongsToMany(Treatment::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(OpeningHour::class);
    }

    /**
     * Extract slug from URL for city processing.
     */
    public static function extractSlugFromUrl(string $url): string
    {
        // Look for pattern like "kur-vilnius" in the URL
        if (preg_match('/kur-([^\/]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Fallback to last segment
        $url = rtrim($url, '/');
        $segments = explode('/', $url);

        return end($segments);
    }

    /**
     * Scope to get only main cities.
     */
    public function scopeMainCities($query)
    {
        return $query->where('is_main_city', true);
    }

    /**
     * Scope to get cities by country.
     */
    public function scopeByCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Get the full display name including country.
     */
    public function getFullDisplayName(): string
    {
        return $this->name.($this->country ? ', '.$this->country->name : '');
    }

    /**
     * Get venues count for this city.
     */
    public function getVenuesCount(): int
    {
        return $this->venues()->count();
    }

    /**
     * Get procedures count for this city.
     */
    public function getProceduresCount(): int
    {
        return $this->procedures()->count();
    }

    // Parsing-related methods

    /**
     * Get API endpoints for this city.
     */
    public function getApiEndpoints(): array
    {
        return [
            'venues' => "/api/cities/{$this->slug}/venues",
            'treatments' => "/api/cities/{$this->slug}/treatments",
            'locations' => "/api/cities/{$this->slug}/locations",
            'services' => "/api/cities/{$this->slug}/services",
        ];
    }

    /**
     * Get parsing status for this city.
     */
    public function getParsingStatus(): string
    {
        $progress = $this->parseProgress()->latest()->first();
        return $progress?->status ?? 'pending';
    }

    /**
     * Mark city as processed.
     */
    public function markAsProcessed(): void
    {
        $this->parseProgress()->updateOrCreate(
            ['city_slug' => $this->slug],
            [
                'status' => 'completed',
                'completed_at' => now(),
                'venues_found' => $this->getVenuesCount(),
                'treatments_found' => $this->getTreatmentsCount(),
            ]
        );
    }

    /**
     * Get treatments count for this city.
     */
    public function getTreatmentsCount(): int
    {
        return Treatment::whereHas('venue', function ($query) {
            $query->where('city_id', $this->id);
        })->count();
    }

    /**
     * Get data completeness percentage for this city.
     */
    public function getDataCompleteness(): float
    {
        $totalFields = 0;
        $completedFields = 0;

        // Check city fields
        $cityFields = ['name', 'slug', 'latitude', 'longitude', 'country_id'];
        foreach ($cityFields as $field) {
            $totalFields++;
            if (!empty($this->$field)) {
                $completedFields++;
            }
        }

        // Check venues data completeness
        $venues = $this->venues;
        foreach ($venues as $venue) {
            $venueCompleteness = $venue->getDataCompleteness();
            $totalFields += 100; // Venue completeness is a percentage
            $completedFields += $venueCompleteness;
        }

        return $totalFields > 0 ? ($completedFields / $totalFields) * 100 : 0;
    }

    /**
     * Get all services available in this city.
     */
    public function getAllServices()
    {
        return $this->services()->active()->get();
    }

    /**
     * Get all images for venues in this city.
     */
    public function getAllImages()
    {
        return $this->images()->get();
    }

    /**
     * Get all ratings for venues in this city.
     */
    public function getAllRatings()
    {
        return $this->ratings()->get();
    }

    /**
     * Get all opening hours for venues in this city.
     */
    public function getAllOpeningHours()
    {
        return $this->openingHours()->get();
    }

    /**
     * Get average rating for all venues in this city.
     */
    public function getAverageRating(): float
    {
        return $this->ratings()->avg('display_average') ?? 0.0;
    }

    /**
     * Get total number of treatments available in this city.
     */
    public function getTotalTreatmentsCount(): int
    {
        return $this->treatments()->count();
    }

    /**
     * Get total number of services available in this city.
     */
    public function getTotalServicesCount(): int
    {
        return $this->services()->count();
    }

    /**
     * Sync all venue-related data with city relationships.
     */
    public function syncVenueRelatedData(): void
    {
        // Update services to have city_id
        $this->venues()->with('services')->get()->each(function ($venue) {
            $venue->services()->update(['city_id' => $this->id]);
        });

        // Update images to have city_id
        $this->venues()->with('images')->get()->each(function ($venue) {
            $venue->images()->update(['city_id' => $this->id]);
        });

        // Update ratings to have city_id
        $this->venues()->with('ratingDetails')->get()->each(function ($venue) {
            if ($venue->ratingDetails) {
                $venue->ratingDetails->update(['city_id' => $this->id]);
            }
        });

        // Update opening hours to have city_id
        $this->venues()->with('openingHours')->get()->each(function ($venue) {
            $venue->openingHours()->update(['city_id' => $this->id]);
        });

        // Sync treatments with city through many-to-many
        $treatmentIds = $this->venues()->with('treatments')->get()
            ->flatMap(function ($venue) {
                return $venue->treatments->pluck('id');
            })->unique();

        $this->treatments()->sync($treatmentIds);
    }

    /**
     * Update city data from API response.
     */
    public function updateFromApiData(array $data): void
    {
        $fillableData = array_intersect_key($data, array_flip($this->fillable));
        $this->update($fillableData);
    }

    /**
     * Relationship to parse progress records.
     */
    public function parseProgress()
    {
        return $this->hasMany(ParseProgress::class, 'city_slug', 'slug');
    }
}
