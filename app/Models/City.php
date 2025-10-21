<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'country_id',
        'name',
        'normalised_name',
        'entity_id',
        'subregion',
        'latitude',
        'longitude',
        'is_main_city',
        'main_city_id',
        'slug',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_main_city' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Get the country that owns the city.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the locations for the city.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get the main city this subregion belongs to
     */
    public function mainCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'main_city_id');
    }

    /**
     * Get all subregions related to this main city
     */
    public function subregions(): HasMany
    {
        return $this->hasMany(City::class, 'main_city_id');
    }

    /**
     * Scope a query to get top cities with most venues
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function scopeWithMostVenues($query, $limit = 8)
    {
        // SQLite requires GROUP BY before HAVING; prefer exists filter to avoid HAVING
        return $query
            ->whereHas('venues')
            ->withCount('venues')
            ->orderBy('venues_count', 'desc')
            ->limit($limit);
    }

    /**
     * Scope a query to only include main cities
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMainCities($query)
    {
        return $query->where('is_main_city', true);
    }

    /**
     * Get all venues associated with this city (including subregions)
     */
    public function getAllVenues()
    {
        $cityIds = $this->subregions()->pluck('id')->push($this->id);

        return Venue::whereHas('location', function ($query) use ($cityIds) {
            $query->whereIn('city_id', $cityIds);
        })->orWhereHas('cities', function ($query) use ($cityIds) {
            $query->whereIn('city_id', $cityIds);
        });
    }

    /**
     * The venues that belong to the city.
     */
    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class);
    }

    /**
     * The procedures that belong to the city.
     */
    public function procedures(): BelongsToMany
    {
        return $this->belongsToMany(Procedure::class);
    }

    /**
     * The treatments that belong to the city.
     */
    public function treatments(): BelongsToMany
    {
        return $this->belongsToMany(Treatment::class);
    }

    /**
     * Extract city slug from URL
     */
    public static function extractSlugFromUrl(string $url): string
    {
        $pattern = '/kur-([^\/]+)/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
