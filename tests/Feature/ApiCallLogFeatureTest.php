<?php

namespace Tests\Feature;

use App\Models\ApiCallLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ApiCallLogFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_bulk_calls(): void
    {
        $calls = [
            [
                'endpoint' => '/api/test1',
                'city_slug' => 'test-city',
                'status_code' => 200,
                'response_time' => 1000,
                'data_points_extracted' => 5,
            ],
            [
                'endpoint' => '/api/test2',
                'city_slug' => 'test-city-2',
                'status_code' => 404,
                'response_time' => 500,
                'data_points_extracted' => 0,
                'error_message' => 'Not found',
            ],
        ];

        $result = ApiCallLog::logBulkCalls($calls);

        $this->assertTrue($result);
        $this->assertDatabaseCount('api_call_logs', 2);
        $this->assertDatabaseHas('api_call_logs', [
            'endpoint' => '/api/test1',
            'status_code' => 200,
        ]);
        $this->assertDatabaseHas('api_call_logs', [
            'endpoint' => '/api/test2',
            'status_code' => 404,
        ]);
    }

    public function test_clear_endpoint_cache(): void
    {
        $endpoint = '/api/test';

        // Set some cache values
        Cache::put("api_call_log_avg_response_time_{$endpoint}", 1000, 300);
        Cache::put("api_call_log_success_rate_{$endpoint}", 95.5, 300);

        // Verify cache exists
        $this->assertTrue(Cache::has("api_call_log_avg_response_time_{$endpoint}"));
        $this->assertTrue(Cache::has("api_call_log_success_rate_{$endpoint}"));

        // Clear cache
        ApiCallLog::clearEndpointCache($endpoint);

        // Verify cache is cleared
        $this->assertFalse(Cache::has("api_call_log_avg_response_time_{$endpoint}"));
        $this->assertFalse(Cache::has("api_call_log_success_rate_{$endpoint}"));
    }

    public function test_performance_stats_uses_database_aggregation(): void
    {
        // Create test data
        ApiCallLog::factory()->create([
            'endpoint' => '/api/test',
            'status_code' => 200,
            'response_time' => 1000,
            'data_points_extracted' => 5,
            'called_at' => now()->subMinutes(30),
        ]);

        ApiCallLog::factory()->create([
            'endpoint' => '/api/test',
            'status_code' => 500,
            'response_time' => 2000,
            'data_points_extracted' => 0,
            'called_at' => now()->subMinutes(15),
        ]);

        $stats = ApiCallLog::getPerformanceStats(now()->subHour(), now());

        $this->assertEquals(2, $stats['total_calls']);
        $this->assertEquals(1, $stats['successful_calls']);
        $this->assertEquals(1, $stats['failed_calls']);
        $this->assertEquals(50.0, $stats['success_rate']);
        $this->assertEquals(1500.0, $stats['average_response_time']);
        $this->assertEquals(5, $stats['total_data_points']);
    }

    public function test_caching_for_endpoint_statistics(): void
    {
        ApiCallLog::factory()->create([
            'endpoint' => '/api/cached-test',
            'status_code' => 200,
            'response_time' => 1500,
        ]);

        // First call should hit database and cache result
        $avgTime1 = ApiCallLog::getAverageResponseTime('/api/cached-test');
        $successRate1 = ApiCallLog::getSuccessRate('/api/cached-test');

        // Second call should use cached result
        $avgTime2 = ApiCallLog::getAverageResponseTime('/api/cached-test');
        $successRate2 = ApiCallLog::getSuccessRate('/api/cached-test');

        $this->assertEquals($avgTime1, $avgTime2);
        $this->assertEquals($successRate1, $successRate2);
        $this->assertEquals(1500.0, $avgTime1);
        $this->assertEquals(100.0, $successRate1);
    }
}
