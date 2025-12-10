<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venue extends Model
{
    /** @use HasFactory<\Database\Factories\VenueFactory> */
    use CrudTrait, HasFactory, SoftDeletes;

    protected $fillable = [
        'city_id', 'external_id', 'name', 'slug', 'description', 'address',
        'type_id', 'type_name', 'normalised_name', 'desktop_uri', 'mobile_uri', 'app_uri',
        'is_new_venue', 'raw_data', 'latitude', 'longitude', 'phone', 'email', 'website',
        'opening_hours', 'rating', 'rating_count', 'is_active',
        'owner_id', 'booking_enabled', 'booking_advance_days', 'default_cancellation_hours',
        'booking_instructions',
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
            'booking_enabled' => 'boolean',
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

    /**
     * Extract slug from URL for venue processing.
     */
    public static function extractSlugFromUrl(string $url): string
    {
        // Remove trailing slash and get the last segment
        $url = rtrim($url, '/');
        $segments = explode('/', $url);
        $lastSegment = end($segments);

        // If the last segment is 'salonas', get the previous one
        if ($lastSegment === 'salonas' && count($segments) > 1) {
            return $segments[count($segments) - 2];
        }

        return $lastSegment;
    }

    /**
     * Get the primary image URL for the venue.
     */
    public function getPrimaryImageUrl(): ?string
    {
        $primaryImage = $this->images()->where('is_primary', true)->first();

        if ($primaryImage) {
            return $primaryImage->uri_large ?? $primaryImage->uri_medium ?? $primaryImage->uri_small ?? $primaryImage->path;
        }

        // Fallback to first image if no primary image is set
        $firstImage = $this->images()->first();

        return $firstImage ? ($firstImage->uri_large ?? $firstImage->uri_medium ?? $firstImage->uri_small ?? $firstImage->path) : null;
    }

    /**
     * Get the average rating for the venue.
     */
    public function getAverageRating(): float
    {
        if ($this->ratingDetails) {
            return (float) $this->ratingDetails->display_average ?? 0.0;
        }

        return (float) $this->rating ?? 0.0;
    }

    /**
     * Get the city name for the venue.
     */
    public function getCityName(): ?string
    {
        return $this->city?->name;
    }

    /**
     * Scope to filter venues by city.
     */
    public function scopeByCity($query, $cityId)
    {
        return $query->whereHas('cities', function ($q) use ($cityId) {
            $q->where('cities.id', $cityId);
        });
    }

    /**
     * Scope to filter venues by procedure.
     */
    public function scopeByProcedure($query, $procedureId)
    {
        return $query->whereHas('procedures', function ($q) use ($procedureId) {
            $q->where('procedures.id', $procedureId);
        });
    }

    /**
     * Scope to get only active venues.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if the venue is currently open.
     */
    public function isCurrentlyOpen(): bool
    {
        $currentDay = now()->format('l');
        $currentTime = now()->format('H:i');

        $todayHours = $this->openingHours()
            ->where('day_of_week', $currentDay)
            ->where('is_open', true)
            ->first();

        if (! $todayHours || ! $todayHours->opening_time || ! $todayHours->closing_time) {
            return false;
        }

        return $currentTime >= $todayHours->opening_time->format('H:i')
            && $currentTime <= $todayHours->closing_time->format('H:i');
    }

    /**
     * Get formatted address for the venue.
     */
    public function getFormattedAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->getCityName(),
        ]);

        return implode(', ', $parts);
    }

    // Booking-related relationships
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    public function bookingPolicies(): HasMany
    {
        return $this->hasMany(BookingPolicy::class);
    }

    // Booking-related scopes
    public function scopeBookingEnabled($query)
    {
        return $query->where('booking_enabled', true);
    }

    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('owner_id', $userId);
    }

    // Business Logic Methods for booking
    public function isBookingEnabled(): bool
    {
        return $this->booking_enabled && $this->is_active;
    }

    public function getAvailableTimeSlots(string $date, ?int $treatmentId = null)
    {
        return $this->timeSlots()
            ->available()
            ->forDate($date)
            ->when($treatmentId, fn ($q) => $q->forTreatment($treatmentId))
            ->orderBy('start_time')
            ->get();
    }

    public function getBookingPolicy(): ?BookingPolicy
    {
        return $this->bookingPolicies()->where('is_active', true)->first()
            ?? BookingPolicy::where('policy_type', 'system')->where('is_active', true)->first();
    }

    public function getTodaysBookings()
    {
        return $this->bookings()
            ->where('booking_date', now()->toDateString())
            ->with(['user', 'treatment'])
            ->orderBy('start_time');
    }

    public function getUpcomingBookings()
    {
        return $this->bookings()
            ->upcoming()
            ->active()
            ->with(['user', 'treatment'])
            ->orderBy('booking_date')
            ->orderBy('start_time');
    }

    public function hasOwner(): bool
    {
        return ! is_null($this->owner_id);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    // Parsing-related methods

    /**
     * Get parsing metadata for this venue.
     */
    public function getParsingMetadata(): array
    {
        return [
            'last_parsed' => $this->updated_at,
            'data_completeness' => $this->getDataCompleteness(),
            'has_images' => $this->images()->exists(),
            'has_treatments' => $this->treatments()->exists(),
            'has_location' => $this->location()->exists(),
            'has_opening_hours' => $this->openingHours()->exists(),
            'has_rating' => ! is_null($this->rating),
        ];
    }

    /**
     * Update venue from API data.
     */
    public function updateFromApiData(array $data): void
    {
        // Store raw data for debugging
        $this->raw_data = $data;

        // Extract fillable data
        $fillableData = array_intersect_key($data, array_flip($this->fillable));

        // Handle special fields
        if (isset($data['opening_hours']) && is_array($data['opening_hours'])) {
            $fillableData['opening_hours'] = $data['opening_hours'];
        }

        if (isset($data['coordinates'])) {
            $fillableData['latitude'] = $data['coordinates']['lat'] ?? null;
            $fillableData['longitude'] = $data['coordinates']['lng'] ?? null;
        }

        // Handle location data that might contain coordinates
        if (isset($data['location'])) {
            if (isset($data['location']['coordinates'])) {
                $fillableData['latitude'] = $data['location']['coordinates']['lat'] ?? null;
                $fillableData['longitude'] = $data['location']['coordinates']['lng'] ?? null;
            }
            if (isset($data['location']['latitude'])) {
                $fillableData['latitude'] = $data['location']['latitude'];
            }
            if (isset($data['location']['longitude'])) {
                $fillableData['longitude'] = $data['location']['longitude'];
            }
        }

        $this->update($fillableData);
    }

    /**
     * Get data completeness percentage for this venue.
     */
    public function getDataCompleteness(): float
    {
        $requiredFields = [
            'name', 'slug', 'address', 'latitude', 'longitude',
            'phone', 'email', 'website', 'description',
        ];

        $completedFields = 0;
        foreach ($requiredFields as $field) {
            if (! empty($this->$field)) {
                $completedFields++;
            }
        }

        // Add bonus points for related data
        $bonusPoints = 0;
        if ($this->images()->exists()) {
            $bonusPoints++;
        }
        if ($this->treatments()->exists()) {
            $bonusPoints++;
        }
        if ($this->openingHours()->exists()) {
            $bonusPoints++;
        }
        if ($this->ratingDetails()->exists()) {
            $bonusPoints++;
        }

        $totalPossible = count($requiredFields) + 4; // 4 bonus categories

        return (($completedFields + $bonusPoints) / $totalPossible) * 100;
    }

    /**
     * Check if venue data is complete enough for display.
     */
    public function isDataComplete(): bool
    {
        return $this->getDataCompleteness() >= 70; // 70% threshold
    }

    /**
     * Get missing data fields for this venue.
     */
    public function getMissingDataFields(): array
    {
        $requiredFields = [
            'name', 'slug', 'address', 'latitude', 'longitude',
            'phone', 'email', 'website', 'description',
        ];

        $missing = [];
        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                $missing[] = $field;
            }
        }

        // Check related data
        if (! $this->images()->exists()) {
            $missing[] = 'images';
        }
        if (! $this->treatments()->exists()) {
            $missing[] = 'treatments';
        }
        if (! $this->openingHours()->exists()) {
            $missing[] = 'opening_hours';
        }
        if (! $this->ratingDetails()->exists()) {
            $missing[] = 'rating_details';
        }

        return $missing;
    }

    /**
     * Sync all related data with city relationships.
     */
    public function syncRelatedDataWithCity(): void
    {
        if (! $this->city_id) {
            return;
        }

        // Update services to have city_id
        $this->services()->update(['city_id' => $this->city_id]);

        // Update images to have city_id
        $this->images()->update(['city_id' => $this->city_id]);

        // Update ratings to have city_id
        if ($this->ratingDetails) {
            $this->ratingDetails->update(['city_id' => $this->city_id]);
        }

        // Update opening hours to have city_id
        $this->openingHours()->update(['city_id' => $this->city_id]);

        // Update locations to have city_id
        $this->locations()->update(['city_id' => $this->city_id]);

        // Sync treatments with city through many-to-many
        $treatmentIds = $this->treatments()->pluck('id');
        if ($treatmentIds->isNotEmpty()) {
            $city = $this->city;
            if ($city) {
                $city->treatments()->syncWithoutDetaching($treatmentIds);
            }
        }
    }
}
