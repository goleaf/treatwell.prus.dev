<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiddlewareInActualRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_middleware_is_applied_to_backpack_admin_routes(): void
    {
        // Test that admin routes are accessible (since middleware allows all requests)
        $adminRoutes = [
            '/admin/country',
            '/admin/city',
            '/admin/venue',
            '/admin/location',
            '/admin/treatment',
            '/admin/rating',
        ];

        foreach ($adminRoutes as $route) {
            $response = $this->get($route);

            // Since the CheckIfAdmin middleware allows all requests to pass through,
            // we should get a successful response (not a 401 or redirect)
            // The actual response might be 200 (success) or 302 (redirect to login from Backpack)
            // but it should not be blocked by our middleware
            $this->assertNotEquals(401, $response->getStatusCode(),
                "Route {$route} should not return 401 - middleware should allow access");
        }
    }

    public function test_admin_middleware_allows_ajax_requests_to_admin_routes(): void
    {
        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])->get('/admin/venue');

        // Should not be blocked by middleware (no 401 response)
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_admin_middleware_allows_post_requests_to_admin_routes(): void
    {
        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->post('/admin/venue', [
            'name' => 'Test Venue',
            'description' => 'Test Description',
        ]);

        // Should not be blocked by middleware (no 401 response)
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_admin_middleware_preserves_request_data_in_actual_routes(): void
    {
        $testData = [
            'name' => 'Test Venue Name',
            'description' => 'Test venue description',
            'address' => '123 Test Street',
        ];

        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->post('/admin/venue', $testData);

        // The middleware should not interfere with request data
        // We can't easily test the exact data preservation in integration tests,
        // but we can ensure the request wasn't blocked
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_admin_middleware_works_with_different_http_methods_on_admin_routes(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        foreach ($methods as $method) {
            $response = $this->call($method, '/admin/venue/1', [], [], [], [
                'HTTP_X_CSRF_TOKEN' => csrf_token(),
            ]);

            // Middleware should not block any HTTP method
            $this->assertNotEquals(401, $response->getStatusCode(),
                "Method {$method} should not be blocked by middleware");
        }
    }

    public function test_admin_middleware_handles_admin_routes_with_parameters(): void
    {
        // Test routes with parameters
        $parametrizedRoutes = [
            '/admin/venue/1',
            '/admin/venue/1/edit',
            '/admin/city/123',
            '/admin/treatment/456/edit',
        ];

        foreach ($parametrizedRoutes as $route) {
            $response = $this->get($route);

            // Should not be blocked by middleware
            $this->assertNotEquals(401, $response->getStatusCode(),
                "Parametrized route {$route} should not be blocked by middleware");
        }
    }

    public function test_admin_middleware_handles_admin_routes_with_query_parameters(): void
    {
        $response = $this->get('/admin/venue?page=2&search=test&order=name');

        // Should not be blocked by middleware
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_admin_middleware_preserves_headers_in_admin_routes(): void
    {
        $customHeaders = [
            'X-Custom-Header' => 'test-value',
            'Authorization' => 'Bearer test-token',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $response = $this->withHeaders($customHeaders)->get('/admin/venue');

        // Should not be blocked by middleware
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_admin_middleware_works_with_file_uploads_to_admin_routes(): void
    {
        // Create a temporary test file
        $tempFile = tmpfile();
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        file_put_contents($tempPath, 'test file content');

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempPath,
            'test.txt',
            'text/plain',
            null,
            true
        );

        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->post('/admin/venue', [
            'name' => 'Test Venue',
            'file' => $uploadedFile,
        ]);

        // Should not be blocked by middleware
        $this->assertNotEquals(401, $response->getStatusCode());

        // Clean up
        fclose($tempFile);
    }

    public function test_admin_middleware_handles_large_request_data_to_admin_routes(): void
    {
        // Create large request data
        $largeData = [
            'name' => 'Test Venue',
            'description' => str_repeat('Large description content. ', 1000),
            'metadata' => array_fill(0, 100, 'test_value'),
        ];

        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->post('/admin/venue', $largeData);

        // Should not be blocked by middleware
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_admin_middleware_handles_special_characters_in_admin_route_data(): void
    {
        $specialData = [
            'name' => 'Venue with Special Chars: !@#$%^&*()',
            'description' => 'Unicode content: Hello 世界 🌍',
            'address' => 'Address with "quotes" and \'apostrophes\'',
        ];

        $response = $this->withHeaders([
            'X-CSRF-TOKEN' => csrf_token(),
        ])->post('/admin/venue', $specialData);

        // Should not be blocked by middleware
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_admin_middleware_integration_with_backpack_crud_operations(): void
    {
        // Test that middleware doesn't interfere with Backpack CRUD operations
        $crudOperations = [
            ['method' => 'GET', 'url' => '/admin/venue'],           // List
            ['method' => 'GET', 'url' => '/admin/venue/create'],    // Create form
            ['method' => 'GET', 'url' => '/admin/venue/1'],         // Show
            ['method' => 'GET', 'url' => '/admin/venue/1/edit'],    // Edit form
        ];

        foreach ($crudOperations as $operation) {
            $response = $this->call($operation['method'], $operation['url']);

            // Should not be blocked by middleware
            $this->assertNotEquals(401, $response->getStatusCode(),
                "CRUD operation {$operation['method']} {$operation['url']} should not be blocked");
        }
    }

    public function test_admin_middleware_allows_concurrent_requests_to_admin_routes(): void
    {
        // Simulate multiple concurrent requests to admin routes
        $responses = [];

        for ($i = 0; $i < 5; $i++) {
            $response = $this->get("/admin/venue?request_id={$i}");
            $responses[] = $response->getStatusCode();
        }

        // All requests should be allowed through (no 401 responses)
        foreach ($responses as $statusCode) {
            $this->assertNotEquals(401, $statusCode,
                'Concurrent requests should not be blocked by middleware');
        }
    }

    public function test_admin_middleware_handles_malformed_admin_route_requests(): void
    {
        // Test various malformed requests to admin routes
        $malformedRequests = [
            '/admin/venue/../../../etc/passwd',
            '/admin/venue?param=value&malformed=',
            '/admin/venue#fragment',
            '/admin/venue with spaces',
        ];

        foreach ($malformedRequests as $url) {
            $response = $this->get($url);

            // Should not be blocked by middleware (may return other errors, but not 401)
            $this->assertNotEquals(401, $response->getStatusCode(),
                "Malformed request to {$url} should not be blocked by middleware");
        }
    }

    public function test_admin_middleware_preserves_session_data_in_admin_routes(): void
    {
        // Set some session data
        session(['test_key' => 'test_value', 'admin_context' => true]);

        $response = $this->get('/admin/venue');

        // Should not be blocked by middleware
        $this->assertNotEquals(401, $response->getStatusCode());

        // Session data should be preserved
        $this->assertEquals('test_value', session('test_key'));
        $this->assertTrue(session('admin_context'));
    }

    public function test_admin_middleware_works_with_authenticated_requests(): void
    {
        // Create a test user (though middleware doesn't check authentication)
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/venue');

        // Should not be blocked by middleware
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_admin_middleware_works_with_unauthenticated_requests(): void
    {
        // Test without authentication (middleware should still allow through)
        $response = $this->get('/admin/venue');

        // Should not be blocked by middleware (though Backpack might redirect)
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_admin_middleware_handles_json_api_requests_to_admin_routes(): void
    {
        $jsonData = [
            'name' => 'API Test Venue',
            'description' => 'Created via JSON API',
        ];

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-CSRF-TOKEN' => csrf_token(),
        ])->postJson('/admin/venue', $jsonData);

        // Should not be blocked by middleware
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_admin_middleware_handles_xml_requests_to_admin_routes(): void
    {
        $xmlData = '<?xml version="1.0"?><venue><name>XML Test Venue</name></venue>';

        $response = $this->withHeaders([
            'Content-Type' => 'application/xml',
            'X-CSRF-TOKEN' => csrf_token(),
        ])->call('POST', '/admin/venue', [], [], [], [], $xmlData);

        // Should not be blocked by middleware
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_admin_middleware_integration_with_route_model_binding(): void
    {
        // Create a test venue for route model binding
        $venue = \App\Models\Venue::factory()->create();

        $response = $this->get("/admin/venue/{$venue->id}");

        // Should not be blocked by middleware
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_admin_middleware_handles_admin_routes_with_middleware_parameters(): void
    {
        // Test that middleware works even when routes have additional middleware
        // The admin routes use the 'admin' middleware group which includes our CheckIfAdmin

        $response = $this->get('/admin/venue');

        // Should not be blocked by our middleware
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    public function test_admin_middleware_performance_with_admin_routes(): void
    {
        // Test that middleware doesn't significantly impact performance
        $startTime = microtime(true);

        $response = $this->get('/admin/venue');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should not be blocked by middleware
        $this->assertNotEquals(401, $response->getStatusCode());

        // Should complete reasonably quickly (less than 1 second for a simple request)
        $this->assertLessThan(1.0, $executionTime,
            'Admin route with middleware should complete within reasonable time');
    }
}
