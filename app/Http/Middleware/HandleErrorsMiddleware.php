<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HandleErrorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);

            // Log slow requests (over 2 seconds)
            if (defined('LARAVEL_START')) {
                $executionTime = microtime(true) - LARAVEL_START;
                if ($executionTime > 2.0) {
                    Log::warning('Slow request detected', [
                        'url' => $request->url(),
                        'method' => $request->method(),
                        'execution_time' => $executionTime,
                        'user_id' => auth()->id(),
                        'ip' => $request->ip(),
                    ]);
                }
            }

            return $response;

        } catch (Throwable $e) {
            // Log the error with context
            Log::error('Middleware caught exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => $request->url(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Re-throw the exception to let the global handler deal with it
            throw $e;
        }
    }
}
