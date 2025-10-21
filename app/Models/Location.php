<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Location extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'external_id',
        'venue_id',
        'city_id',
        'name',
        'postal_code',
        'address_line1',
        'address_line2',
        'address',
        'latitude',
        'longitude',
        'map_zoom',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'map_zoom' => 'integer',
    ];

    /**
     * Get the venue that owns the location.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Get the city that owns the location.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the full address as a string.
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->address_line1;

        if (! empty($this->address_line2)) {
            $address .= ', '.$this->address_line2;
        }

        if (! empty($this->postal_code) && $this->city) {
            $address .= ', '.$this->postal_code.' '.$this->city->name;
        } elseif ($this->city) {
            $address .= ', '.$this->city->name;
        }

        return $address;
    }

    /**
     * Calculate distance from coordinates.
     *
     * @param  string  $unit  K for kilometers, M for miles (default: K)
     */
    public function distanceFrom(float $lat, float $lng, string $unit = 'K'): ?float
    {
        if (! $this->latitude || ! $this->longitude) {
            return null;
        }

        $theta = $this->longitude - $lng;
        $dist = sin(deg2rad($this->latitude)) * sin(deg2rad($lat)) +
                cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        if ($unit == 'K') {
            return $miles * 1.609344;
        } else {
            return $miles;
        }
    }
}
