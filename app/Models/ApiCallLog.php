<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiCallLog extends Model
{
    /** @use HasFactory<\Database\Factories\ApiCallLogFactory> */
    use HasFactory;

    // HTTP Status Code Constants
    public const STATUS_SUCCESS_MIN = 200;

    public const STATUS_SUCCESS_MAX = 299;

    public const STATUS_CLIENT_ERROR_MIN = 400;

    public const STATUS_SERVER_ERROR_MIN = 500;

    // Performance Thresholds
    public const DEFAULT_SLOW_THRESHOLD_MS = 5000;

    public const MILLISECONDS_TO_NANOSECONDS = 1_000_000;

    protected $fillable = [
        'endpoint',
        'city_slug',
        'status_code',
        'response_time',
        'data_points_extracted',
        'error_message',
        'called_at',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'response_time' => 'integer',
            'data_points_extracted' => 'integer',
            'called_at' => 'datetime',
        ];
    }

    /**
     * Get the city associated with this API call log.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_slug', 'slug');
    }

    /**
     * Scope to eager load city relationship efficiently.
     */
    public function scopeWithCity(Builder $query): Builder
    {
        return $query->with('city:slug,name,id');
    }

    /**
     * Scope to get logs by city slug.
     */
    public function scopeByCity(Builder $query, string $citySlug): Builder
    {
        return $query->where('city_slug', $citySlug);
    }

    /**
     * Scope to get logs by status code.
     */
    public function scopeByStatusCode(Builder $query, int $statusCode): Builder
    {
        return $query->where('status_code', $statusCode);
    }

    /**
     * Scope to get successful calls (2xx status codes).
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->whereBetween('status_code', [self::STATUS_SUCCESS_MIN, self::STATUS_SUCCESS_MAX]);
    }

    /**
     * Scope to get failed calls (4xx and 5xx status codes).
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status_code', '>=', self::STATUS_CLIENT_ERROR_MIN);
    }

    /**
     * Scope to get logs within a time range.
     */
    public function scopeWithinTimeRange(Builder $query, $startTime, $endTime): Builder
    {
        return $query->whereBetween('called_at', [$startTime, $endTime]);
    }

    /**
     * Scope to get logs for a specific endpoint.
     */
    public function scopeForEndpoint(Builder $query, string $endpoint): Builder
    {
        return $query->where('endpoint', $endpoint);
    }

    /**
     * Scope to get slow calls (above threshold).
     */
    public function scopeSlowCalls(Builder $query, int $thresholdMs = self::DEFAULT_SLOW_THRESHOLD_MS): Builder
    {
        return $query->where('response_time', '>', $thresholdMs);
    }

    /**
     * Create a log entry for an API call.
     */
    public static function logCall(
        string $endpoint,
        ?string $citySlug = null,
        ?int $statusCode = null,
        ?int $responseTime = null,
        int $dataPointsExtracted = 0,
        ?string $errorMessage = null
    ): self {
        return self::create([
            'endpoint' => $endpoint,
            'city_slug' => $citySlug,
            'status_code' => $statusCode,
            'response_time' => $responseTime,
            'data_points_extracted' => $dataPointsExtracted,
            'error_message' => $errorMessage,
            'called_at' => now(),
        ]);
    }

    /**
     * Bulk insert multiple API call logs for better performance.
     */
    public static function logBulkCalls(array $calls): bool
    {
        $now = now();
        $data = collect($calls)->map(function ($call) use ($now) {
            return [
                'endpoint' => $call['endpoint'],
                'city_slug' => $call['city_slug'] ?? null,
                'status_code' => $call['status_code'] ?? null,
                'response_time' => $call['response_time'] ?? null,
                'data_points_extracted' => $call['data_points_extracted'] ?? 0,
                'error_message' => $call['error_message'] ?? null,
                'called_at' => $call['called_at'] ?? $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();

        return self::insert($data);
    }

    /**
     * Get average response time for an endpoint.
     */
    public static function getAverageResponseTime(string $endpoint): ?float
    {
        return cache()->remember(
            "api_call_log_avg_response_time_{$endpoint}",
            now()->addMinutes(5),
            fn () => self::forEndpoint($endpoint)
                ->whereNotNull('response_time')
                ->avg('response_time')
        );
    }

    /**
     * Get success rate for an endpoint.
     */
    public static function getSuccessRate(string $endpoint): float
    {
        return cache()->remember(
            "api_call_log_success_rate_{$endpoint}",
            now()->addMinutes(5),
            function () use ($endpoint) {
                $query = self::forEndpoint($endpoint);
                $total = $query->count();

                if ($total === 0) {
                    return 0.0;
                }

                $successful = (clone $query)->successful()->count();

                return ($successful / $total) * 100;
            }
        );
    }

    /**
     * Get error statistics for a city.
     */
    public static function getErrorStats(string $citySlug): array
    {
        $logs = self::byCity($citySlug)->failed()->get();

        $errorCounts = [];
        foreach ($logs as $log) {
            $statusCode = $log->status_code ?? 'unknown';
            $errorCounts[$statusCode] = ($errorCounts[$statusCode] ?? 0) + 1;
        }

        return [
            'total_errors' => $logs->count(),
            'error_breakdown' => $errorCounts,
            'most_common_error' => $errorCounts ? array_keys($errorCounts, max($errorCounts))[0] : null,
        ];
    }

    /**
     * Get performance statistics for a time period.
     */
    public static function getPerformanceStats($startTime, $endTime): array
    {
        // Use database aggregation instead of loading all records into memory
        $baseQuery = self::withinTimeRange($startTime, $endTime);

        $totalCalls = $baseQuery->count();

        if ($totalCalls === 0) {
            return self::getEmptyPerformanceStats();
        }

        $successfulCalls = (clone $baseQuery)->successful()->count();
        $failedCalls = (clone $baseQuery)->failed()->count();
        $avgResponseTime = (clone $baseQuery)->whereNotNull('response_time')->avg('response_time');
        $totalDataPoints = (clone $baseQuery)->sum('data_points_extracted');

        return [
            'total_calls' => $totalCalls,
            'successful_calls' => $successfulCalls,
            'failed_calls' => $failedCalls,
            'success_rate' => ($successfulCalls / $totalCalls) * 100,
            'average_response_time' => $avgResponseTime,
            'total_data_points' => $totalDataPoints,
        ];
    }

    /**
     * Get empty performance statistics structure.
     */
    private static function getEmptyPerformanceStats(): array
    {
        return [
            'total_calls' => 0,
            'successful_calls' => 0,
            'failed_calls' => 0,
            'success_rate' => 0.0,
            'average_response_time' => null,
            'total_data_points' => 0,
        ];
    }

    /**
     * Check if the API call was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status_code >= self::STATUS_SUCCESS_MIN
            && $this->status_code <= self::STATUS_SUCCESS_MAX;
    }

    /**
     * Check if the API call failed.
     */
    public function isFailed(): bool
    {
        return $this->status_code >= self::STATUS_CLIENT_ERROR_MIN;
    }

    /**
     * Check if the API call was slow.
     */
    public function isSlow(int $thresholdMs = self::DEFAULT_SLOW_THRESHOLD_MS): bool
    {
        return $this->response_time !== null && $this->response_time > $thresholdMs;
    }

    /**
     * Get response time in seconds.
     */
    public function getResponseTimeInSeconds(): ?float
    {
        return $this->response_time ? $this->response_time / 1000 : null;
    }

    /**
     * Clear cached statistics for an endpoint.
     */
    public static function clearEndpointCache(string $endpoint): void
    {
        cache()->forget("api_call_log_avg_response_time_{$endpoint}");
        cache()->forget("api_call_log_success_rate_{$endpoint}");
    }

    /**
     * Boot method to handle cache invalidation on model events.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($log) {
            if ($log->endpoint) {
                self::clearEndpointCache($log->endpoint);
            }
        });

        static::updated(function ($log) {
            if ($log->endpoint) {
                self::clearEndpointCache($log->endpoint);
            }
        });
    }
}
