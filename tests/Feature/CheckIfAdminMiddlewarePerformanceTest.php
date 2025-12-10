<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckIfAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class CheckIfAdminMiddlewarePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected CheckIfAdmin $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckIfAdmin;
    }

    public function test_middleware_execution_time_performance(): void
    {
        $request = Request::create('/admin/performance', 'GET');

        $next = function ($req) {
            return new Response('Performance test');
        };

        // Measure execution time over multiple iterations
        $iterations = 1000;
        $executionTimes = [];

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $this->middleware->handle($request, $next);
            $endTime = microtime(true);

            $executionTimes[] = ($endTime - $startTime) * 1000; // Convert to milliseconds
        }

        $averageTime = array_sum($executionTimes) / count($executionTimes);
        $maxTime = max($executionTimes);
        $minTime = min($executionTimes);

        // Assert performance benchmarks
        $this->assertLessThan(1.0, $averageTime, 'Average execution time should be less than 1ms');
        $this->assertLessThan(5.0, $maxTime, 'Maximum execution time should be less than 5ms');
        $this->assertGreaterThan(0, $minTime, 'Minimum execution time should be greater than 0');

        // Log performance metrics for analysis
        echo "\nMiddleware Performance Metrics:\n";
        echo 'Average execution time: '.number_format($averageTime, 4)."ms\n";
        echo 'Maximum execution time: '.number_format($maxTime, 4)."ms\n";
        echo 'Minimum execution time: '.number_format($minTime, 4)."ms\n";
        echo "Total iterations: {$iterations}\n";
    }

    public function test_middleware_memory_usage_performance(): void
    {
        $request = Request::create('/admin/memory', 'GET');

        $next = function ($req) {
            return new Response('Memory test');
        };

        // Measure memory usage
        $memoryBefore = memory_get_usage(true);
        $peakMemoryBefore = memory_get_peak_usage(true);

        // Execute middleware multiple times
        for ($i = 0; $i < 100; $i++) {
            $this->middleware->handle($request, $next);
        }

        $memoryAfter = memory_get_usage(true);
        $peakMemoryAfter = memory_get_peak_usage(true);

        $memoryIncrease = $memoryAfter - $memoryBefore;
        $peakMemoryIncrease = $peakMemoryAfter - $peakMemoryBefore;

        // Assert memory usage is reasonable
        $this->assertLessThan(1024 * 1024, $memoryIncrease, 'Memory increase should be less than 1MB');
        $this->assertLessThan(2 * 1024 * 1024, $peakMemoryIncrease, 'Peak memory increase should be less than 2MB');

        // Log memory metrics
        echo "\nMiddleware Memory Usage:\n";
        echo 'Memory increase: '.number_format($memoryIncrease / 1024, 2)."KB\n";
        echo 'Peak memory increase: '.number_format($peakMemoryIncrease / 1024, 2)."KB\n";
        echo 'Current memory usage: '.number_format($memoryAfter / 1024 / 1024, 2)."MB\n";
    }

    public function test_middleware_throughput_performance(): void
    {
        $request = Request::create('/admin/throughput', 'GET');

        $next = function ($req) {
            return new Response('Throughput test');
        };

        // Measure throughput over a fixed time period
        $testDuration = 1; // 1 second
        $startTime = microtime(true);
        $requestCount = 0;

        while ((microtime(true) - $startTime) < $testDuration) {
            $this->middleware->handle($request, $next);
            $requestCount++;
        }

        $actualDuration = microtime(true) - $startTime;
        $requestsPerSecond = $requestCount / $actualDuration;

        // Assert minimum throughput
        $this->assertGreaterThan(1000, $requestsPerSecond, 'Middleware should handle at least 1000 requests per second');

        // Log throughput metrics
        echo "\nMiddleware Throughput:\n";
        echo "Requests processed: {$requestCount}\n";
        echo 'Test duration: '.number_format($actualDuration, 3)."s\n";
        echo 'Requests per second: '.number_format($requestsPerSecond, 0)."\n";
    }

    public function test_middleware_performance_with_large_requests(): void
    {
        // Create a large request payload
        $largeData = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeData["field_{$i}"] = str_repeat('x', 100);
        }

        $request = Request::create('/admin/large', 'POST', $largeData);

        $next = function ($req) {
            return new Response('Large request processed');
        };

        // Measure performance with large requests
        $iterations = 100;
        $executionTimes = [];
        $memoryUsages = [];

        for ($i = 0; $i < $iterations; $i++) {
            $memoryBefore = memory_get_usage();
            $startTime = microtime(true);

            $this->middleware->handle($request, $next);

            $endTime = microtime(true);
            $memoryAfter = memory_get_usage();

            $executionTimes[] = ($endTime - $startTime) * 1000;
            $memoryUsages[] = $memoryAfter - $memoryBefore;
        }

        $averageTime = array_sum($executionTimes) / count($executionTimes);
        $averageMemory = array_sum($memoryUsages) / count($memoryUsages);

        // Assert performance with large requests
        $this->assertLessThan(10.0, $averageTime, 'Large request processing should be under 10ms on average');
        $this->assertLessThan(1024 * 10, $averageMemory, 'Memory usage per large request should be under 10KB');

        // Log large request performance
        echo "\nLarge Request Performance:\n";
        echo 'Average execution time: '.number_format($averageTime, 4)."ms\n";
        echo 'Average memory per request: '.number_format($averageMemory / 1024, 2)."KB\n";
        echo 'Request payload size: ~'.number_format(strlen(serialize($largeData)) / 1024, 2)."KB\n";
    }

    public function test_middleware_performance_under_concurrent_load(): void
    {
        $request = Request::create('/admin/concurrent', 'GET');

        $next = function ($req) {
            // Simulate some processing time
            usleep(100); // 0.1ms

            return new Response('Concurrent request processed');
        };

        // Simulate concurrent requests by running them in quick succession
        $concurrentRequests = 50;
        $startTime = microtime(true);
        $responses = [];

        for ($i = 0; $i < $concurrentRequests; $i++) {
            $responses[] = $this->middleware->handle($request, $next);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Verify all requests were processed
        $this->assertCount($concurrentRequests, $responses);
        foreach ($responses as $response) {
            $this->assertEquals('Concurrent request processed', $response->getContent());
        }

        // Assert reasonable performance under load
        $averageTimePerRequest = $totalTime / $concurrentRequests;
        $this->assertLessThan(2.0, $averageTimePerRequest, 'Average time per concurrent request should be under 2ms');

        // Log concurrent performance
        echo "\nConcurrent Load Performance:\n";
        echo "Total requests: {$concurrentRequests}\n";
        echo 'Total time: '.number_format($totalTime, 2)."ms\n";
        echo 'Average time per request: '.number_format($averageTimePerRequest, 4)."ms\n";
    }

    public function test_middleware_performance_with_different_request_types(): void
    {
        $requestTypes = [
            ['method' => 'GET', 'data' => []],
            ['method' => 'POST', 'data' => ['key' => 'value']],
            ['method' => 'PUT', 'data' => ['update' => 'data']],
            ['method' => 'DELETE', 'data' => []],
            ['method' => 'PATCH', 'data' => ['patch' => 'data']],
        ];

        $performanceResults = [];

        foreach ($requestTypes as $type) {
            $request = Request::create('/admin/test', $type['method'], $type['data']);

            $next = function ($req) use ($type) {
                return new Response("Processed {$type['method']} request");
            };

            // Measure performance for each request type
            $iterations = 200;
            $executionTimes = [];

            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);
                $this->middleware->handle($request, $next);
                $endTime = microtime(true);

                $executionTimes[] = ($endTime - $startTime) * 1000;
            }

            $averageTime = array_sum($executionTimes) / count($executionTimes);
            $performanceResults[$type['method']] = $averageTime;

            // Assert performance for each request type
            $this->assertLessThan(1.0, $averageTime, "{$type['method']} requests should process in under 1ms on average");
        }

        // Log performance by request type
        echo "\nPerformance by Request Type:\n";
        foreach ($performanceResults as $method => $avgTime) {
            echo "{$method}: ".number_format($avgTime, 4)."ms\n";
        }

        // Verify performance consistency across request types
        $maxVariation = max($performanceResults) - min($performanceResults);
        $this->assertLessThan(0.5, $maxVariation, 'Performance variation between request types should be minimal');
    }

    public function test_middleware_performance_baseline(): void
    {
        // Establish a baseline by measuring request processing without middleware
        $request = Request::create('/admin/baseline', 'GET');

        $next = function ($req) {
            return new Response('Baseline test');
        };

        // Measure baseline (direct next() call)
        $iterations = 1000;
        $baselineTimes = [];

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $next($request);
            $endTime = microtime(true);

            $baselineTimes[] = ($endTime - $startTime) * 1000;
        }

        // Measure with middleware
        $middlewareTimes = [];

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $this->middleware->handle($request, $next);
            $endTime = microtime(true);

            $middlewareTimes[] = ($endTime - $startTime) * 1000;
        }

        $baselineAverage = array_sum($baselineTimes) / count($baselineTimes);
        $middlewareAverage = array_sum($middlewareTimes) / count($middlewareTimes);
        $overhead = $middlewareAverage - $baselineAverage;
        $overheadPercentage = ($overhead / $baselineAverage) * 100;

        // Assert middleware overhead is minimal
        $this->assertLessThan(0.5, $overhead, 'Middleware overhead should be less than 0.5ms');
        $this->assertLessThan(50, $overheadPercentage, 'Middleware overhead should be less than 50% of baseline');

        // Log baseline comparison
        echo "\nBaseline Performance Comparison:\n";
        echo 'Baseline average: '.number_format($baselineAverage, 4)."ms\n";
        echo 'Middleware average: '.number_format($middlewareAverage, 4)."ms\n";
        echo 'Overhead: '.number_format($overhead, 4)."ms\n";
        echo 'Overhead percentage: '.number_format($overheadPercentage, 2)."%\n";
    }

    public function test_middleware_performance_summary(): void
    {
        // This test provides a comprehensive performance summary
        $request = Request::create('/admin/summary', 'GET');

        $next = function ($req) {
            return new Response('Summary test');
        };

        // Collect various performance metrics
        $metrics = [
            'execution_time' => [],
            'memory_usage' => [],
            'cpu_time' => [],
        ];

        $iterations = 500;

        for ($i = 0; $i < $iterations; $i++) {
            $memoryBefore = memory_get_usage();
            $cpuBefore = getrusage();
            $startTime = microtime(true);

            $this->middleware->handle($request, $next);

            $endTime = microtime(true);
            $cpuAfter = getrusage();
            $memoryAfter = memory_get_usage();

            $metrics['execution_time'][] = ($endTime - $startTime) * 1000;
            $metrics['memory_usage'][] = $memoryAfter - $memoryBefore;
            $metrics['cpu_time'][] = ($cpuAfter['ru_utime.tv_usec'] - $cpuBefore['ru_utime.tv_usec']) / 1000;
        }

        // Calculate statistics
        $stats = [];
        foreach ($metrics as $metric => $values) {
            $stats[$metric] = [
                'average' => array_sum($values) / count($values),
                'min' => min($values),
                'max' => max($values),
                'median' => $this->calculateMedian($values),
                'std_dev' => $this->calculateStandardDeviation($values),
            ];
        }

        // Assert overall performance is acceptable
        $this->assertLessThan(1.0, $stats['execution_time']['average'], 'Average execution time should be under 1ms');
        $this->assertLessThan(5.0, $stats['execution_time']['max'], 'Maximum execution time should be under 5ms');
        $this->assertLessThan(1024, abs($stats['memory_usage']['average']), 'Average memory usage should be minimal');

        // Output comprehensive performance summary
        echo "\n=== MIDDLEWARE PERFORMANCE SUMMARY ===\n";
        foreach ($stats as $metric => $stat) {
            echo "\n".strtoupper(str_replace('_', ' ', $metric)).":\n";
            echo '  Average: '.number_format($stat['average'], 4)."\n";
            echo '  Minimum: '.number_format($stat['min'], 4)."\n";
            echo '  Maximum: '.number_format($stat['max'], 4)."\n";
            echo '  Median:  '.number_format($stat['median'], 4)."\n";
            echo '  Std Dev: '.number_format($stat['std_dev'], 4)."\n";
        }
        echo "\nTotal test iterations: {$iterations}\n";
        echo "=== END PERFORMANCE SUMMARY ===\n";
    }

    private function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);
        $middle = floor($count / 2);

        if ($count % 2 === 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }

        return $values[$middle];
    }

    private function calculateStandardDeviation(array $values): float
    {
        $mean = array_sum($values) / count($values);
        $squaredDifferences = array_map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);

        $variance = array_sum($squaredDifferences) / count($values);

        return sqrt($variance);
    }
}
