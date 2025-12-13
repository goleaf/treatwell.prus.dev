<?php

namespace App\Repositories;

use App\Models\Booking;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BookingRepository
{
    /**
     * Get bookings for user dashboard with optimized queries
     */
    public function getUserBookings(int $userId, array $filters = []): LengthAwarePaginator
    {
        return Booking::query()
            ->forUser($userId)
            ->withDashboardData()
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->byStatus($status))
            ->when($filters['upcoming'] ?? false, fn ($q) => $q->upcoming())
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->where('booking_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->where('booking_date', '<=', $date))
            ->latest('booking_date')
            ->paginate(15);
    }

    /**
     * Get venue bookings for owner dashboard with performance optimization
     */
    public function getVenueBookings(int $venueId, array $filters = []): LengthAwarePaginator
    {
        return Booking::query()
            ->forVenueOwnerDashboard($venueId)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->byStatus($status))
            ->when($filters['date'] ?? null, fn ($q, $date) => $q->whereDate('booking_date', $date))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->where('booking_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->where('booking_date', '<=', $date))
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->paginate(20);
    }

    /**
     * Get booking statistics with caching
     */
    public function getBookingStats(int $venueId, string $period = '30d'): array
    {
        $cacheKey = "booking_stats_{$venueId}_{$period}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($venueId, $period) {
            $startDate = match ($period) {
                '7d' => now()->subDays(7),
                '30d' => now()->subDays(30),
                '90d' => now()->subDays(90),
                default => now()->subDays(30)
            };

            return [
                'total_bookings' => $this->getBookingCount($venueId, $startDate),
                'confirmed_bookings' => $this->getBookingCount($venueId, $startDate, 'confirmed'),
                'cancelled_bookings' => $this->getBookingCount($venueId, $startDate, 'cancelled'),
                'total_revenue' => $this->getTotalRevenue($venueId, $startDate),
                'popular_treatments' => $this->getPopularTreatments($venueId, $startDate),
                'booking_trends' => $this->getBookingTrends($venueId, $startDate),
            ];
        });
    }

    /**
     * Find available time slots with optimized query
     */
    public function findAvailableSlots(int $venueId, string $date, ?int $treatmentId = null): \Illuminate\Support\Collection
    {
        return DB::table('time_slots')
            ->leftJoin('bookings', function ($join) {
                $join->on('time_slots.id', '=', 'bookings.time_slot_id')
                    ->whereIn('bookings.status', ['pending', 'confirmed'])
                    ->whereNull('bookings.deleted_at');
            })
            ->where('time_slots.venue_id', $venueId)
            ->where('time_slots.date', $date)
            ->where('time_slots.is_available', true)
            ->where('time_slots.is_blocked', false)
            ->when($treatmentId, fn ($q) => $q->where('time_slots.treatment_id', $treatmentId))
            ->whereRaw('time_slots.capacity > COALESCE(time_slots.booked_count, 0)')
            ->whereNull('bookings.id') // No existing booking
            ->select([
                'time_slots.id',
                'time_slots.date',
                'time_slots.start_time',
                'time_slots.end_time',
                'time_slots.capacity',
                'time_slots.booked_count',
            ])
            ->orderBy('time_slots.start_time')
            ->get();
    }

    /**
     * Get booking count for a venue and period
     */
    private function getBookingCount(int $venueId, $startDate, ?string $status = null): int
    {
        return Booking::query()
            ->where('venue_id', $venueId)
            ->where('booking_date', '>=', $startDate->toDateString())
            ->when($status, fn ($q) => $q->where('status', $status))
            ->count();
    }

    /**
     * Get total revenue for a venue and period
     */
    private function getTotalRevenue(int $venueId, $startDate): float
    {
        return Booking::query()
            ->where('venue_id', $venueId)
            ->where('booking_date', '>=', $startDate->toDateString())
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('price');
    }

    /**
     * Get popular treatments for a venue
     */
    private function getPopularTreatments(int $venueId, $startDate): array
    {
        return Booking::query()
            ->select(['treatment_id', DB::raw('COUNT(*) as booking_count'), DB::raw('SUM(price) as total_revenue')])
            ->with('treatment:id,name')
            ->where('venue_id', $venueId)
            ->where('booking_date', '>=', $startDate->toDateString())
            ->whereIn('status', ['confirmed', 'completed'])
            ->groupBy('treatment_id')
            ->orderByDesc('booking_count')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Get booking trends over time
     */
    private function getBookingTrends(int $venueId, $startDate): array
    {
        return Booking::query()
            ->select([
                DB::raw('DATE(booking_date) as date'),
                DB::raw('COUNT(*) as bookings'),
                DB::raw('SUM(price) as revenue'),
            ])
            ->where('venue_id', $venueId)
            ->where('booking_date', '>=', $startDate->toDateString())
            ->whereIn('status', ['confirmed', 'completed'])
            ->groupBy(DB::raw('DATE(booking_date)'))
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Bulk update booking statuses with transaction
     */
    public function bulkUpdateStatus(array $bookingIds, string $status): bool
    {
        return DB::transaction(function () use ($bookingIds, $status) {
            $timestamp = now();
            $updateData = ['status' => $status];

            // Add appropriate timestamp based on status
            match ($status) {
                'confirmed' => $updateData['confirmed_at'] = $timestamp,
                'cancelled' => $updateData['cancelled_at'] = $timestamp,
                'completed' => $updateData['completed_at'] = $timestamp,
                default => null
            };

            return Booking::whereIn('id', $bookingIds)->update($updateData);
        });
    }

    /**
     * Clear booking statistics cache
     */
    public function clearStatsCache(int $venueId): void
    {
        $periods = ['7d', '30d', '90d'];
        foreach ($periods as $period) {
            Cache::forget("booking_stats_{$venueId}_{$period}");
        }
    }
}
