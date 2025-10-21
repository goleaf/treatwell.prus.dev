<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Venue extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'external_id',
        'name',
        'description',
        'type_id',
        'type_name',
        'normalised_name',
        'desktop_uri',
        'mobile_uri',
        'app_uri',
        'is_new_venue',
        'raw_data',
        'slug',
        'url',
        'source',
        'address',
        'phone',
        'email',
        'website',
        'latitude',
        'longitude',
        'location_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'raw_data' => 'array',
        'is_new_venue' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Get the location for the venue.
     */
    public function location(): HasOne
    {
        return $this->hasOne(Location::class);
    }

    /**
     * Get the rating for the venue.
     */
    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }

    /**
     * Get the images for the venue.
     */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    /**
     * Get the opening hours for the venue.
     */
    public function openingHours(): HasMany
    {
        return $this->hasMany(OpeningHour::class);
    }

    /**
     * Get the treatments for the venue.
     */
    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    /**
     * The procedures that belong to the venue.
     */
    public function procedures(): BelongsToMany
    {
        return $this->belongsToMany(Procedure::class);
    }

    /**
     * The cities that belong to the venue.
     */
    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class);
    }

    /**
     * Extract slug from URL
     */
    public static function extractSlugFromUrl(string $url): string
    {
        if (preg_match('/\/vieta\/([^\/]+)/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('/\/salonas\/([^\/]+)/', $url, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Get the primary image URL.
     */
    public function getPrimaryImageUrl(): ?string
    {
        $primaryImage = $this->images()
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();

        return $primaryImage?->preferred_url;
    }

    /**
     * Get the venue's average rating.
     */
    public function getAverageRating(): ?float
    {
        return $this->rating?->weighted_average;
    }

    /**
     * Get city name from location or related cities.
     */
    public function getCityName(): string
    {
        if ($this->location && $this->location->city) {
            return $this->location->city->name;
        }

        $city = $this->cities()->first();

        return $city ? $city->name : 'Unknown';
    }

    /**
     * Scope a query to filter venues by city.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|array  $cityId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCity($query, $cityId)
    {
        return $query->whereHas('location', function ($q) use ($cityId) {
            $q->where('city_id', $cityId);
        })->orWhereHas('cities', function ($q) use ($cityId) {
            if (is_array($cityId)) {
                $q->whereIn('city_id', $cityId);
            } else {
                $q->where('city_id', $cityId);
            }
        });
    }

    /**
     * Scope a query to filter venues by procedure.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|array  $procedureId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByProcedure($query, $procedureId)
    {
        return $query->whereHas('procedures', function ($q) use ($procedureId) {
            if (is_array($procedureId)) {
                $q->whereIn('procedure_id', $procedureId);
            } else {
                $q->where('procedure_id', $procedureId);
            }
        });
    }

    /**
     * Retrieve the minimum recorded treatment price for the venue.
     */
    public function getMinimumPriceAttribute(): ?float
    {
        $min = $this->treatments->pluck('min_price')->filter()->min();

        if ($min !== null) {
            return (float) $min;
        }

        $fallback = $this->treatments->pluck('max_price')->filter()->min();

        return $fallback !== null ? (float) $fallback : null;
    }

    /**
     * Retrieve the maximum recorded treatment price for the venue.
     */
    public function getMaximumPriceAttribute(): ?float
    {
        $max = $this->treatments->pluck('max_price')->filter()->max();

        if ($max !== null) {
            return (float) $max;
        }

        $fallback = $this->treatments->pluck('min_price')->filter()->max();

        return $fallback !== null ? (float) $fallback : null;
    }
}
