<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\HandleErrorsMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class HandleErrorsMiddlewareTest extends TestCase
{
    protected HandleErrorsMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new HandleErrorsMiddleware;
    }

    /**
     * Test middleware passes through normal requests.
     */
    public function test_middleware_passes_through_normal_requests(): void
    {
        $request = Request::create('/test', 'GET');
        $response = new Response('Test content');

        $result = $this->middleware->handle($request, function ($req) use ($response) {
            return $response;
        });

        $this->assertSame($response, $result);
    }

    /**
     * Test middleware logs slow requests.
     */
    public function test_middleware_logs_slow_requests(): void
    {
        // Define LARAVEL_START to simulate application start time
        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true) - 3.0); // Simulate 3 second execution
        }

        Log::shouldReceive('warning')
            ->once()
            ->with('Slow request detected', \Mockery::type('array'));

        $request = Request::create('/test', 'GET');
        $response = new Response('Test content');

        $result = $this->middleware->handle($request, function ($req) use ($response) {
            return $response;
        });

        $this->assertSame($response, $result);
    }

    /**
     * Test middleware catches and logs exceptions.
     */
    public function test_middleware_catches_and_logs_exceptions(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Middleware caught exception', \Mockery::type('array'));

        $request = Request::create('/test', 'GET');
        $exception = new \Exception('Test exception');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->middleware->handle($request, function ($req) use ($exception) {
            throw $exception;
        });
    }

    /**
     * Test middleware logs request context properly.
     */
    public function test_middleware_logs_request_context(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Middleware caught exception', \Mockery::on(function ($context) {
                return isset($context['url']) &&
                       isset($context['method']) &&
                       isset($context['ip']) &&
                       isset($context['user_agent']);
            }));

        $request = Request::create('/test', 'POST');
        $request->headers->set('User-Agent', 'Test User Agent');
        $exception = new \RuntimeException('Test runtime exception');

        $this->expectException(\RuntimeException::class);

        $this->middleware->handle($request, function ($req) use ($exception) {
            throw $exception;
        });
    }
}
