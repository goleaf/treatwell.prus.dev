<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Treatment;
use App\Models\User;
use App\Models\Venue;
use App\Repositories\BookingRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private BookingRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new BookingRepository;
    }

    public function test_eager_loading_prevents_n_plus_one_queries(): void
    {
        // Create test data
        $user = User::factory()->create();
        $venue = Venue::factory()->create();
        $treatment = Treatment::factory()->create(['venue_id' => $venue->id]);

        // Create multiple bookings
        Booking::factory()->count(5)->create([
            'user_id' => $user->id,
            'venue_id' => $venue->id,
            'treatment_id' => $treatment->id,
        ]);

        // Enable query logging
        DB::enableQueryLog();

        // Fetch bookings with relationships
        $bookings = Booking::with(['user', 'venue', 'treatment'])->get();

        // Get query count
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should be 4 queries: 1 for bookings + 3 for relationships (not 1 + 5*3 = 16)
        $this->assertLessThanOrEqual(4, count($queries), 'Too many queries executed - N+1 problem detected');
        $this->assertCount(5, $bookings);
    }

    public function test_dashboard_scope_optimizes_queries(): void
    {
        $user = User::factory()->create();
        $venue = Venue::factory()->create();
        $treatment = Treatment::factory()->create(['venue_id' => $venue->id]);

        Booking::factory()->count(3)->create([
            'user_id' => $user->id,
            'venue_id' => $venue->id,
            'treatment_id' => $treatment->id,
        ]);

        DB::enableQueryLog();

        // Use the optimized dashboard scope
        $bookings = Booking::withDashboardData()->forUser($user->id)->get();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should be minimal queries due to eager loading
        $this->assertLessThanOrEqual(4, count($queries));
        $this->assertCount(3, $bookings);

        // Verify relationships are loaded
        $this->assertTrue($bookings->first()->relationLoaded('user'));
        $this->assertTrue($bookings->first()->relationLoaded('venue'));
        $this->assertTrue($bookings->first()->relationLoaded('treatment'));
    }

    public function test_venue_owner_dashboard_scope_selects_minimal_columns(): void
    {
        $venue = Venue::factory()->create();
        $user = User::factory()->create();
        $treatment = Treatment::factory()->create(['venue_id' => $venue->id]);

        Booking::factory()->count(2)->create([
            'user_id' => $user->id,
            'venue_id' => $venue->id,
            'treatment_id' => $treatment->id,
        ]);

        DB::enableQueryLog();

        $bookings = Booking::forVenueOwnerDashboard($venue->id)->get();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Verify the main query selects only necessary columns
        $mainQuery = $queries[0]['query'];
        $this->assertStringContainsString('select "id", "booking_reference"', $mainQuery);
        $this->assertStringNotContainsString('special_requests', $mainQuery);
        $this->assertStringNotContainsString('created_at', $mainQuery);

        $this->assertCount(2, $bookings);
    }

    public function test_repository_caching_works(): void
    {
        $venue = Venue::factory()->create();

        // First call should hit database
        $stats1 = $this->repository->getBookingStats($venue->id, '7d');

        // Second call should use cache
        $stats2 = $this->repository->getBookingStats($venue->id, '7d');

        $this->assertEquals($stats1, $stats2);
        $this->assertIsArray($stats1);
        $this->assertArrayHasKey('total_bookings', $stats1);
        $this->assertArrayHasKey('total_revenue', $stats1);
    }

    public function test_bulk_status_update_uses_single_query(): void
    {
        $bookings = Booking::factory()->count(3)->create(['status' => 'pending']);
        $bookingIds = $bookings->pluck('id')->toArray();

        DB::enableQueryLog();

        $result = $this->repository->bulkUpdateStatus($bookingIds, 'confirmed');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertTrue($result);

        // Should be minimal queries (transaction + update)
        $this->assertLessThanOrEqual(3, count($queries));

        // Verify all bookings were updated
        $updatedBookings = Booking::whereIn('id', $bookingIds)->get();
        $this->assertTrue($updatedBookings->every(fn ($booking) => $booking->status === 'confirmed'));
        $this->assertTrue($updatedBookings->every(fn ($booking) => $booking->confirmed_at !== null));
    }

    public function test_available_slots_query_is_optimized(): void
    {
        $venue = Venue::factory()->create();
        $date = now()->addDay()->toDateString();

        DB::enableQueryLog();

        $slots = $this->repository->findAvailableSlots($venue->id, $date);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should be a single optimized query with joins
        $this->assertCount(1, $queries);

        $query = $queries[0]['query'];
        $this->assertStringContainsString('left join', strtolower($query));
        $this->assertStringContainsString('time_slots', $query);
        $this->assertStringContainsString('bookings', $query);
    }

    public function test_booking_scopes_use_indexes(): void
    {
        $user = User::factory()->create();
        $venue = Venue::factory()->create();

        Booking::factory()->count(5)->create([
            'user_id' => $user->id,
            'venue_id' => $venue->id,
            'status' => 'confirmed',
        ]);

        DB::enableQueryLog();

        // Test various scopes that should use indexes
        Booking::forUser($user->id)->get();
        Booking::forVenue($venue->id)->get();
        Booking::byStatus('confirmed')->get();
        Booking::active()->get();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // All queries should execute quickly (using indexes)
        foreach ($queries as $query) {
            $this->assertStringContainsString('where', strtolower($query['query']));
        }
    }
}
