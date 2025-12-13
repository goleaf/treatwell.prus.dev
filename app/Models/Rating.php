<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rating extends Model
{
    /** @use HasFactory<\Database\Factories\RatingFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'venue_id', 'city_id', 'weighted_average', 'count',
        'cleanliness_avg', 'cleanliness_count',
        'staff_avg', 'staff_count',
        'atmosphere_avg', 'atmosphere_count',
        'display_average', 'reviewer_name', 'reviewer_email',
        'rating', 'comment', 'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'weighted_average' => 'decimal:2',
            'cleanliness_avg' => 'decimal:2',
            'staff_avg' => 'decimal:2',
            'atmosphere_avg' => 'decimal:2',
            'display_average' => 'decimal:2',
            'is_verified' => 'boolean',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Scope to get only verified ratings.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Get overall rating score (0-5 scale).
     */
    public function getOverallRating(): float
    {
        return (float) ($this->display_average ?? $this->weighted_average ?? 0);
    }

    /**
     * Get rating as percentage (0-100%).
     */
    public function getRatingPercentage(): float
    {
        return ($this->getOverallRating() / 5) * 100;
    }

    /**
     * Get star rating display (1-5 stars).
     */
    public function getStarRating(): int
    {
        return (int) round($this->getOverallRating());
    }

    /**
     * Check if rating has detailed breakdown.
     */
    public function hasDetailedBreakdown(): bool
    {
        return $this->cleanliness_avg || $this->staff_avg || $this->atmosphere_avg;
    }

    /**
     * Get average of all detailed ratings.
     */
    public function getDetailedAverage(): float
    {
        $ratings = array_filter([
            $this->cleanliness_avg,
            $this->staff_avg,
            $this->atmosphere_avg,
        ]);

        return $ratings ? array_sum($ratings) / count($ratings) : 0;
    }

    /**
     * Get formatted rating display.
     */
    public function getFormattedRating(): string
    {
        $rating = $this->getOverallRating();

        return number_format($rating, 1).'/5';
    }
}
