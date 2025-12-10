<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venue extends Model
{
    /** @use HasFactory<\Database\Factories\VenueFactory> */
    use CrudTrait, HasFactory, SoftDeletes;

    protected $fillable = [
        'city_id', 'external_id', 'name', 'slug', 'description', 'address',
        'type_id', 'type_name', 'normalised_name', 'desktop_uri', 'mobile_uri', 'app_uri',
        'is_new_venue', 'raw_data', 'latitude', 'longitude', 'phone', 'email', 'website',
        'opening_hours', 'rating', 'rating_count', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'rating' => 'decimal:2',
            'is_active' => 'boolean',
            'is_new_venue' => 'boolean',
            'raw_data' => 'array',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function ratingDetails(): HasOne
    {
        return $this->hasOne(Rating::class);
    }

    public function location(): HasOne
    {
        return $this->hasOne(Location::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(OpeningHour::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function procedures(): BelongsToMany
    {
        return $this->belongsToMany(Procedure::class);
    }

    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class);
    }

    /**
     * Extract slug from URL for venue processing.
     */
    public static function extractSlugFromUrl(string $url): string
    {
        // Remove trailing slash and get the last segment
        $url = rtrim($url, '/');
        $segments = explode('/', $url);
        $lastSegment = end($segments);

        // If the last segment is 'salonas', get the previous one
        if ($lastSegment === 'salonas' && count($segments) > 1) {
            return $segments[count($segments) - 2];
        }

        return $lastSegment;
    }

    /**
     * Get the primary image URL for the venue.
     */
    public function getPrimaryImageUrl(): ?string
    {
        $primaryImage = $this->images()->where('is_primary', true)->first();

        if ($primaryImage) {
            return $primaryImage->uri_large ?? $primaryImage->uri_medium ?? $primaryImage->uri_small ?? $primaryImage->path;
        }

        // Fallback to first image if no primary image is set
        $firstImage = $this->images()->first();

        return $firstImage ? ($firstImage->uri_large ?? $firstImage->uri_medium ?? $firstImage->uri_small ?? $firstImage->path) : null;
    }

    /**
     * Get the average rating for the venue.
     */
    public function getAverageRating(): float
    {
        if ($this->ratingDetails) {
            return (float) $this->ratingDetails->display_average ?? 0.0;
        }

        return (float) $this->rating ?? 0.0;
    }

    /**
     * Get the city name for the venue.
     */
    public function getCityName(): ?string
    {
        return $this->city?->name;
    }

    /**
     * Scope to filter venues by city.
     */
    public function scopeByCity($query, $cityId)
    {
        return $query->whereHas('cities', function ($q) use ($cityId) {
            $q->where('cities.id', $cityId);
        });
    }

    /**
     * Scope to filter venues by procedure.
     */
    public function scopeByProcedure($query, $procedureId)
    {
        return $query->whereHas('procedures', function ($q) use ($procedureId) {
            $q->where('procedures.id', $procedureId);
        });
    }

    /**
     * Scope to get only active venues.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if the venue is currently open.
     */
    public function isCurrentlyOpen(): bool
    {
        $currentDay = now()->format('l');
        $currentTime = now()->format('H:i');

        $todayHours = $this->openingHours()
            ->where('day_of_week', $currentDay)
            ->where('is_open', true)
            ->first();

        if (! $todayHours || ! $todayHours->opening_time || ! $todayHours->closing_time) {
            return false;
        }

        return $currentTime >= $todayHours->opening_time->format('H:i')
            && $currentTime <= $todayHours->closing_time->format('H:i');
    }

    /**
     * Get formatted address for the venue.
     */
    public function getFormattedAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->getCityName(),
        ]);

        return implode(', ', $parts);
    }
}
