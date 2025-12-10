<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpeningHour extends Model
{
    /** @use HasFactory<\Database\Factories\OpeningHourFactory> */
    use HasFactory;

    public const DAYS_OF_WEEK = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];

    protected $fillable = [
        'venue_id',
        'city_id',
        'day_of_week',
        'opening_time',
        'closing_time',
        'is_open',
    ];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'opening_time' => 'datetime:H:i',
            'closing_time' => 'datetime:H:i',
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
     * Check if the venue is currently open based on current day and time.
     */
    public function isCurrentlyOpen(): bool
    {
        if (! $this->is_open || ! $this->opening_time || ! $this->closing_time) {
            return false;
        }

        $currentDay = now()->format('l'); // Full day name (Monday, Tuesday, etc.)
        $currentTime = now()->format('H:i');

        return $this->day_of_week === $currentDay
            && $currentTime >= $this->opening_time->format('H:i')
            && $currentTime <= $this->closing_time->format('H:i');
    }

    /**
     * Get formatted opening hours string.
     */
    public function getFormattedHours(): string
    {
        if (! $this->is_open) {
            return 'Closed';
        }

        if (! $this->opening_time || ! $this->closing_time) {
            return 'Hours not specified';
        }

        return sprintf(
            '%s - %s',
            $this->opening_time->format('g:i A'),
            $this->closing_time->format('g:i A')
        );
    }

    /**
     * Scope to get opening hours for a specific day.
     */
    public function scopeForDay($query, string $day)
    {
        return $query->where('day_of_week', $day);
    }

    /**
     * Scope to get only open days.
     */
    public function scopeOpen($query)
    {
        return $query->where('is_open', true);
    }
}
