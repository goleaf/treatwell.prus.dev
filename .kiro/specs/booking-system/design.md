# Booking System Design Document

## Overview

This design document outlines how to implement the booking system using appropriate Laravel features including Eloquent ORM, Form Request validation, API Resources, Policies, and other Laravel best practices. The design follows Laravel 12 conventions and integrates seamlessly with the existing venue and treatment management system.

## Architecture

### Model-Driven Architecture
The booking system follows Laravel's Eloquent ORM patterns with proper model relationships, scopes, and business logic encapsulation. Each model represents a clear business entity with well-defined responsibilities.

### API-First Design
Following the existing application pattern, the booking system provides both web and API interfaces using:
- Eloquent API Resources for consistent JSON responses
- Form Request classes for validation
- Resource Controllers following RESTful conventions
- Proper HTTP status codes and error handling

### Policy-Based Authorization
Authorization is handled through Laravel Policies, providing fine-grained access control for:
- Booking management (users can manage their own bookings)
- Venue ownership (venue owners can manage their venue's bookings)
- Administrative functions (system-wide booking policies)

## Components and Interfaces

### Core Models

#### Booking Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_reference', 'user_id', 'venue_id', 'treatment_id', 'time_slot_id',
        'booking_date', 'start_time', 'end_time', 'duration', 'price', 'currency',
        'status', 'customer_name', 'customer_email', 'customer_phone', 'special_requests',
        'cancellation_deadline', 'advance_booking_days'
    ];

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
        return 'BK-' . now()->format('Y') . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }
}
```

#### TimeSlot Model
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TimeSlot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'venue_id', 'treatment_id', 'date', 'start_time', 'end_time', 'duration',
        'is_available', 'is_blocked', 'capacity', 'booked_count',
        'is_recurring', 'recurrence_type', 'recurrence_end_date',
        'created_by_user_id', 'notes'
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

    // Business Logic Methods
    public function isBookable(): bool
    {
        return $this->is_available 
            && !$this->is_blocked 
            && $this->booked_count < $this->capacity
            && $this->date >= now()->toDateString();
    }

    public function hasCapacity(): bool
    {
        return $this->booked_count < $this->capacity;
    }

    public function incrementBookedCount(): void
    {
        $this->increment('booked_count');
    }

    public function decrementBookedCount(): void
    {
        $this->decrement('booked_count');
    }
}
```

### Extended Existing Models

#### User Model Extensions
```php
// Add to existing User model

// Relationships
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

// Business Logic Methods
public function ownsVenue(int $venueId): bool
{
    return $this->ownedVenues()->where('id', $venueId)->exists();
}

public function isVenueOwner(): bool
{
    return $this->ownedVenues()->exists();
}

public function canBookTreatment(Treatment $treatment): bool
{
    // Check if user has reached daily booking limit
    $todayBookings = $this->bookings()
        ->where('booking_date', now()->toDateString())
        ->where('status', '!=', 'cancelled')
        ->count();
    
    return $todayBookings < 5; // Default limit from booking policies
}
```

#### Venue Model Extensions
```php
// Add to existing Venue model

// Relationships
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

public function availabilityTemplates(): HasMany
{
    return $this->hasMany(VenueAvailabilityTemplate::class);
}

// Scopes
public function scopeBookingEnabled($query)
{
    return $query->where('booking_enabled', true);
}

// Business Logic Methods
public function isBookingEnabled(): bool
{
    return $this->booking_enabled && $this->is_active;
}

public function getAvailableTimeSlots(string $date, ?int $treatmentId = null): Collection
{
    return $this->timeSlots()
        ->available()
        ->forDate($date)
        ->when($treatmentId, fn($q) => $q->forTreatment($treatmentId))
        ->orderBy('start_time')
        ->get();
}

public function getBookingPolicy(): ?BookingPolicy
{
    return $this->bookingPolicies()->where('is_active', true)->first()
        ?? BookingPolicy::where('policy_type', 'system')->where('is_active', true)->first();
}
```

#### Treatment Model Extensions
```php
// Add to existing Treatment model

// Relationships
public function bookings(): HasMany
{
    return $this->hasMany(Booking::class);
}

public function timeSlots(): HasMany
{
    return $this->hasMany(TimeSlot::class);
}

// Scopes
public function scopeBookingEnabled($query)
{
    return $query->where('booking_enabled', true);
}

// Business Logic Methods
public function isBookingEnabled(): bool
{
    return $this->booking_enabled && $this->is_active;
}

public function getAdvanceBookingDays(): int
{
    return $this->advance_booking_days ?? $this->venue->booking_advance_days ?? 30;
}

public function getCancellationHours(): int
{
    return $this->cancellation_hours ?? 24;
}
```

## Data Models

### Form Request Classes

#### StoreBookingRequest
```php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'venue_id' => ['required', 'exists:venues,id'],
            'treatment_id' => ['required', 'exists:treatments,id'],
            'time_slot_id' => ['required', 'exists:time_slots,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:255'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'venue_id.required' => 'Please select a venue.',
            'venue_id.exists' => 'The selected venue is not available.',
            'treatment_id.required' => 'Please select a treatment.',
            'treatment_id.exists' => 'The selected treatment is not available.',
            'time_slot_id.required' => 'Please select a time slot.',
            'time_slot_id.exists' => 'The selected time slot is not available.',
            'booking_date.after_or_equal' => 'Booking date must be today or in the future.',
            'end_time.after' => 'End time must be after start time.',
            'customer_email.email' => 'Please provide a valid email address.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate time slot is available
            if ($this->time_slot_id) {
                $timeSlot = TimeSlot::find($this->time_slot_id);
                if ($timeSlot && !$timeSlot->isBookable()) {
                    $validator->errors()->add('time_slot_id', 'The selected time slot is no longer available.');
                }
            }

            // Validate venue and treatment belong together
            if ($this->venue_id && $this->treatment_id) {
                $treatment = Treatment::find($this->treatment_id);
                if ($treatment && $treatment->venue_id !== (int) $this->venue_id) {
                    $validator->errors()->add('treatment_id', 'The selected treatment is not available at this venue.');
                }
            }
        });
    }
}
```

#### StoreTimeSlotRequest
```php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isVenueOwner();
    }

    public function rules(): array
    {
        return [
            'venue_id' => ['required', 'exists:venues,id'],
            'treatment_id' => ['nullable', 'exists:treatments,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'duration' => ['required', 'integer', 'min:15', 'max:480'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10'],
            'is_recurring' => ['boolean'],
            'recurrence_type' => ['nullable', 'in:daily,weekly,monthly'],
            'recurrence_end_date' => ['nullable', 'date', 'after:date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'venue_id.required' => 'Please select a venue.',
            'date.after_or_equal' => 'Date must be today or in the future.',
            'end_time.after' => 'End time must be after start time.',
            'duration.min' => 'Duration must be at least 15 minutes.',
            'duration.max' => 'Duration cannot exceed 8 hours.',
            'capacity.min' => 'Capacity must be at least 1.',
            'capacity.max' => 'Capacity cannot exceed 10.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate user owns the venue
            if ($this->venue_id && !auth()->user()->ownsVenue($this->venue_id)) {
                $validator->errors()->add('venue_id', 'You can only create time slots for your own venues.');
            }

            // Validate treatment belongs to venue
            if ($this->venue_id && $this->treatment_id) {
                $treatment = Treatment::find($this->treatment_id);
                if ($treatment && $treatment->venue_id !== (int) $this->venue_id) {
                    $validator->errors()->add('treatment_id', 'The selected treatment does not belong to this venue.');
                }
            }
        });
    }
}
```

### API Resources

#### BookingResource
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_reference' => $this->booking_reference,
            'status' => $this->status,
            'booking_date' => $this->booking_date->format('Y-m-d'),
            'start_time' => $this->start_time->format('H:i'),
            'end_time' => $this->end_time->format('H:i'),
            'duration' => $this->duration,
            'price' => [
                'amount' => $this->price,
                'currency' => $this->currency,
                'formatted' => "€{$this->price}",
            ],
            'customer' => [
                'name' => $this->customer_name,
                'email' => $this->customer_email,
                'phone' => $this->customer_phone,
            ],
            'special_requests' => $this->special_requests,
            'cancellation_deadline' => $this->cancellation_deadline?->format('Y-m-d H:i:s'),
            'can_be_cancelled' => $this->canBeCancelled(),
            'can_be_modified' => $this->canBeModified(),
            'venue' => new VenueResource($this->whenLoaded('venue')),
            'treatment' => new TreatmentResource($this->whenLoaded('treatment')),
            'user' => new UserResource($this->whenLoaded('user')),
            'timestamps' => [
                'booked_at' => $this->booked_at?->format('Y-m-d H:i:s'),
                'confirmed_at' => $this->confirmed_at?->format('Y-m-d H:i:s'),
                'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
                'created_at' => $this->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            ],
        ];
    }
}
```

#### TimeSlotResource
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'start_time' => $this->start_time->format('H:i'),
            'end_time' => $this->end_time->format('H:i'),
            'duration' => $this->duration,
            'capacity' => $this->capacity,
            'booked_count' => $this->booked_count,
            'available_spots' => $this->capacity - $this->booked_count,
            'is_available' => $this->is_available,
            'is_blocked' => $this->is_blocked,
            'is_bookable' => $this->isBookable(),
            'venue' => new VenueResource($this->whenLoaded('venue')),
            'treatment' => new TreatmentResource($this->whenLoaded('treatment')),
            'notes' => $this->notes,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
```

### Authorization Policies

#### BookingPolicy
```php
<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own bookings
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id 
            || $user->ownsVenue($booking->venue_id);
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create bookings
    }

    public function update(User $user, Booking $booking): bool
    {
        // Users can update their own bookings or venue owners can update bookings for their venues
        return ($user->id === $booking->user_id || $user->ownsVenue($booking->venue_id))
            && $booking->canBeModified();
    }

    public function delete(User $user, Booking $booking): bool
    {
        // Users can cancel their own bookings or venue owners can cancel bookings for their venues
        return ($user->id === $booking->user_id || $user->ownsVenue($booking->venue_id))
            && $booking->canBeCancelled();
    }

    public function confirm(User $user, Booking $booking): bool
    {
        // Only venue owners can confirm bookings
        return $user->ownsVenue($booking->venue_id);
    }
}
```

#### TimeSlotPolicy
```php
<?php

namespace App\Policies;

use App\Models\TimeSlot;
use App\Models\User;

class TimeSlotPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Anyone can view available time slots
    }

    public function view(User $user, TimeSlot $timeSlot): bool
    {
        return true; // Anyone can view individual time slots
    }

    public function create(User $user): bool
    {
        return $user->isVenueOwner();
    }

    public function update(User $user, TimeSlot $timeSlot): bool
    {
        return $user->ownsVenue($timeSlot->venue_id);
    }

    public function delete(User $user, TimeSlot $timeSlot): bool
    {
        return $user->ownsVenue($timeSlot->venue_id);
    }
}
```

## Error Handling

### Custom Exception Classes
```php
<?php

namespace App\Exceptions;

use Exception;

class BookingException extends Exception
{
    public static function timeSlotNotAvailable(): self
    {
        return new self('The selected time slot is no longer available.');
    }

    public static function bookingNotCancellable(): self
    {
        return new self('This booking cannot be cancelled due to the cancellation policy.');
    }

    public static function venueNotBookingEnabled(): self
    {
        return new self('This venue does not accept online bookings.');
    }

    public static function treatmentNotBookingEnabled(): self
    {
        return new self('This treatment is not available for online booking.');
    }

    public static function bookingLimitExceeded(): self
    {
        return new self('You have reached the maximum number of bookings allowed per day.');
    }
}
```

### Error Handling in Controllers
```php
// In BookingController
public function store(StoreBookingRequest $request)
{
    try {
        $booking = DB::transaction(function () use ($request) {
            $timeSlot = TimeSlot::findOrFail($request->time_slot_id);
            
            if (!$timeSlot->isBookable()) {
                throw BookingException::timeSlotNotAvailable();
            }

            $booking = Booking::create([
                ...$request->validated(),
                'user_id' => auth()->id(),
                'booking_reference' => '', // Will be generated after creation
                'status' => 'pending',
                'booked_at' => now(),
            ]);

            $booking->booking_reference = $booking->generateReference();
            $booking->save();

            $timeSlot->incrementBookedCount();

            return $booking;
        });

        return new BookingResource($booking->load(['venue', 'treatment', 'user']));
    } catch (BookingException $e) {
        return response()->json(['error' => $e->getMessage()], 422);
    }
}
```

## Testing Strategy

### Model Factories
```php
<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\Venue;
use App\Models\Treatment;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $bookingDate = $this->faker->dateTimeBetween('now', '+30 days');
        $startTime = $this->faker->time('H:i');
        $duration = $this->faker->randomElement([30, 45, 60, 90, 120]);
        $endTime = date('H:i', strtotime($startTime) + ($duration * 60));

        return [
            'booking_reference' => 'BK-' . date('Y') . '-' . $this->faker->unique()->numberBetween(100000, 999999),
            'user_id' => User::factory(),
            'venue_id' => Venue::factory(),
            'treatment_id' => Treatment::factory(),
            'time_slot_id' => TimeSlot::factory(),
            'booking_date' => $bookingDate->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $duration,
            'price' => $this->faker->randomFloat(2, 20, 200),
            'currency' => 'EUR',
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'cancelled', 'completed']),
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'customer_phone' => $this->faker->phoneNumber(),
            'special_requests' => $this->faker->optional()->sentence(),
            'cancellation_deadline' => $bookingDate->modify('-24 hours'),
            'booked_at' => now(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }
}
```

### Feature Tests
```php
<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\User;
use App\Models\Venue;
use App\Models\Treatment;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_booking(): void
    {
        $user = User::factory()->create();
        $venue = Venue::factory()->create(['booking_enabled' => true]);
        $treatment = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'booking_enabled' => true,
        ]);
        $timeSlot = TimeSlot::factory()->create([
            'venue_id' => $venue->id,
            'treatment_id' => $treatment->id,
            'is_available' => true,
            'capacity' => 1,
            'booked_count' => 0,
        ]);

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'venue_id' => $venue->id,
            'treatment_id' => $treatment->id,
            'time_slot_id' => $timeSlot->id,
            'booking_date' => $timeSlot->date->format('Y-m-d'),
            'start_time' => $timeSlot->start_time->format('H:i'),
            'end_time' => $timeSlot->end_time->format('H:i'),
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '+1234567890',
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'booking_reference',
                        'status',
                        'booking_date',
                        'start_time',
                        'end_time',
                        'price',
                        'customer',
                        'venue',
                        'treatment',
                    ]
                ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'venue_id' => $venue->id,
            'treatment_id' => $treatment->id,
            'time_slot_id' => $timeSlot->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_book_unavailable_time_slot(): void
    {
        $user = User::factory()->create();
        $venue = Venue::factory()->create(['booking_enabled' => true]);
        $treatment = Treatment::factory()->create([
            'venue_id' => $venue->id,
            'booking_enabled' => true,
        ]);
        $timeSlot = TimeSlot::factory()->create([
            'venue_id' => $venue->id,
            'treatment_id' => $treatment->id,
            'is_available' => false, // Not available
        ]);

        $response = $this->actingAs($user)->postJson('/api/bookings', [
            'venue_id' => $venue->id,
            'treatment_id' => $treatment->id,
            'time_slot_id' => $timeSlot->id,
            'booking_date' => $timeSlot->date->format('Y-m-d'),
            'start_time' => $timeSlot->start_time->format('H:i'),
            'end_time' => $timeSlot->end_time->format('H:i'),
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'error' => 'The selected time slot is no longer available.'
                ]);
    }

    public function test_user_can_view_their_bookings(): void
    {
        $user = User::factory()->create();
        $bookings = Booking::factory()->count(3)->create(['user_id' => $user->id]);
        
        // Create bookings for other users (should not be visible)
        Booking::factory()->count(2)->create();

        $response = $this->actingAs($user)->getJson('/api/bookings');

        $response->assertStatus(200)
                ->assertJsonCount(3, 'data');
    }

    public function test_venue_owner_can_view_venue_bookings(): void
    {
        $venueOwner = User::factory()->create(['is_venue_owner' => true]);
        $venue = Venue::factory()->create(['owner_id' => $venueOwner->id]);
        $bookings = Booking::factory()->count(3)->create(['venue_id' => $venue->id]);
        
        // Create bookings for other venues (should not be visible)
        Booking::factory()->count(2)->create();

        $response = $this->actingAs($venueOwner)->getJson("/api/venues/{$venue->id}/bookings");

        $response->assertStatus(200)
                ->assertJsonCount(3, 'data');
    }
}
```

This design document provides a comprehensive approach to implementing the booking system using appropriate Laravel features including Eloquent relationships, Form Request validation, API Resources, Policies, and proper error handling. The implementation follows Laravel 12 conventions and integrates seamlessly with the existing codebase.