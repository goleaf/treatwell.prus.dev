<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingPolicy extends Model
{
    /** @use HasFactory<\Database\Factories\BookingPolicyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'policy_type', 'venue_id',
        'cancellation_hours', 'advance_booking_days', 'max_bookings_per_day',
        'require_payment', 'allow_modifications', 'modification_hours',
        'send_confirmation', 'send_reminders', 'reminder_hours', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'require_payment' => 'boolean',
            'allow_modifications' => 'boolean',
            'send_confirmation' => 'boolean',
            'send_reminders' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('policy_type', 'system');
    }

    public function scopeVenue($query)
    {
        return $query->where('policy_type', 'venue');
    }

    public function scopeForVenue($query, $venueId)
    {
        return $query->where('venue_id', $venueId);
    }

    // Business Logic Methods
    public function isSystemPolicy(): bool
    {
        return $this->policy_type === 'system';
    }

    public function isVenuePolicy(): bool
    {
        return $this->policy_type === 'venue';
    }

    public function getCancellationDeadline(\DateTime $bookingDateTime): \DateTime
    {
        return (clone $bookingDateTime)->modify("-{$this->cancellation_hours} hours");
    }

    public function getModificationDeadline(\DateTime $bookingDateTime): \DateTime
    {
        return (clone $bookingDateTime)->modify("-{$this->modification_hours} hours");
    }

    public function getReminderTime(\DateTime $bookingDateTime): \DateTime
    {
        return (clone $bookingDateTime)->modify("-{$this->reminder_hours} hours");
    }

    public function getEarliestBookingDate(): \DateTime
    {
        return now()->addDays($this->advance_booking_days);
    }

    public function canBookInAdvance(\DateTime $bookingDate): bool
    {
        $maxAdvanceDate = now()->addDays($this->advance_booking_days);

        return $bookingDate <= $maxAdvanceDate;
    }

    public function canCancelBooking(\DateTime $bookingDateTime): bool
    {
        $deadline = $this->getCancellationDeadline($bookingDateTime);

        return now() <= $deadline;
    }

    public function canModifyBooking(\DateTime $bookingDateTime): bool
    {
        if (! $this->allow_modifications) {
            return false;
        }

        $deadline = $this->getModificationDeadline($bookingDateTime);

        return now() <= $deadline;
    }

    public function hasReachedDailyLimit(int $userBookingsToday): bool
    {
        return $userBookingsToday >= $this->max_bookings_per_day;
    }

    // Static methods for policy resolution
    public static function getEffectivePolicy(?int $venueId = null): self
    {
        // Try to get venue-specific policy first
        if ($venueId) {
            $venuePolicy = self::active()
                ->venue()
                ->forVenue($venueId)
                ->first();

            if ($venuePolicy) {
                return $venuePolicy;
            }
        }

        // Fall back to system policy
        $systemPolicy = self::active()
            ->system()
            ->first();

        if ($systemPolicy) {
            return $systemPolicy;
        }

        // Create default policy if none exists
        return self::createDefaultSystemPolicy();
    }

    public static function createDefaultSystemPolicy(): self
    {
        return self::create([
            'name' => 'Default System Policy',
            'description' => 'Default booking policy for the system',
            'policy_type' => 'system',
            'cancellation_hours' => 24,
            'advance_booking_days' => 30,
            'max_bookings_per_day' => 5,
            'require_payment' => false,
            'allow_modifications' => true,
            'modification_hours' => 24,
            'send_confirmation' => true,
            'send_reminders' => true,
            'reminder_hours' => 24,
            'is_active' => true,
        ]);
    }
}
