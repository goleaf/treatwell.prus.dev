<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

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
     * Extract slug from a Treatwell URL.
     * Example: https://www.treatwell.lt/salonai/kur-vilnius-lt/ -> vilnius-lt
     */
    public static function extractSlugFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));

        // Get the last segment and remove 'kur-' prefix if present
        $slug = end($segments);

        // Remove 'kur-' prefix if it exists
        if (str_starts_with($slug, 'kur-')) {
            $slug = substr($slug, 4);
        }

        return $slug;
    }
}
