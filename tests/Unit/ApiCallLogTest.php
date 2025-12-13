<?php

namespace Tests\Unit;

use App\Models\ApiCallLog;
use PHPUnit\Framework\TestCase;

class ApiCallLogTest extends TestCase
{
    public function test_constants_are_defined(): void
    {
        $this->assertEquals(200, ApiCallLog::STATUS_SUCCESS_MIN);
        $this->assertEquals(299, ApiCallLog::STATUS_SUCCESS_MAX);
        $this->assertEquals(400, ApiCallLog::STATUS_CLIENT_ERROR_MIN);
        $this->assertEquals(500, ApiCallLog::STATUS_SERVER_ERROR_MIN);
        $this->assertEquals(5000, ApiCallLog::DEFAULT_SLOW_THRESHOLD_MS);
    }

    public function test_is_successful_method(): void
    {
        $successfulLog = new ApiCallLog(['status_code' => 200]);
        $this->assertTrue($successfulLog->isSuccessful());

        $failedLog = new ApiCallLog(['status_code' => 404]);
        $this->assertFalse($failedLog->isSuccessful());

        $serverErrorLog = new ApiCallLog(['status_code' => 500]);
        $this->assertFalse($serverErrorLog->isSuccessful());
    }

    public function test_is_failed_method(): void
    {
        $successfulLog = new ApiCallLog(['status_code' => 200]);
        $this->assertFalse($successfulLog->isFailed());

        $clientErrorLog = new ApiCallLog(['status_code' => 404]);
        $this->assertTrue($clientErrorLog->isFailed());

        $serverErrorLog = new ApiCallLog(['status_code' => 500]);
        $this->assertTrue($serverErrorLog->isFailed());
    }

    public function test_is_slow_method(): void
    {
        $fastLog = new ApiCallLog(['response_time' => 1000]);
        $this->assertFalse($fastLog->isSlow());

        $slowLog = new ApiCallLog(['response_time' => 6000]);
        $this->assertTrue($slowLog->isSlow());

        $customThresholdLog = new ApiCallLog(['response_time' => 2000]);
        $this->assertTrue($customThresholdLog->isSlow(1500));
        $this->assertFalse($customThresholdLog->isSlow(3000));

        $nullResponseTimeLog = new ApiCallLog(['response_time' => null]);
        $this->assertFalse($nullResponseTimeLog->isSlow());
    }

    public function test_get_response_time_in_seconds(): void
    {
        $log = new ApiCallLog(['response_time' => 5000]);
        $this->assertEquals(5.0, $log->getResponseTimeInSeconds());

        $nullLog = new ApiCallLog(['response_time' => null]);
        $this->assertNull($nullLog->getResponseTimeInSeconds());
    }

    public function test_get_empty_performance_stats(): void
    {
        $reflection = new \ReflectionClass(ApiCallLog::class);
        $method = $reflection->getMethod('getEmptyPerformanceStats');
        $method->setAccessible(true);

        $stats = $method->invoke(null);

        $expected = [
            'total_calls' => 0,
            'successful_calls' => 0,
            'failed_calls' => 0,
            'success_rate' => 0.0,
            'average_response_time' => null,
            'total_data_points' => 0,
        ];

        $this->assertEquals($expected, $stats);
    }
}
