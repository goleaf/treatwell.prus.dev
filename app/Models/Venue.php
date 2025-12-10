<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Venue extends Model
{
    /** @use HasFactory<\Database\Factories\VenueFactory> */
    use CrudTrait, HasFactory;

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
}
