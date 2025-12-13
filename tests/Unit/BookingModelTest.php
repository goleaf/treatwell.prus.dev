<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\TimeSlot;
use App\Models\Treatment;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_can_be_created_with_factory(): void
    {
        $booking = Booking::factory()->create();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'booking_reference' => $booking->booking_reference,
        ]);

        $this->assertNotNull($booking->booking_reference);
        $this->assertStringStartsWith('BK-', $booking->booking_reference);
    }

    public function test_booking_relationships_work(): void
    {
        $user = User::factory()->create();
        $venue = Venue::factory()->create();
        $treatment = Treatment::factory()->create(['venue_id' => $venue->id]);
        $timeSlot = TimeSlot::factory()->create(['venue_id' => $venue->id]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'venue_id' => $venue->id,
            'treatment_id' => $treatment->id,
            'time_slot_id' => $timeSlot->id,
        ]);

        $this->assertEquals($user->id, $booking->user->id);
        $this->assertEquals($venue->id, $booking->venue->id);
        $this->assertEquals($treatment->id, $booking->treatment->id);
        $this->assertEquals($timeSlot->id, $booking->timeSlot->id);
    }

    public function test_booking_reference_is_generated_automatically(): void
    {
        $booking = Booking::factory()->create();

        $this->assertNotNull($booking->booking_reference);
        $this->assertStringStartsWith('BK-', $booking->booking_reference);
        $this->assertStringContainsString(date('Y'), $booking->booking_reference);
    }

    public function test_booking_scopes_work(): void
    {
        $activeBooking = Booking::factory()->confirmed()->create();
        $cancelledBooking = Booking::factory()->cancelled()->create();
        $upcomingBooking = Booking::factory()->tomorrow()->create();

        $activeBookings = Booking::active()->get();
        $upcomingBookings = Booking::upcoming()->get();

        $this->assertTrue($activeBookings->contains($activeBooking));
        $this->assertFalse($activeBookings->contains($cancelledBooking));
        $this->assertTrue($upcomingBookings->contains($upcomingBooking));
    }

    public function test_booking_business_logic_methods(): void
    {
        $confirmedBooking = Booking::factory()->confirmed()->create([
            'cancellation_deadline' => now()->addHours(25),
        ]);

        $this->assertTrue($confirmedBooking->canBeCancelled());
        $this->assertTrue($confirmedBooking->canBeModified());
        $this->assertTrue($confirmedBooking->isUpcoming());
        $this->assertFalse($confirmedBooking->isPast());
    }

    public function test_booking_formatting_methods(): void
    {
        $booking = Booking::factory()->create([
            'price' => 75.50,
            'currency' => 'EUR',
            'duration' => 90,
        ]);

        $this->assertEquals('EUR75.50', $booking->getFormattedPrice());
        $this->assertEquals('1h 30m', $booking->getFormattedDuration());
        $this->assertIsString($booking->getStatusLabel());
        $this->assertIsString($booking->getStatusColor());
    }
}
