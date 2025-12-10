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

    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class);
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
}
