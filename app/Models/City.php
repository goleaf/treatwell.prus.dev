<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use CrudTrait;

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
}
