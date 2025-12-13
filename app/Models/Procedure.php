<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Procedure extends Model
{
    /** @use HasFactory<\Database\Factories\ProcedureFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class);
    }

    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class);
    }

    /**
     * Extract slug from URL for procedure processing.
     */
    public static function extractSlugFromUrl(string $url): string
    {
        // Look for pattern like "procedura-massage" in the URL
        if (preg_match('/procedura-([^\/]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Fallback to last segment
        $url = rtrim($url, '/');
        $segments = explode('/', $url);

        return end($segments);
    }

    /**
     * Scope to get only active procedures.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter procedures by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get venues count for this procedure.
     */
    public function getVenuesCount(): int
    {
        return $this->venues()->count();
    }

    /**
     * Get cities count for this procedure.
     */
    public function getCitiesCount(): int
    {
        return $this->cities()->count();
    }

    /**
     * Get available cities for this procedure.
     */
    public function getAvailableCities()
    {
        return $this->cities()->where('is_active', true)->get();
    }
}
