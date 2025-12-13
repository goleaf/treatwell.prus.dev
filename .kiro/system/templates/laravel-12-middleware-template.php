<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class {{MiddlewareName}}
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $parameter = null): Response
    {
        // Pre-processing logic here
        
        // Example: Check authentication
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        // Example: Rate limiting
        // if ($this->rateLimitExceeded($request)) {
        //     return response()->json(['error' => 'Rate limit exceeded'], 429);
        // }
        
        $response = $next($request);
        
        // Post-processing logic here
        
        return $response;
    }
    
    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        // Cleanup or logging tasks
    }
}