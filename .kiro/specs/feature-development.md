# Feature Development Spec

## Overview
This spec template guides the development of new features in the Laravel application following Laravel Boost guidelines.

## Requirements
- [x] Define feature requirements and acceptance criteria
- [x] Identify affected models, controllers, and views
- [x] Plan database schema changes if needed
- [x] Consider authentication and authorization requirements

### Affected Components Analysis

Based on the booking system requirements in `.kiro/specs/booking-system/requirements.md`, the following components are affected:

#### New Models Required
- **Booking** - Core booking entity linking users, venues, treatments, and time slots
- **TimeSlot** - Available booking slots for treatments at venues
- **BookingPolicy** - System-wide booking policies and rules
- **Notification** - Booking-related notifications and reminders

#### Existing Models to Extend
- **User** - Add booking-related relationships and methods
  - `hasMany(Booking::class)` - User's bookings
  - `hasMany(Venue::class, 'owner_id')` - Venues owned by user (for venue owners)
  - Booking preference fields (notification settings, etc.)

- **Venue** - Add booking availability and management
  - `hasMany(Booking::class)` - Venue's bookings
  - `hasMany(TimeSlot::class)` - Available time slots
  - `belongsTo(User::class, 'owner_id')` - Venue owner relationship
  - Booking configuration fields (advance booking limits, cancellation policies)

- **Treatment** - Add booking-specific attributes
  - `hasMany(Booking::class)` - Treatment bookings
  - `hasMany(TimeSlot::class)` - Available slots for this treatment
  - Booking-related fields (booking_enabled, advance_booking_days, etc.)

#### Controllers Required

**Web Controllers:**
- **BookingController** - Main booking management for users
  - `index()` - User's booking dashboard
  - `create()` - Search and browse treatments
  - `store()` - Create new booking
  - `show()` - View booking details
  - `edit()` - Modify booking (if allowed)
  - `destroy()` - Cancel booking

- **VenueBookingController** - Booking management for venue owners
  - `index()` - Venue owner's booking dashboard
  - `show()` - View booking details
  - `update()` - Confirm/manage bookings
  - `destroy()` - Cancel bookings

- **AvailabilityController** - Manage venue availability
  - `index()` - Calendar view of availability
  - `store()` - Create time slots
  - `update()` - Modify availability
  - `destroy()` - Block time slots

**API Controllers:**
- **Api\BookingController** - RESTful booking API
- **Api\TimeSlotController** - Time slot management API
- **Api\AvailabilityController** - Availability search API

#### Controllers to Extend
- **VenueController** - Add booking-related methods
  - `availability()` - Get venue availability
  - `treatments()` - Get bookable treatments

- **TreatmentController** - Add booking functionality
  - `availability()` - Get treatment availability
  - `book()` - Quick booking endpoint

- **UserController** - Add booking profile management
  - `bookings()` - User's booking history
  - `preferences()` - Booking preferences

#### Views Required

**Booking Views:**
- `resources/views/bookings/index.blade.php` - User booking dashboard
- `resources/views/bookings/create.blade.php` - Treatment search and booking form
- `resources/views/bookings/show.blade.php` - Booking details
- `resources/views/bookings/edit.blade.php` - Modify booking
- `resources/views/bookings/confirmation.blade.php` - Booking confirmation

**Venue Management Views:**
- `resources/views/venue-bookings/index.blade.php` - Venue owner dashboard
- `resources/views/venue-bookings/show.blade.php` - Booking details for venue owners
- `resources/views/availability/index.blade.php` - Availability calendar
- `resources/views/availability/create.blade.php` - Create time slots
- `resources/views/availability/edit.blade.php` - Modify availability

**Partial Views:**
- `resources/views/partials/booking-card.blade.php` - Booking summary card
- `resources/views/partials/treatment-search.blade.php` - Treatment search form
- `resources/views/partials/time-slot-picker.blade.php` - Time slot selection
- `resources/views/partials/booking-status.blade.php` - Status indicators

#### Views to Extend
- **venues/show.blade.php** - Add booking functionality and availability display
- **treatments/show.blade.php** - Add booking button and availability
- **treatments/index.blade.php** - Add booking filters and quick book options
- **layouts/app.blade.php** - Add booking navigation items

#### Form Requests Required
- **StoreBookingRequest** - Booking creation validation
- **UpdateBookingRequest** - Booking modification validation
- **StoreTimeSlotRequest** - Time slot creation validation
- **UpdateAvailabilityRequest** - Availability modification validation
- **BookingSearchRequest** - Treatment search validation

#### Resources Required
- **BookingResource** - API booking representation
- **BookingCollection** - API booking collection
- **TimeSlotResource** - API time slot representation
- **AvailabilityResource** - API availability representation

#### Policies Required
- **BookingPolicy** - Authorization for booking operations
- **TimeSlotPolicy** - Authorization for time slot management
- **VenueBookingPolicy** - Authorization for venue booking management

#### Middleware Considerations
- **EnsureVenueOwner** - Verify user owns the venue for booking management
- **ValidateBookingWindow** - Ensure bookings are within allowed timeframes
- **CheckBookingAvailability** - Prevent double bookings

#### Integration Points
- **Notification System** - Email/SMS notifications for booking events
- **Payment Integration** - Handle booking payments and refunds
- **Calendar Integration** - Sync with external calendar systems
- **Queue Jobs** - Background processing for notifications and cleanup

### Authentication and Authorization Analysis

Based on the booking system requirements and current application state, the following authentication and authorization considerations have been identified:

#### Current Authentication State
- **Authentication System**: Laravel's built-in authentication is configured but **NOT IMPLEMENTED**
- **Current Routes**: All routes are currently **PUBLIC** (no authentication required)
- **Admin System**: Backpack admin functionality has been **DISABLED**
- **User Model**: Basic User model exists with standard Laravel authentication traits
- **Policies**: Existing policies are present but currently allow all authenticated users full access

#### Authentication Requirements for Booking System

**1. User Authentication Setup**
- **REQUIRED**: Implement Laravel authentication system (login/register/logout)
- **Routes Needed**: 
  - `GET /login` - Login form
  - `POST /login` - Process login
  - `GET /register` - Registration form  
  - `POST /register` - Process registration
  - `POST /logout` - Logout user
  - `GET /password/reset` - Password reset (optional)
- **Middleware**: Apply `auth` middleware to protected routes
- **Guards**: Use default `web` guard for session-based authentication

**2. User Roles and Permissions**
Based on booking requirements, we need to distinguish between:
- **Regular Users** - Can browse treatments, make bookings, manage their own bookings
- **Venue Owners** - Can manage their venues, availability, and venue bookings
- **System Administrators** - Can manage booking policies and system settings (future)

**3. Route Protection Strategy**

**Public Routes (No Authentication Required):**
```php
// Treatment browsing and search
GET /treatments - Browse treatments
GET /treatments/{treatment} - View treatment details
GET /venues - Browse venues  
GET /venues/{venue} - View venue details
GET /api/treatments - API treatment search
GET /api/venues - API venue search
```

**Protected Routes (Authentication Required):**
```php
// User booking management
GET /bookings - User's booking dashboard
POST /bookings - Create new booking
GET /bookings/{booking} - View booking details
PUT /bookings/{booking} - Modify booking
DELETE /bookings/{booking} - Cancel booking

// Venue owner routes (venue ownership required)
GET /venue-bookings - Venue owner dashboard
GET /venue-bookings/{booking} - View venue booking
PUT /venue-bookings/{booking} - Manage venue booking
GET /availability - Manage availability
POST /availability - Create time slots
```

#### Authorization Requirements

**1. Booking Authorization Rules**
- **View Booking**: User can view their own bookings OR venue owner can view bookings for their venues
- **Create Booking**: Any authenticated user can create bookings
- **Modify Booking**: User can modify their own bookings (within policy limits) OR venue owner can modify bookings for their venues
- **Cancel Booking**: User can cancel their own bookings OR venue owner can cancel bookings for their venues

**2. Venue Management Authorization**
- **Venue Ownership**: Add `owner_id` field to venues table to establish ownership
- **Availability Management**: Only venue owners can manage their venue's availability
- **Booking Management**: Only venue owners can manage bookings for their venues

**3. Policy Implementation Required**

**BookingPolicy:**
```php
public function view(User $user, Booking $booking): bool
{
    return $user->id === $booking->user_id || 
           $user->ownsVenue($booking->venue_id);
}

public function update(User $user, Booking $booking): bool
{
    // Check if within modification window and user owns booking or venue
    return ($user->id === $booking->user_id || $user->ownsVenue($booking->venue_id))
           && $booking->canBeModified();
}
```

**VenuePolicy Updates:**
```php
public function update(User $user, Venue $venue): bool
{
    return $user->id === $venue->owner_id;
}

public function manageBookings(User $user, Venue $venue): bool
{
    return $user->id === $venue->owner_id;
}
```

#### Database Schema Changes for Authorization

**1. Add Venue Ownership**
```php
// Migration: add_owner_id_to_venues_table
Schema::table('venues', function (Blueprint $table) {
    $table->foreignId('owner_id')->nullable()->constrained('users');
});
```

**2. User Model Extensions**
```php
// Add to User model
public function ownedVenues(): HasMany
{
    return $this->hasMany(Venue::class, 'owner_id');
}

public function ownsVenue(int $venueId): bool
{
    return $this->ownedVenues()->where('id', $venueId)->exists();
}

public function isVenueOwner(): bool
{
    return $this->ownedVenues()->exists();
}
```

#### Middleware Requirements

**1. Custom Middleware Needed**
- **EnsureVenueOwner**: Verify user owns the venue for management routes
- **ValidateBookingAccess**: Ensure user can access booking (owns booking or owns venue)

**2. Route Group Protection**
```php
// Protected user routes
Route::middleware(['auth'])->group(function () {
    Route::resource('bookings', BookingController::class);
});

// Venue owner routes
Route::middleware(['auth', 'venue.owner'])->group(function () {
    Route::get('/venue-bookings', [VenueBookingController::class, 'index']);
    Route::resource('availability', AvailabilityController::class);
});
```

#### Security Considerations

**1. Input Validation**
- Validate booking time slots are available and not in the past
- Prevent booking conflicts and double bookings
- Validate venue ownership for management operations

**2. Rate Limiting**
- Apply rate limiting to booking creation to prevent spam
- Limit availability updates to prevent abuse

**3. CSRF Protection**
- Ensure all booking forms include CSRF tokens
- Protect all state-changing operations

#### Implementation Priority

**Phase 1: Basic Authentication**
1. Set up Laravel authentication routes and views
2. Add authentication middleware to booking routes
3. Update existing policies for proper authorization

**Phase 2: Venue Ownership**
1. Add owner_id to venues table
2. Implement venue ownership authorization
3. Create venue owner middleware

**Phase 3: Advanced Authorization**
1. Implement booking-specific policies
2. Add custom middleware for complex authorization rules
3. Add rate limiting and security measures

#### Testing Requirements

**Authentication Tests:**
- Test login/logout functionality
- Test registration process
- Test password reset (if implemented)

**Authorization Tests:**
- Test booking access control (users can only see their bookings)
- Test venue owner access control (owners can only manage their venues)
- Test unauthorized access attempts return proper errors

**Security Tests:**
- Test CSRF protection on all forms
- Test rate limiting on booking creation
- Test input validation on all booking operations

## Design
- [ ] Follow existing Laravel conventions and patterns
- [ ] Use appropriate Laravel features (Eloquent, validation, etc.)
- [ ] Plan API endpoints if needed (with versioning)
- [ ] Design user interface following Tailwind CSS patterns

## Implementation Tasks
- [ ] Create/update models with proper relationships
- [ ] Create Form Request classes for validation
- [ ] Implement controllers following existing patterns
- [ ] Create/update database migrations
- [ ] Build frontend components (Blade/Vue/React)
- [ ] Add proper error handling

## Testing
- [ ] Write feature tests for happy paths
- [ ] Write feature tests for error conditions
- [ ] Write unit tests for complex business logic
- [ ] Test API endpoints if applicable
- [ ] Run `php artisan test --filter=FeatureName`

## Documentation
- [ ] Update API documentation if applicable
- [ ] Add inline code documentation
- [ ] Update README if needed

## References
- #[[file:AGENTS.md]] - Laravel Boost guidelines
- Use `search-docs` tool for Laravel-specific documentation