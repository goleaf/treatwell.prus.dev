<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MiddlewareAliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_middleware_alias_is_registered(): void
    {
        // Create a test route with the admin middleware alias
        Route::get('/test-admin-alias', function () {
            return response()->json(['message' => 'Admin access granted']);
        })->middleware('admin');

        // Make a request to the test route
        $response = $this->get('/test-admin-alias');

        // Since CheckIfAdmin middleware currently allows all requests,
        // we should get a successful response
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Admin access granted']);
    }

    public function test_throttle_middleware_alias_is_registered(): void
    {
        // Create a test route with the throttle middleware alias
        Route::get('/test-throttle-alias', function () {
            return response()->json(['message' => 'Request successful']);
        })->middleware('throttle:5,1');

        // Make a request to the test route
        $response = $this->get('/test-throttle-alias');

        // Should get a successful response
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Request successful']);
    }

    public function test_signed_middleware_alias_is_registered(): void
    {
        // Create a test route with the signed middleware alias
        Route::get('/test-signed-alias', function () {
            return response()->json(['message' => 'Signed request verified']);
        })->middleware('signed');

        // Test that unsigned request fails (this proves the middleware alias works)
        $response = $this->get('/test-signed-alias');
        $response->assertStatus(403);

        // Test with a manually signed URL
        $url = '/test-signed-alias?signature='.hash_hmac('sha256', 'test-signed-alias', config('app.key'));
        $response = $this->get($url);
        // This will still fail because we need proper signature, but it proves the middleware is working
        $response->assertStatus(403);
    }

    public function test_can_middleware_alias_is_registered(): void
    {
        // Create a test route with the can middleware alias
        Route::get('/test-can-alias', function () {
            return response()->json(['message' => 'Authorization passed']);
        })->middleware('can:view-admin');

        // Make a request to the test route (this will fail authorization)
        $response = $this->get('/test-can-alias');

        // Should get a 403 response since we don't have the permission
        $response->assertStatus(403);
    }

    public function test_cache_headers_middleware_alias_is_registered(): void
    {
        // Create a test route with the cache.headers middleware alias
        Route::get('/test-cache-headers-alias', function () {
            return response()->json(['message' => 'Cache headers set']);
        })->middleware('cache.headers:public;max_age=3600');

        // Make a request to the test route
        $response = $this->get('/test-cache-headers-alias');

        // Should get a successful response with cache headers
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Cache headers set']);
        $response->assertHeader('Cache-Control', 'max-age=3600, public');
    }
}
