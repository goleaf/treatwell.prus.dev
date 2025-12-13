<?php

namespace App\Services;

use App\Models\ApiCallLog;
use App\Models\City;
use App\Models\ParseProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProgressTracker
{
    /**
     * Mark a city as started for parsing.
     */
    public function markCityAsStarted(string $citySlug): ParseProgress
    {
        $progress = ParseProgress::firstOrCreate(
            ['city_slug' => $citySlug],
            [
                'status' => 'pending',
                'venues_found' => 0,
                'treatments_found' => 0,
                'api_calls_made' => 0,
                'metadata' => [],
            ]
        );

        $progress->markAsStarted();

        Log::info('City parsing started', [
            'city_slug' => $citySlug,
            'progress_id' => $progress->id,
        ]);

        return $progress;
    }

    /**
     * Mark a city as completed with statistics.
     */
    public function markCityAsCompleted(string $citySlug, array $stats): ParseProgress
    {
        $progress = ParseProgress::where('city_slug', $citySlug)->firstOrFail();
        $progress->markAsCompleted($stats);

        Log::info('City parsing completed', [
            'city_slug' => $citySlug,
            'progress_id' => $progress->id,
            'stats' => $stats,
        ]);

        return $progress;
    }

    /**
     * Mark a city as failed with error message.
     */
    public function markCityAsFailed(string $citySlug, string $error, array $stats = []): ParseProgress
    {
        $progress = ParseProgress::where('city_slug', $citySlug)->firstOrFail();
        $progress->markAsFailed($error, $stats);

        Log::error('City parsing failed', [
            'city_slug' => $citySlug,
            'progress_id' => $progress->id,
            'error' => $error,
            'stats' => $stats,
        ]);

        return $progress;
    }

    /**
     * Get the last successfully processed city for resumability.
     */
    public function getLastProcessedCity(): ?string
    {
        $lastCompleted = ParseProgress::completed()
            ->orderBy('completed_at', 'desc')
            ->first();

        return $lastCompleted?->city_slug;
    }

    /**
     * Get cities that need to be processed (pending or failed).
     */
    public function getCitiesToProcess(): Collection
    {
        $processedCitySlugs = ParseProgress::completed()
            ->pluck('city_slug')
            ->toArray();

        return City::whereNotIn('slug', $processedCitySlugs)
            ->orWhereIn('slug', function ($query) {
                $query->select('city_slug')
                    ->from('parse_progress')
                    ->where('status', 'failed');
            })
            ->get();
    }

    /**
     * Get cities that are currently being processed.
     */
    public function getCitiesInProgress(): Collection
    {
        return ParseProgress::processing()
            ->with('city')
            ->get()
            ->pluck('city')
            ->filter();
    }

    /**
     * Get overall progress summary.
     */
    public function getOverallProgress(): ProgressSummary
    {
        $totalCities = City::count();
        $completedCount = ParseProgress::completed()->count();
        $failedCount = ParseProgress::failed()->count();
        $processingCount = ParseProgress::processing()->count();
        $pendingCount = $totalCities - $completedCount - $failedCount - $processingCount;

        $totalStats = ParseProgress::completed()
            ->selectRaw('
                SUM(venues_found) as total_venues,
                SUM(treatments_found) as total_treatments,
                SUM(api_calls_made) as total_api_calls
            ')
            ->first();

        $averageProcessingTime = ParseProgress::completed()
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->get()
            ->map(fn ($progress) => $progress->getProcessingDuration())
            ->filter()
            ->avg();

        return new ProgressSummary([
            'total_cities' => $totalCities,
            'completed_cities' => $completedCount,
            'failed_cities' => $failedCount,
            'processing_cities' => $processingCount,
            'pending_cities' => $pendingCount,
            'completion_percentage' => $totalCities > 0 ? ($completedCount / $totalCities) * 100 : 0,
            'total_venues_found' => $totalStats->total_venues ?? 0,
            'total_treatments_found' => $totalStats->total_treatments ?? 0,
            'total_api_calls_made' => $totalStats->total_api_calls ?? 0,
            'average_processing_time_seconds' => $averageProcessingTime,
        ]);
    }

    /**
     * Get progress for a specific city.
     */
    public function getCityProgress(string $citySlug): ?ParseProgress
    {
        return ParseProgress::where('city_slug', $citySlug)->first();
    }

    /**
     * Get failed cities with error details.
     */
    public function getFailedCities(): Collection
    {
        return ParseProgress::failed()
            ->with('city')
            ->orderBy('completed_at', 'desc')
            ->get();
    }

    /**
     * Reset progress for a specific city (for retry).
     */
    public function resetCityProgress(string $citySlug): void
    {
        ParseProgress::where('city_slug', $citySlug)->delete();

        Log::info('City progress reset', ['city_slug' => $citySlug]);
    }

    /**
     * Reset all progress (for complete restart).
     */
    public function resetAllProgress(): void
    {
        ParseProgress::truncate();
        ApiCallLog::truncate();

        Log::warning('All parsing progress reset');
    }

    /**
     * Get cities to resume from after interruption.
     */
    public function getCitiesForResume(): Collection
    {
        // Get cities that were processing but didn't complete
        $stuckProcessing = ParseProgress::processing()
            ->where('started_at', '<', now()->subHours(2)) // Assume stuck if processing for >2 hours
            ->pluck('city_slug');

        // Reset stuck processing cities to pending
        if ($stuckProcessing->isNotEmpty()) {
            ParseProgress::whereIn('city_slug', $stuckProcessing)
                ->update(['status' => 'pending']);

            Log::info('Reset stuck processing cities', [
                'city_slugs' => $stuckProcessing->toArray(),
            ]);
        }

        return $this->getCitiesToProcess();
    }

    /**
     * Update progress statistics during processing.
     */
    public function updateProgressStats(string $citySlug, array $stats): void
    {
        $progress = ParseProgress::where('city_slug', $citySlug)->first();

        if (!$progress) {
            return;
        }

        $updateData = [];

        if (isset($stats['venues_found'])) {
            $updateData['venues_found'] = $stats['venues_found'];
        }

        if (isset($stats['treatments_found'])) {
            $updateData['treatments_found'] = $stats['treatments_found'];
        }

        if (isset($stats['api_calls_made'])) {
            $updateData['api_calls_made'] = $stats['api_calls_made'];
        }

        if (isset($stats['metadata'])) {
            $currentMetadata = $progress->metadata ?? [];
            $updateData['metadata'] = array_merge($currentMetadata, $stats['metadata']);
        }

        if (!empty($updateData)) {
            $progress->update($updateData);
        }
    }

    /**
     * Get performance statistics for a time period.
     */
    public function getPerformanceStats(\DateTimeInterface $startTime, \DateTimeInterface $endTime): array
    {
        $completedInPeriod = ParseProgress::completed()
            ->whereBetween('completed_at', [$startTime, $endTime])
            ->get();

        $apiStats = ApiCallLog::getPerformanceStats($startTime, $endTime);

        $processingTimes = $completedInPeriod
            ->map(fn ($progress) => $progress->getProcessingDuration())
            ->filter();

        return [
            'period' => [
                'start' => $startTime->format('Y-m-d H:i:s'),
                'end' => $endTime->format('Y-m-d H:i:s'),
            ],
            'cities_completed' => $completedInPeriod->count(),
            'total_venues_found' => $completedInPeriod->sum('venues_found'),
            'total_treatments_found' => $completedInPeriod->sum('treatments_found'),
            'average_processing_time' => $processingTimes->avg(),
            'min_processing_time' => $processingTimes->min(),
            'max_processing_time' => $processingTimes->max(),
            'api_performance' => $apiStats,
        ];
    }

    /**
     * Check if parsing can be resumed from a specific city.
     */
    public function canResumeFromCity(string $citySlug): bool
    {
        $progress = $this->getCityProgress($citySlug);

        return $progress && $progress->isTerminal();
    }

    /**
     * Get the next city to process based on current progress.
     */
    public function getNextCityToProcess(): ?City
    {
        $citiesToProcess = $this->getCitiesToProcess();

        return $citiesToProcess->first();
    }

    /**
     * Generate a detailed progress report.
     */
    public function generateProgressReport(): array
    {
        $summary = $this->getOverallProgress();
        $failedCities = $this->getFailedCities();
        $processingCities = $this->getCitiesInProgress();

        $recentPerformance = $this->getPerformanceStats(
            now()->subDay(),
            now()
        );

        return [
            'summary' => $summary->toArray(),
            'failed_cities' => $failedCities->map(function ($progress) {
                return [
                    'city_slug' => $progress->city_slug,
                    'city_name' => $progress->city?->name,
                    'error_message' => $progress->error_message,
                    'failed_at' => $progress->completed_at?->format('Y-m-d H:i:s'),
                    'stats' => [
                        'venues_found' => $progress->venues_found,
                        'treatments_found' => $progress->treatments_found,
                        'api_calls_made' => $progress->api_calls_made,
                    ],
                ];
            })->toArray(),
            'processing_cities' => $processingCities->map(function ($city) {
                $progress = $this->getCityProgress($city->slug);

                return [
                    'city_slug' => $city->slug,
                    'city_name' => $city->name,
                    'started_at' => $progress?->started_at?->format('Y-m-d H:i:s'),
                    'duration_seconds' => $progress?->getProcessingDuration(),
                ];
            })->toArray(),
            'recent_performance' => $recentPerformance,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}

/**
 * Progress summary data structure.
 */
class ProgressSummary
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function getTotalCities(): int
    {
        return $this->data['total_cities'];
    }

    public function getCompletedCities(): int
    {
        return $this->data['completed_cities'];
    }

    public function getFailedCities(): int
    {
        return $this->data['failed_cities'];
    }

    public function getProcessingCities(): int
    {
        return $this->data['processing_cities'];
    }

    public function getPendingCities(): int
    {
        return $this->data['pending_cities'];
    }

    public function getCompletionPercentage(): float
    {
        return $this->data['completion_percentage'];
    }

    public function getTotalVenuesFound(): int
    {
        return $this->data['total_venues_found'];
    }

    public function getTotalTreatmentsFound(): int
    {
        return $this->data['total_treatments_found'];
    }

    public function getTotalApiCallsMade(): int
    {
        return $this->data['total_api_calls_made'];
    }

    public function getAverageProcessingTime(): ?float
    {
        return $this->data['average_processing_time_seconds'];
    }
}
