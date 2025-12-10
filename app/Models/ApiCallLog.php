<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiCallLog extends Model
{
    /** @use HasFactory<\Database\Factories\ApiCallLogFactory> */
    use HasFactory;

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
     * Scope to get logs by city slug.
     */
    public function scopeByCity($query, string $citySlug)
    {
        return $query->where('city_slug', $citySlug);
    }

    /**
     * Scope to get logs by status code.
     */
    public function scopeByStatusCode($query, int $statusCode)
    {
        return $query->where('status_code', $statusCode);
    }

    /**
     * Scope to get successful calls (2xx status codes).
     */
    public function scopeSuccessful($query)
    {
        return $query->whereBetween('status_code', [200, 299]);
    }

    /**
     * Scope to get failed calls (4xx and 5xx status codes).
     */
    public function scopeFailed($query)
    {
        return $query->where('status_code', '>=', 400);
    }

    /**
     * Scope to get logs within a time range.
     */
    public function scopeWithinTimeRange($query, $startTime, $endTime)
    {
        return $query->whereBetween('called_at', [$startTime, $endTime]);
    }

    /**
     * Scope to get logs for a specific endpoint.
     */
    public function scopeForEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    /**
     * Scope to get slow calls (above threshold).
     */
    public function scopeSlowCalls($query, int $thresholdMs = 5000)
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
     * Get average response time for an endpoint.
     */
    public static function getAverageResponseTime(string $endpoint): ?float
    {
        return self::forEndpoint($endpoint)
            ->whereNotNull('response_time')
            ->avg('response_time');
    }

    /**
     * Get success rate for an endpoint.
     */
    public static function getSuccessRate(string $endpoint): float
    {
        $total = self::forEndpoint($endpoint)->count();

        if ($total === 0) {
            return 0.0;
        }

        $successful = self::forEndpoint($endpoint)->successful()->count();

        return ($successful / $total) * 100;
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
        $logs = self::withinTimeRange($startTime, $endTime)->get();

        if ($logs->isEmpty()) {
            return [
                'total_calls' => 0,
                'successful_calls' => 0,
                'failed_calls' => 0,
                'success_rate' => 0.0,
                'average_response_time' => null,
                'total_data_points' => 0,
            ];
        }

        $successful = $logs->where('status_code', '>=', 200)->where('status_code', '<', 300);
        $failed = $logs->where('status_code', '>=', 400);
        $responseTimes = $logs->whereNotNull('response_time')->pluck('response_time');

        return [
            'total_calls' => $logs->count(),
            'successful_calls' => $successful->count(),
            'failed_calls' => $failed->count(),
            'success_rate' => ($successful->count() / $logs->count()) * 100,
            'average_response_time' => $responseTimes->avg(),
            'total_data_points' => $logs->sum('data_points_extracted'),
        ];
    }
}
