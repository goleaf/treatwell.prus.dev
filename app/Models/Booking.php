<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use CrudTrait, HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_reference', 'user_id', 'venue_id', 'treatment_id', 'time_slot_id',
        'booking_date', 'start_time', 'end_time', 'duration', 'price', 'currency',
        'status', 'customer_name', 'customer_email', 'customer_phone', 'special_requests',
        'cancellation_deadline', 'advance_booking_days', 'booked_at', 'confirmed_at',
        'cancelled_at', 'completed_at',
    ];

    /**
     * Default relationships to eager load to prevent N+1 queries
     */
    protected $with = ['user', 'venue', 'treatment'];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'price' => 'decimal:2',
            'cancellation_deadline' => 'datetime',
            'booked_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(BookingNotification::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForVenue($query, $venueId)
    {
        return $query->where('venue_id', $venueId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', now()->toDateString());
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('booking_date', [$startDate, $endDate]);
    }

    /**
     * Scope for dashboard queries with optimized eager loading
     */
    public function scopeWithDashboardData($query)
    {
        return $query->with(['user:id,name,email', 'venue:id,name,address', 'treatment:id,name,duration']);
    }

    /**
     * Scope for venue owner dashboard with minimal data
     */
    public function scopeForVenueOwnerDashboard($query, $venueId)
    {
        return $query->where('venue_id', $venueId)
            ->with(['user:id,name,email,phone', 'treatment:id,name,duration,price'])
            ->select(['id', 'booking_reference', 'user_id', 'treatment_id', 'booking_date', 'start_time', 'status', 'price', 'customer_name', 'customer_phone']);
    }

    // Business Logic Methods
    public function canBeCancelled(): bool
    {
        return $this->status === 'confirmed'
            && $this->cancellation_deadline
            && now()->isBefore($this->cancellation_deadline);
    }

    public function canBeModified(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'])
            && $this->booking_date >= now()->addHours(24)->toDateString();
    }

    public function generateReference(): string
    {
        return 'BK-'.now()->format('Y').'-'.str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    public function isUpcoming(): bool
    {
        return $this->booking_date >= now()->toDateString()
            && in_array($this->status, ['pending', 'confirmed']);
    }

    public function isPast(): bool
    {
        return $this->booking_date < now()->toDateString()
            || $this->status === 'completed';
    }

    public function getFormattedPrice(): string
    {
        return $this->currency.number_format($this->price, 2);
    }

    public function getFormattedDuration(): string
    {
        $hours = intval($this->duration / 60);
        $minutes = $this->duration % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}m";
        }
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Confirmation',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            default => ucfirst($this->status)
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'confirmed' => 'green',
            'cancelled' => 'red',
            'completed' => 'blue',
            default => 'gray'
        };
    }

    // Boot method to handle model events
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_reference)) {
                // Temporary reference, will be updated after save
                $booking->booking_reference = 'TEMP-'.uniqid();
            }

            if (empty($booking->booked_at)) {
                $booking->booked_at = now();
            }
        });

        static::created(function ($booking) {
            // Update with proper reference after ID is available
            if (str_starts_with($booking->booking_reference, 'TEMP-')) {
                $booking->booking_reference = $booking->generateReference();
                $booking->saveQuietly();
            }
        });
    }
}
