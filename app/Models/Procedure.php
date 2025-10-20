<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Procedure extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * The venues that belong to the procedure.
     */
    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class);
    }

    /**
     * The cities that belong to the procedure.
     */
    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class);
    }

    /**
     * Get the treatments for the procedure.
     */
    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    /**
     * Extract procedure slug from URL
     */
    public static function extractSlugFromUrl(string $url): string
    {
        $pattern = '/procedura-([^\/]+)/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        
        return '';
    }

    /**
     * Scope a query to get procedures with venues count.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithVenuesCount($query)
    {
        return $query->withCount('venues');
    }

    /**
     * Scope a query to get popular procedures.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->withCount('venues')
            ->having('venues_count', '>', 0)
            ->orderByDesc('venues_count')
            ->limit($limit);
    }

    /**
     * Scope a query to get procedures for a specific city.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $cityId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCity($query, $cityId)
    {
        return $query->whereHas('cities', function($q) use ($cityId) {
            $q->where('city_id', $cityId);
        })->orWhereHas('venues', function($q) use ($cityId) {
            $q->whereHas('cities', function($sq) use ($cityId) {
                $sq->where('city_id', $cityId);
            });
        });
    }
} 