<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use CrudTrait;
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_venue_owner',
        'notification_preferences',
        'last_booking_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_venue_owner' => 'boolean',
            'notification_preferences' => 'array',
            'last_booking_at' => 'datetime',
        ];
    }

    /**
     * Check if user is an admin - DISABLED.
     * Admin functionality has been removed from the application.
     */
    public function isAdmin(): bool
    {
        return false;
    }

    /**
     * Get user's display name.
     */
    public function getDisplayName(): string
    {
        return $this->name ?? $this->email;
    }

    /**
     * Get user's initials.
     */
    public function getInitials(): string
    {
        $names = explode(' ', $this->name ?? '');
        $initials = '';

        foreach ($names as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }

        return $initials ?: strtoupper(substr($this->email, 0, 2));
    }

    // Booking-related relationships
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function ownedVenues(): HasMany
    {
        return $this->hasMany(Venue::class, 'owner_id');
    }

    public function createdTimeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class, 'created_by_user_id');
    }

    public function bookingNotifications(): HasMany
    {
        return $this->hasMany(BookingNotification::class);
    }

    // Business Logic Methods for booking
    public function ownsVenue(int $venueId): bool
    {
        return $this->ownedVenues()->where('id', $venueId)->exists();
    }

    public function isVenueOwner(): bool
    {
        return $this->is_venue_owner && $this->ownedVenues()->exists();
    }

    public function canBookTreatment(Treatment $treatment): bool
    {
        // Check if user has reached daily booking limit
        $todayBookings = $this->bookings()
            ->where('booking_date', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->count();

        $policy = BookingPolicy::getEffectivePolicy($treatment->venue_id);

        return $todayBookings < $policy->max_bookings_per_day;
    }

    public function getUpcomingBookings()
    {
        return $this->bookings()
            ->upcoming()
            ->active()
            ->with(['venue', 'treatment'])
            ->orderBy('booking_date')
            ->orderBy('start_time');
    }

    public function getPastBookings()
    {
        return $this->bookings()
            ->where('booking_date', '<', now()->toDateString())
            ->with(['venue', 'treatment'])
            ->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'desc');
    }

    public function getTodaysBookingCount(): int
    {
        return $this->bookings()
            ->where('booking_date', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->count();
    }
}
