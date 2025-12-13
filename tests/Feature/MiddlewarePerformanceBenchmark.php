<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckIfAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class MiddlewarePerformanceBenchmark extends TestCase
{
    use RefreshDatabase;

    protected CheckIfAdmin $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckIfAdmin;
    }

    public function test_middleware_performance_impact_analysis(): void
    {
        echo "\n=== MIDDLEWARE PERFORMANCE IMPACT ANALYSIS ===\n";

        // Test 1: Basic Performance Impact
        $this->analyzeBasicPerformance();

        // Test 2: Scalability Analysis
        $this->analyzeScalability();

        // Test 3: Resource Usage Analysis
        $this->analyzeResourceUsage();

        // Test 4: Comparison with Other Middleware
        $this->compareWithOtherMiddleware();

        echo "\n=== PERFORMANCE ANALYSIS COMPLETE ===\n";

        // All tests should pass - this is just for analysis
        $this->assertTrue(true);
    }

    private function analyzeBasicPerformance(): void
    {
        echo "\n1. BASIC PERFORMANCE IMPACT:\n";

        $request = Request::create('/admin/test', 'GET');
        $next = function ($req) {
            return new Response('Test');
        };

        // Measure without middleware
        $iterations = 10000;
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $next($request);
        }
        $baselineTime = microtime(true) - $startTime;

        // Measure with middleware
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->middleware->handle($request, $next);
        }
        $middlewareTime = microtime(true) - $startTime;

        $overhead = $middlewareTime - $baselineTime;
        $overheadPercentage = ($overhead / $baselineTime) * 100;
        $overheadPerRequest = ($overhead / $iterations) * 1000; // ms

        echo '   Baseline time: '.number_format($baselineTime * 1000, 2)."ms\n";
        echo '   Middleware time: '.number_format($middlewareTime * 1000, 2)."ms\n";
        echo '   Total overhead: '.number_format($overhead * 1000, 2)."ms\n";
        echo '   Overhead per request: '.number_format($overheadPerRequest, 4)."ms\n";
        echo '   Overhead percentage: '.number_format($overheadPercentage, 2)."%\n";

        // Performance assessment
        if ($overheadPerRequest < 0.01) {
            echo "   ✅ EXCELLENT: Negligible performance impact\n";
        } elseif ($overheadPerRequest < 0.1) {
            echo "   ✅ GOOD: Very low performance impact\n";
        } elseif ($overheadPerRequest < 1.0) {
            echo "   ⚠️  MODERATE: Acceptable performance impact\n";
        } else {
            echo "   ❌ HIGH: Significant performance impact\n";
        }
    }

    private function analyzeScalability(): void
    {
        echo "\n2. SCALABILITY ANALYSIS:\n";

        $requestCounts = [100, 500, 1000, 5000, 10000];
        $results = [];

        foreach ($requestCounts as $count) {
            $request = Request::create('/admin/scale', 'GET');
            $next = function ($req) {
                return new Response('Scale test');
            };

            $startTime = microtime(true);
            for ($i = 0; $i < $count; $i++) {
                $this->middleware->handle($request, $next);
            }
            $totalTime = microtime(true) - $startTime;

            $avgTimePerRequest = ($totalTime / $count) * 1000; // ms
            $requestsPerSecond = $count / $totalTime;

            $results[$count] = [
                'total_time' => $totalTime,
                'avg_per_request' => $avgTimePerRequest,
                'requests_per_second' => $requestsPerSecond,
            ];

            echo "   {$count} requests: ".number_format($avgTimePerRequest, 4).'ms/req, '
                 .number_format($requestsPerSecond, 0)." req/s\n";
        }

        // Check for linear scalability
        $firstRps = $results[100]['requests_per_second'];
        $lastRps = $results[10000]['requests_per_second'];
        $scalabilityRatio = $lastRps / $firstRps;

        echo '   Scalability ratio: '.number_format($scalabilityRatio, 2)."\n";

        if ($scalabilityRatio > 0.8) {
            echo "   ✅ EXCELLENT: Linear scalability maintained\n";
        } elseif ($scalabilityRatio > 0.6) {
            echo "   ✅ GOOD: Good scalability\n";
        } elseif ($scalabilityRatio > 0.4) {
            echo "   ⚠️  MODERATE: Some scalability degradation\n";
        } else {
            echo "   ❌ POOR: Significant scalability issues\n";
        }
    }

    private function analyzeResourceUsage(): void
    {
        echo "\n3. RESOURCE USAGE ANALYSIS:\n";

        $request = Request::create('/admin/resource', 'GET');
        $next = function ($req) {
            return new Response('Resource test');
        };

        // Memory usage analysis
        $memoryBefore = memory_get_usage(true);
        $peakBefore = memory_get_peak_usage(true);

        for ($i = 0; $i < 1000; $i++) {
            $this->middleware->handle($request, $next);
        }

        $memoryAfter = memory_get_usage(true);
        $peakAfter = memory_get_peak_usage(true);

        $memoryIncrease = $memoryAfter - $memoryBefore;
        $peakIncrease = $peakAfter - $peakBefore;

        echo '   Memory increase: '.number_format($memoryIncrease / 1024, 2)."KB\n";
        echo '   Peak memory increase: '.number_format($peakIncrease / 1024, 2)."KB\n";
        echo '   Memory per request: '.number_format($memoryIncrease / 1000, 2)." bytes\n";

        // CPU usage analysis (simplified)
        $cpuBefore = getrusage();

        for ($i = 0; $i < 1000; $i++) {
            $this->middleware->handle($request, $next);
        }

        $cpuAfter = getrusage();
        $cpuTime = ($cpuAfter['ru_utime.tv_usec'] - $cpuBefore['ru_utime.tv_usec']) / 1000000;

        echo '   CPU time: '.number_format($cpuTime * 1000, 2)."ms\n";
        echo '   CPU per request: '.number_format($cpuTime, 6)."s\n";

        // Resource assessment
        if ($memoryIncrease < 1024 && $cpuTime < 0.01) {
            echo "   ✅ EXCELLENT: Minimal resource usage\n";
        } elseif ($memoryIncrease < 10240 && $cpuTime < 0.1) {
            echo "   ✅ GOOD: Low resource usage\n";
        } elseif ($memoryIncrease < 102400 && $cpuTime < 1.0) {
            echo "   ⚠️  MODERATE: Acceptable resource usage\n";
        } else {
            echo "   ❌ HIGH: Significant resource usage\n";
        }
    }

    private function compareWithOtherMiddleware(): void
    {
        echo "\n4. COMPARISON WITH STANDARD MIDDLEWARE:\n";

        $request = Request::create('/admin/compare', 'GET');
        $next = function ($req) {
            return new Response('Compare test');
        };

        // Test our middleware
        $iterations = 5000;
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->middleware->handle($request, $next);
        }
        $ourTime = microtime(true) - $startTime;

        // Test a simple pass-through middleware for comparison
        $simpleMiddleware = function ($request, $next) {
            return $next($request);
        };

        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $simpleMiddleware($request, $next);
        }
        $simpleTime = microtime(true) - $startTime;

        // Test direct execution (no middleware)
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $next($request);
        }
        $directTime = microtime(true) - $startTime;

        $ourAvg = ($ourTime / $iterations) * 1000;
        $simpleAvg = ($simpleTime / $iterations) * 1000;
        $directAvg = ($directTime / $iterations) * 1000;

        echo '   Direct execution: '.number_format($directAvg, 4)."ms/req\n";
        echo '   Simple middleware: '.number_format($simpleAvg, 4)."ms/req\n";
        echo '   Our middleware: '.number_format($ourAvg, 4)."ms/req\n";

        $overheadVsDirect = (($ourTime - $directTime) / $directTime) * 100;
        $overheadVsSimple = (($ourTime - $simpleTime) / $simpleTime) * 100;

        echo '   Overhead vs direct: '.number_format($overheadVsDirect, 2)."%\n";
        echo '   Overhead vs simple: '.number_format($overheadVsSimple, 2)."%\n";

        // Performance comparison assessment
        if ($overheadVsDirect < 20) {
            echo "   ✅ EXCELLENT: Minimal overhead compared to direct execution\n";
        } elseif ($overheadVsDirect < 50) {
            echo "   ✅ GOOD: Low overhead\n";
        } elseif ($overheadVsDirect < 100) {
            echo "   ⚠️  MODERATE: Acceptable overhead\n";
        } else {
            echo "   ❌ HIGH: Significant overhead\n";
        }
    }
}
