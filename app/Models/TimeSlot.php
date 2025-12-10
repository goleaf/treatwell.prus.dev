<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeSlot extends Model
{
    /** @use HasFactory<\Database\Factories\TimeSlotFactory> */
    use CrudTrait, HasFactory, SoftDeletes;

    protected $fillable = [
        'venue_id', 'treatment_id', 'date', 'start_time', 'end_time', 'duration',
        'is_available', 'is_blocked', 'capacity', 'booked_count',
        'is_recurring', 'recurrence_type', 'recurrence_end_date',
        'created_by_user_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'is_available' => 'boolean',
            'is_blocked' => 'boolean',
            'is_recurring' => 'boolean',
            'recurrence_end_date' => 'date',
        ];
    }

    // Relationships
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
            ->where('is_blocked', false)
            ->whereRaw('booked_count < capacity');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForVenue($query, $venueId)
    {
        return $query->where('venue_id', $venueId);
    }

    public function scopeForTreatment($query, $treatmentId)
    {
        return $query->where(function ($q) use ($treatmentId) {
            $q->where('treatment_id', $treatmentId)
                ->orWhereNull('treatment_id');
        });
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString());
    }

    public function scopeByTimeRange($query, $startTime, $endTime)
    {
        return $query->where('start_time', '>=', $startTime)
            ->where('end_time', '<=', $endTime);
    }

    public function scopeNotBlocked($query)
    {
        return $query->where('is_blocked', false);
    }

    public function scopeWithCapacity($query)
    {
        return $query->whereRaw('booked_count < capacity');
    }

    // Business Logic Methods
    public function isBookable(): bool
    {
        return $this->is_available
            && ! $this->is_blocked
            && $this->booked_count < $this->capacity
            && $this->date >= now()->toDateString();
    }

    public function hasCapacity(): bool
    {
        return $this->booked_count < $this->capacity;
    }

    public function getAvailableSpots(): int
    {
        return max(0, $this->capacity - $this->booked_count);
    }

    public function incrementBookedCount(): void
    {
        $this->increment('booked_count');
    }

    public function decrementBookedCount(): void
    {
        if ($this->booked_count > 0) {
            $this->decrement('booked_count');
        }
    }

    public function isFull(): bool
    {
        return $this->booked_count >= $this->capacity;
    }

    public function isPast(): bool
    {
        return $this->date < now()->toDateString();
    }

    public function isToday(): bool
    {
        return $this->date->isToday();
    }

    public function getFormattedTime(): string
    {
        return $this->start_time->format('H:i').' - '.$this->end_time->format('H:i');
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
        if ($this->is_blocked) {
            return 'Blocked';
        }

        if (! $this->is_available) {
            return 'Unavailable';
        }

        if ($this->isFull()) {
            return 'Fully Booked';
        }

        if ($this->isPast()) {
            return 'Past';
        }

        return 'Available';
    }

    public function getStatusColor(): string
    {
        if ($this->is_blocked) {
            return 'red';
        }

        if (! $this->is_available) {
            return 'gray';
        }

        if ($this->isFull()) {
            return 'orange';
        }

        if ($this->isPast()) {
            return 'gray';
        }

        return 'green';
    }

    public function canBeDeleted(): bool
    {
        return $this->booked_count === 0;
    }

    public function canBeModified(): bool
    {
        // Can be modified if no bookings or all bookings are pending/can be moved
        return $this->bookings()->whereIn('status', ['confirmed', 'completed'])->count() === 0;
    }

    // Boot method to handle model events
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($timeSlot) {
            // Prevent deletion if there are confirmed bookings
            if ($timeSlot->bookings()->whereIn('status', ['confirmed', 'completed'])->exists()) {
                throw new \Exception('Cannot delete time slot with confirmed or completed bookings.');
            }
        });
    }
}
