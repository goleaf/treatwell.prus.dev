<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Treatment extends Model
{
    /** @use HasFactory<\Database\Factories\TreatmentFactory> */
    use CrudTrait, HasFactory, SoftDeletes;

    protected $fillable = [
        'venue_id', 'external_id', 'name', 'slug', 'description',
        'duration', 'price', 'min_price', 'max_price',
        'min_duration', 'max_duration', 'category_id', 'category_name',
        'category', 'options', 'is_active',
        'booking_enabled', 'advance_booking_days', 'cancellation_hours',
        'buffer_time_before', 'buffer_time_after', 'booking_notes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'min_price' => 'decimal:2',
            'max_price' => 'decimal:2',
            'options' => 'array',
            'is_active' => 'boolean',
            'booking_enabled' => 'boolean',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class);
    }

    /**
     * Scope to get only active treatments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter treatments by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter treatments by price range.
     */
    public function scopeByPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->where(function ($q) use ($minPrice, $maxPrice) {
            $q->whereBetween('price', [$minPrice, $maxPrice])
                ->orWhereBetween('min_price', [$minPrice, $maxPrice])
                ->orWhereBetween('max_price', [$minPrice, $maxPrice]);
        });
    }

    /**
     * Get formatted price display.
     */
    public function getFormattedPrice(): string
    {
        if ($this->min_price && $this->max_price && $this->min_price !== $this->max_price) {
            return "€{$this->min_price} - €{$this->max_price}";
        }

        if ($this->price) {
            return "€{$this->price}";
        }

        return 'Price on request';
    }

    /**
     * Get formatted duration display.
     */
    public function getFormattedDuration(): string
    {
        if ($this->min_duration && $this->max_duration && $this->min_duration !== $this->max_duration) {
            return "{$this->min_duration} - {$this->max_duration} min";
        }

        if ($this->duration) {
            return "{$this->duration} min";
        }

        return 'Duration varies';
    }

    /**
     * Check if treatment has price range.
     */
    public function hasPriceRange(): bool
    {
        return $this->min_price && $this->max_price && $this->min_price !== $this->max_price;
    }

    /**
     * Check if treatment has duration range.
     */
    public function hasDurationRange(): bool
    {
        return $this->min_duration && $this->max_duration && $this->min_duration !== $this->max_duration;
    }

    // Booking-related relationships
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    // Booking-related scopes
    public function scopeBookingEnabled($query)
    {
        return $query->where('booking_enabled', true);
    }

    // Business Logic Methods for booking
    public function isBookingEnabled(): bool
    {
        return $this->booking_enabled && $this->is_active && $this->venue->isBookingEnabled();
    }

    public function getAdvanceBookingDays(): int
    {
        return $this->advance_booking_days ?? $this->venue->booking_advance_days ?? 30;
    }

    public function getCancellationHours(): int
    {
        return $this->cancellation_hours ?? $this->venue->default_cancellation_hours ?? 24;
    }

    public function getAvailableTimeSlots(string $date)
    {
        return $this->timeSlots()
            ->available()
            ->forDate($date)
            ->orderBy('start_time')
            ->get();
    }

    public function getTodaysBookings()
    {
        return $this->bookings()
            ->where('booking_date', now()->toDateString())
            ->with(['user', 'venue'])
            ->orderBy('start_time');
    }

    public function getUpcomingBookings()
    {
        return $this->bookings()
            ->upcoming()
            ->active()
            ->with(['user', 'venue'])
            ->orderBy('booking_date')
            ->orderBy('start_time');
    }

    public function getBookingCount(): int
    {
        return $this->bookings()->count();
    }

    public function getActiveBookingCount(): int
    {
        return $this->bookings()->active()->count();
    }

    public function hasActiveBookings(): bool
    {
        return $this->bookings()->active()->exists();
    }

    // Parsing-related methods

    /**
     * Get parsing metadata for this treatment.
     */
    public function getParsingMetadata(): array
    {
        return [
            'last_parsed' => $this->updated_at,
            'data_completeness' => $this->getDataCompleteness(),
            'has_price_info' => $this->hasPriceInfo(),
            'has_duration_info' => $this->hasDurationInfo(),
            'has_description' => ! empty($this->description),
            'has_category' => ! empty($this->category),
        ];
    }

    /**
     * Update treatment from API data.
     */
    public function updateFromApiData(array $data): void
    {
        // Extract fillable data
        $fillableData = array_intersect_key($data, array_flip($this->fillable));

        // Handle price data
        if (isset($data['pricing'])) {
            $pricing = $data['pricing'];
            $fillableData['price'] = $pricing['price'] ?? null;
            $fillableData['min_price'] = $pricing['min_price'] ?? null;
            $fillableData['max_price'] = $pricing['max_price'] ?? null;
        }

        // Handle duration data
        if (isset($data['duration_info'])) {
            $duration = $data['duration_info'];
            $fillableData['duration'] = $duration['duration'] ?? null;
            $fillableData['min_duration'] = $duration['min_duration'] ?? null;
            $fillableData['max_duration'] = $duration['max_duration'] ?? null;
        }

        // Handle category data
        if (isset($data['category_info'])) {
            $category = $data['category_info'];
            $fillableData['category'] = $category['name'] ?? null;
            $fillableData['category_id'] = $category['id'] ?? null;
            $fillableData['category_name'] = $category['display_name'] ?? null;
        }

        $this->update($fillableData);
    }

    /**
     * Get data completeness percentage for this treatment.
     */
    public function getDataCompleteness(): float
    {
        $requiredFields = [
            'name', 'slug', 'description', 'category',
        ];

        $completedFields = 0;
        foreach ($requiredFields as $field) {
            if (! empty($this->$field)) {
                $completedFields++;
            }
        }

        // Add points for price and duration info
        if ($this->hasPriceInfo()) {
            $completedFields++;
        }
        if ($this->hasDurationInfo()) {
            $completedFields++;
        }

        $totalPossible = count($requiredFields) + 2; // 2 bonus categories

        return ($completedFields / $totalPossible) * 100;
    }

    /**
     * Check if treatment has price information.
     */
    public function hasPriceInfo(): bool
    {
        return ! is_null($this->price) ||
               (! is_null($this->min_price) && ! is_null($this->max_price));
    }

    /**
     * Check if treatment has duration information.
     */
    public function hasDurationInfo(): bool
    {
        return ! is_null($this->duration) ||
               (! is_null($this->min_duration) && ! is_null($this->max_duration));
    }

    /**
     * Check if treatment data is complete enough for display.
     */
    public function isDataComplete(): bool
    {
        return $this->getDataCompleteness() >= 60; // 60% threshold for treatments
    }

    /**
     * Get missing data fields for this treatment.
     */
    public function getMissingDataFields(): array
    {
        $requiredFields = ['name', 'slug', 'description', 'category'];

        $missing = [];
        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                $missing[] = $field;
            }
        }

        if (! $this->hasPriceInfo()) {
            $missing[] = 'price_info';
        }
        if (! $this->hasDurationInfo()) {
            $missing[] = 'duration_info';
        }

        return $missing;
    }
}
