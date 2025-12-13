<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    /** @use HasFactory<\Database\Factories\CountryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'slug',
        'normalised_name',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * Scope to get only active countries.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get cities count for this country.
     */
    public function getCitiesCount(): int
    {
        return $this->cities()->count();
    }

    /**
     * Get main cities for this country.
     */
    public function getMainCities()
    {
        return $this->cities()->where('is_main_city', true)->get();
    }

    /**
     * Get flag emoji for the country (basic implementation).
     */
    public function getFlagEmoji(): string
    {
        // This is a simple implementation for common countries
        return match (strtoupper($this->code)) {
            'LT' => '🇱🇹',
            'LV' => '🇱🇻',
            'EE' => '🇪🇪',
            'US' => '🇺🇸',
            'GB' => '🇬🇧',
            'DE' => '🇩🇪',
            'FR' => '🇫🇷',
            default => '🏳️',
        };
    }

    /**
     * Get display name with flag.
     */
    public function getDisplayNameWithFlag(): string
    {
        return $this->getFlagEmoji().' '.$this->name;
    }
}
