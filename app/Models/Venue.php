<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Venue extends Model
{
    use CrudTrait;

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

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function rating(): BelongsTo
    {
        return $this->belongsTo(Rating::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(OpeningHour::class);
    }
}
