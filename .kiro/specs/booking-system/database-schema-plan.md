# Booking System Database Schema Plan

## Overview

This document outlines the database schema changes required to implement the booking system functionality. The plan includes new tables, modifications to existing tables, and the relationships between them.

## Current Database Analysis

### Existing Tables (Relevant to Booking System)
- **users** - Basic user authentication (name, email, password)
- **venues** - Venue information with location, contact details, ratings
- **treatments** - Treatment services offered by venues with pricing and duration
- **cities** - Geographic location data
- **opening_hours** - Venue operating hours

### Missing Components for Booking System
Based on the requirements analysis, we need to add:
1. Booking management tables
2. Time slot and availability management
3. Venue ownership relationships
4. Booking policies and system settings
5. Notification preferences and tracking

## New Tables Required

### 1. bookings
Core booking entity linking users, venues, treatments, and time slots.

```sql
CREATE TABLE bookings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    booking_reference VARCHAR(255) UNIQUE NOT NULL, -- Unique booking reference (e.g., BK-2024-001234)
    user_id BIGINT UNSIGNED NOT NULL,
    venue_id BIGINT UNSIGNED NOT NULL,
    treatment_id BIGINT UNSIGNED NOT NULL,
    time_slot_id BIGINT UNSIGNED NOT NULL,
    
    -- Booking Details
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    duration INTEGER NOT NULL, -- Duration in minutes
    
    -- Pricing Information
    price DECIMAL(8,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    
    -- Status Management
    status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'no_show') DEFAULT 'pending',
    
    -- Customer Information (snapshot at booking time)
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(255) NULL,
    special_requests TEXT NULL,
    
    -- Booking Policies
    cancellation_deadline DATETIME NULL, -- When cancellation is no longer allowed
    advance_booking_days INTEGER NULL, -- How far in advance this was booked
    
    -- Timestamps
    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    
    -- Audit Fields
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    -- Foreign Key Constraints
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
    FOREIGN KEY (treatment_id) REFERENCES treatments(id) ON DELETE CASCADE,
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_bookings_user_id (user_id),
    INDEX idx_bookings_venue_id (venue_id),
    INDEX idx_bookings_treatment_id (treatment_id),
    INDEX idx_bookings_booking_date (booking_date),
    INDEX idx_bookings_status (status),
    INDEX idx_bookings_reference (booking_reference),
    INDEX idx_bookings_datetime (booking_date, start_time)
);
```

### 2. time_slots
Available booking slots for treatments at venues.

```sql
CREATE TABLE time_slots (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    venue_id BIGINT UNSIGNED NOT NULL,
    treatment_id BIGINT UNSIGNED NULL, -- NULL means available for any treatment
    
    -- Time Information
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    duration INTEGER NOT NULL, -- Duration in minutes
    
    -- Availability
    is_available BOOLEAN DEFAULT TRUE,
    is_blocked BOOLEAN DEFAULT FALSE, -- Manually blocked by venue owner
    capacity INTEGER DEFAULT 1, -- How many bookings this slot can handle
    booked_count INTEGER DEFAULT 0, -- Current number of bookings
    
    -- Recurrence (for recurring availability)
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_type ENUM('daily', 'weekly', 'monthly') NULL,
    recurrence_end_date DATE NULL,
    
    -- Metadata
    created_by_user_id BIGINT UNSIGNED NULL, -- Venue owner who created this slot
    notes TEXT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    -- Foreign Key Constraints
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
    FOREIGN KEY (treatment_id) REFERENCES treatments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_time_slots_venue_id (venue_id),
    INDEX idx_time_slots_treatment_id (treatment_id),
    INDEX idx_time_slots_date (date),
    INDEX idx_time_slots_availability (is_available, is_blocked),
    INDEX idx_time_slots_datetime (date, start_time),
    UNIQUE KEY unique_venue_treatment_datetime (venue_id, treatment_id, date, start_time)
);
```

### 3. booking_policies
System-wide and venue-specific booking policies and rules.

```sql
CREATE TABLE booking_policies (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    venue_id BIGINT UNSIGNED NULL, -- NULL for system-wide policies
    
    -- Policy Configuration
    policy_name VARCHAR(255) NOT NULL,
    policy_type ENUM('system', 'venue') DEFAULT 'venue',
    
    -- Booking Rules
    advance_booking_min_hours INTEGER DEFAULT 24, -- Minimum hours in advance
    advance_booking_max_days INTEGER DEFAULT 90, -- Maximum days in advance
    cancellation_min_hours INTEGER DEFAULT 24, -- Minimum hours before cancellation
    
    -- Time Configuration
    booking_time_increment INTEGER DEFAULT 15, -- Time slot increments in minutes
    max_booking_duration INTEGER DEFAULT 480, -- Maximum booking duration in minutes (8 hours)
    min_booking_duration INTEGER DEFAULT 15, -- Minimum booking duration in minutes
    
    -- Capacity and Limits
    max_concurrent_bookings INTEGER DEFAULT 1, -- Max bookings per time slot
    max_daily_bookings_per_user INTEGER DEFAULT 5, -- Max bookings per user per day
    
    -- Payment and Confirmation
    requires_payment BOOLEAN DEFAULT FALSE,
    requires_confirmation BOOLEAN DEFAULT TRUE,
    auto_confirm_bookings BOOLEAN DEFAULT FALSE,
    
    -- Notification Settings
    send_confirmation_email BOOLEAN DEFAULT TRUE,
    send_reminder_email BOOLEAN DEFAULT TRUE,
    reminder_hours_before INTEGER DEFAULT 24,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    -- Foreign Key Constraints
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_booking_policies_venue_id (venue_id),
    INDEX idx_booking_policies_type (policy_type),
    INDEX idx_booking_policies_active (is_active)
);
```

### 4. booking_notifications
Track notifications sent for bookings.

```sql
CREATE TABLE booking_notifications (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    booking_id BIGINT UNSIGNED NOT NULL,
    
    -- Notification Details
    notification_type ENUM('confirmation', 'reminder', 'cancellation', 'modification', 'venue_change') NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_type ENUM('customer', 'venue_owner', 'admin') DEFAULT 'customer',
    
    -- Content
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    
    -- Delivery Status
    status ENUM('pending', 'sent', 'failed', 'bounced') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    failed_at TIMESTAMP NULL,
    failure_reason TEXT NULL,
    
    -- Metadata
    provider VARCHAR(50) NULL, -- Email provider used
    provider_message_id VARCHAR(255) NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Key Constraints
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_booking_notifications_booking_id (booking_id),
    INDEX idx_booking_notifications_type (notification_type),
    INDEX idx_booking_notifications_status (status),
    INDEX idx_booking_notifications_recipient (recipient_email)
);
```

### 5. venue_availability_templates
Templates for recurring availability patterns.

```sql
CREATE TABLE venue_availability_templates (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    venue_id BIGINT UNSIGNED NOT NULL,
    treatment_id BIGINT UNSIGNED NULL, -- NULL for general availability
    
    -- Template Information
    template_name VARCHAR(255) NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    
    -- Time Configuration
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_duration INTEGER NOT NULL, -- Duration of each slot in minutes
    break_duration INTEGER DEFAULT 0, -- Break between slots in minutes
    
    -- Availability
    is_active BOOLEAN DEFAULT TRUE,
    effective_from DATE NOT NULL,
    effective_until DATE NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    -- Foreign Key Constraints
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
    FOREIGN KEY (treatment_id) REFERENCES treatments(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_venue_availability_venue_id (venue_id),
    INDEX idx_venue_availability_treatment_id (treatment_id),
    INDEX idx_venue_availability_day (day_of_week),
    INDEX idx_venue_availability_active (is_active)
);
```

## Modifications to Existing Tables

### 1. venues Table Modifications
Add venue ownership and booking-related fields.

```sql
ALTER TABLE venues ADD COLUMN owner_id BIGINT UNSIGNED NULL AFTER id;
ALTER TABLE venues ADD COLUMN booking_enabled BOOLEAN DEFAULT TRUE AFTER is_active;
ALTER TABLE venues ADD COLUMN auto_confirm_bookings BOOLEAN DEFAULT FALSE AFTER booking_enabled;
ALTER TABLE venues ADD COLUMN booking_advance_days INTEGER DEFAULT 30 AFTER auto_confirm_bookings;
ALTER TABLE venues ADD COLUMN cancellation_policy TEXT NULL AFTER booking_advance_days;
ALTER TABLE venues ADD COLUMN booking_instructions TEXT NULL AFTER cancellation_policy;

-- Add foreign key constraint
ALTER TABLE venues ADD CONSTRAINT fk_venues_owner_id 
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL;

-- Add indexes
ALTER TABLE venues ADD INDEX idx_venues_owner_id (owner_id);
ALTER TABLE venues ADD INDEX idx_venues_booking_enabled (booking_enabled);
```

### 2. treatments Table Modifications
Add booking-specific attributes for treatments.

```sql
ALTER TABLE treatments ADD COLUMN booking_enabled BOOLEAN DEFAULT TRUE AFTER is_active;
ALTER TABLE treatments ADD COLUMN advance_booking_days INTEGER NULL AFTER booking_enabled;
ALTER TABLE treatments ADD COLUMN cancellation_hours INTEGER NULL AFTER advance_booking_days;
ALTER TABLE treatments ADD COLUMN max_bookings_per_slot INTEGER DEFAULT 1 AFTER cancellation_hours;
ALTER TABLE treatments ADD COLUMN booking_buffer_minutes INTEGER DEFAULT 0 AFTER max_bookings_per_slot;
ALTER TABLE treatments ADD COLUMN requires_confirmation BOOLEAN DEFAULT TRUE AFTER booking_buffer_minutes;
ALTER TABLE treatments ADD COLUMN booking_notes TEXT NULL AFTER requires_confirmation;

-- Add indexes
ALTER TABLE treatments ADD INDEX idx_treatments_booking_enabled (booking_enabled);
```

### 3. users Table Modifications
Add user preferences and booking-related fields.

```sql
ALTER TABLE users ADD COLUMN phone VARCHAR(255) NULL AFTER email;
ALTER TABLE users ADD COLUMN date_of_birth DATE NULL AFTER phone;
ALTER TABLE users ADD COLUMN gender ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL AFTER date_of_birth;
ALTER TABLE users ADD COLUMN notification_preferences JSON NULL AFTER gender;
ALTER TABLE users ADD COLUMN timezone VARCHAR(50) DEFAULT 'Europe/Vilnius' AFTER notification_preferences;
ALTER TABLE users ADD COLUMN is_venue_owner BOOLEAN DEFAULT FALSE AFTER timezone;
ALTER TABLE users ADD COLUMN last_booking_at TIMESTAMP NULL AFTER is_venue_owner;

-- Add indexes
ALTER TABLE users ADD INDEX idx_users_venue_owner (is_venue_owner);
ALTER TABLE users ADD INDEX idx_users_phone (phone);
```

## Pivot Tables and Relationships

### 1. treatment_venue_availability
Link treatments to specific venue availability (if treatments have different availability than venue default).

```sql
CREATE TABLE treatment_venue_availability (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    treatment_id BIGINT UNSIGNED NOT NULL,
    venue_id BIGINT UNSIGNED NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (treatment_id) REFERENCES treatments(id) ON DELETE CASCADE,
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_treatment_venue_day (treatment_id, venue_id, day_of_week, start_time)
);
```

## Data Migration Considerations

### 1. Default Booking Policies
Create system-wide default booking policies during migration:

```sql
INSERT INTO booking_policies (
    policy_name, policy_type, advance_booking_min_hours, advance_booking_max_days,
    cancellation_min_hours, booking_time_increment, max_booking_duration,
    min_booking_duration, requires_confirmation, send_confirmation_email,
    send_reminder_email, reminder_hours_before, is_active
) VALUES (
    'Default System Policy', 'system', 24, 90, 24, 15, 480, 15, 
    TRUE, TRUE, TRUE, 24, TRUE
);
```

### 2. Venue Ownership Assignment
For existing venues, we'll need to either:
- Assign them to a default admin user
- Create a migration script to assign ownership based on business rules
- Leave owner_id as NULL initially and assign later through admin interface

### 3. Default Time Slots
Generate default availability based on existing opening_hours data:
- Convert opening_hours JSON to time_slots entries
- Create recurring weekly patterns
- Set default 30-minute slots during operating hours

## Indexes and Performance Optimization

### Critical Indexes for Performance
1. **Booking Search Performance**:
   - `idx_bookings_venue_date` (venue_id, booking_date)
   - `idx_bookings_user_status` (user_id, status)
   - `idx_bookings_datetime_status` (booking_date, start_time, status)

2. **Availability Search Performance**:
   - `idx_time_slots_venue_date_available` (venue_id, date, is_available, is_blocked)
   - `idx_time_slots_treatment_date` (treatment_id, date, is_available)

3. **Notification Processing**:
   - `idx_booking_notifications_status_created` (status, created_at)
   - `idx_booking_notifications_booking_type` (booking_id, notification_type)

### Composite Indexes for Complex Queries
```sql
-- For finding available slots
CREATE INDEX idx_time_slots_availability_search 
ON time_slots (venue_id, date, is_available, is_blocked, treatment_id);

-- For booking dashboard queries
CREATE INDEX idx_bookings_user_dashboard 
ON bookings (user_id, status, booking_date DESC);

-- For venue owner dashboard
CREATE INDEX idx_bookings_venue_dashboard 
ON bookings (venue_id, booking_date, status);
```

## Security and Data Integrity

### 1. Constraints and Validations
- Ensure booking end_time > start_time
- Ensure booking_date >= current_date
- Ensure time_slot capacity >= booked_count
- Validate booking reference uniqueness

### 2. Soft Deletes
All booking-related tables use soft deletes to maintain audit trail:
- bookings.deleted_at
- time_slots.deleted_at
- booking_policies.deleted_at

### 3. Audit Trail
Key fields for audit tracking:
- Who created/modified bookings (user_id)
- When bookings were confirmed/cancelled
- Original booking details vs modifications

## Migration Order

The migrations should be created in this order to respect foreign key dependencies:

1. **Modify existing tables** (venues, treatments, users)
2. **Create booking_policies** (no dependencies)
3. **Create time_slots** (depends on venues, treatments, users)
4. **Create bookings** (depends on users, venues, treatments, time_slots)
5. **Create booking_notifications** (depends on bookings)
6. **Create venue_availability_templates** (depends on venues, treatments)
7. **Create treatment_venue_availability** (depends on treatments, venues)
8. **Create indexes and constraints**
9. **Seed default data** (default policies, admin users)

## Estimated Storage Requirements

Based on expected usage patterns:

- **bookings**: ~100-1000 records per day = 36K-365K per year
- **time_slots**: ~50-500 slots per venue per day = high volume
- **booking_notifications**: 2-5 per booking = 2-5x booking volume
- **booking_policies**: Low volume (~10-100 records total)

Total estimated storage: 1-10GB per year depending on venue count and booking volume.

## Next Steps

1. Create Laravel migrations for each table modification and new table
2. Update Eloquent models with new relationships
3. Create model factories for testing
4. Implement database seeders for default data
5. Add validation rules for booking constraints
6. Create database indexes for performance optimization

This schema plan provides a robust foundation for the booking system while maintaining data integrity, performance, and scalability.