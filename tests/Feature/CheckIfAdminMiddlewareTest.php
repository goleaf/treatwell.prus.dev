<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckIfAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class CheckIfAdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected CheckIfAdmin $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckIfAdmin;
    }

    public function test_middleware_allows_request_to_proceed(): void
    {
        $request = Request::create('/admin/test', 'GET');
        $nextCalled = false;

        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;

            return new Response('Success');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertTrue($nextCalled);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_middleware_handles_ajax_requests(): void
    {
        $request = Request::create('/admin/test', 'GET');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $next = function ($req) {
            return new Response('Success');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_middleware_handles_json_requests(): void
    {
        $request = Request::create('/admin/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $next = function ($req) {
            return new Response('Success');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_middleware_preserves_request_data(): void
    {
        $requestData = ['key' => 'value', 'admin' => true];
        $request = Request::create('/admin/test', 'POST', $requestData);

        $receivedRequest = null;
        $next = function ($req) use (&$receivedRequest) {
            $receivedRequest = $req;

            return new Response('Success');
        };

        $this->middleware->handle($request, $next);

        $this->assertNotNull($receivedRequest);
        $this->assertEquals($requestData, $receivedRequest->all());
    }

    public function test_middleware_preserves_request_headers(): void
    {
        $request = Request::create('/admin/test', 'GET');
        $request->headers->set('Custom-Header', 'test-value');
        $request->headers->set('Authorization', 'Bearer token123');

        $receivedRequest = null;
        $next = function ($req) use (&$receivedRequest) {
            $receivedRequest = $req;

            return new Response('Success');
        };

        $this->middleware->handle($request, $next);

        $this->assertNotNull($receivedRequest);
        $this->assertEquals('test-value', $receivedRequest->header('Custom-Header'));
        $this->assertEquals('Bearer token123', $receivedRequest->header('Authorization'));
    }

    public function test_middleware_works_with_different_http_methods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        foreach ($methods as $method) {
            $request = Request::create('/admin/test', $method);
            $nextCalled = false;

            $next = function ($req) use (&$nextCalled) {
                $nextCalled = true;

                return new Response("Success for {$req->method()}");
            };

            $response = $this->middleware->handle($request, $next);

            $this->assertTrue($nextCalled, "Next was not called for method: {$method}");
            $this->assertEquals("Success for {$method}", $response->getContent());
        }
    }

    public function test_middleware_handles_nested_request_data(): void
    {
        $nestedData = [
            'user' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'settings' => [
                'theme' => 'dark',
                'notifications' => true,
            ],
        ];

        $request = Request::create('/admin/users', 'POST', $nestedData);

        $receivedRequest = null;
        $next = function ($req) use (&$receivedRequest) {
            $receivedRequest = $req;

            return new Response('Success');
        };

        $this->middleware->handle($request, $next);

        $this->assertNotNull($receivedRequest);
        $this->assertEquals('John Doe', $receivedRequest->input('user.name'));
        $this->assertEquals('john@example.com', $receivedRequest->input('user.email'));
        $this->assertEquals('dark', $receivedRequest->input('settings.theme'));
        $this->assertTrue($receivedRequest->input('settings.notifications'));
    }

    public function test_middleware_maintains_request_integrity(): void
    {
        $originalRequest = Request::create('/admin/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'admin',
        ]);
        $originalRequest->headers->set('Content-Type', 'application/json');

        $processedRequest = null;
        $next = function ($req) use (&$processedRequest) {
            $processedRequest = $req;

            return new Response('Success');
        };

        $response = $this->middleware->handle($originalRequest, $next);

        // Verify the request passed through unchanged
        $this->assertEquals($originalRequest->all(), $processedRequest->all());
        $this->assertEquals($originalRequest->method(), $processedRequest->method());
        $this->assertEquals($originalRequest->url(), $processedRequest->url());
        $this->assertEquals($originalRequest->header('Content-Type'), $processedRequest->header('Content-Type'));
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_middleware_can_be_instantiated(): void
    {
        $middleware = new CheckIfAdmin;
        $this->assertInstanceOf(CheckIfAdmin::class, $middleware);
    }

    public function test_middleware_handle_method_exists_and_is_callable(): void
    {
        $this->assertTrue(method_exists($this->middleware, 'handle'));
        $this->assertTrue(is_callable([$this->middleware, 'handle']));
    }

    public function test_middleware_handles_empty_request(): void
    {
        $request = Request::create('/admin/test', 'GET');

        $next = function ($req) {
            return new Response('Empty request handled');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('Empty request handled', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_handles_large_request_data(): void
    {
        // Create a large dataset to test middleware performance
        $largeData = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeData["item_{$i}"] = "value_{$i}";
        }

        $request = Request::create('/admin/bulk', 'POST', $largeData);

        $receivedRequest = null;
        $next = function ($req) use (&$receivedRequest) {
            $receivedRequest = $req;

            return new Response('Large data processed');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertNotNull($receivedRequest);
        $this->assertEquals(1000, count($receivedRequest->all()));
        $this->assertEquals('value_500', $receivedRequest->input('item_500'));
        $this->assertEquals('Large data processed', $response->getContent());
    }

    public function test_middleware_handles_special_characters_in_data(): void
    {
        $specialData = [
            'unicode' => 'Hello 世界 🌍',
            'symbols' => '!@#$%^&*()_+-=[]{}|;:,.<>?',
            'quotes' => 'Single \'quotes\' and "double quotes"',
            'html' => '<script>alert("test")</script>',
            'newlines' => "Line 1\nLine 2\r\nLine 3",
        ];

        $request = Request::create('/admin/special', 'POST', $specialData);

        $receivedRequest = null;
        $next = function ($req) use (&$receivedRequest) {
            $receivedRequest = $req;

            return new Response('Special characters handled');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertNotNull($receivedRequest);
        $this->assertEquals($specialData['unicode'], $receivedRequest->input('unicode'));
        $this->assertEquals($specialData['symbols'], $receivedRequest->input('symbols'));
        $this->assertEquals($specialData['html'], $receivedRequest->input('html'));
        $this->assertEquals('Special characters handled', $response->getContent());
    }

    public function test_middleware_preserves_file_uploads(): void
    {
        // Create a temporary file for testing
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

        $request = Request::create('/admin/upload', 'POST', [], [], ['file' => $uploadedFile]);

        $receivedRequest = null;
        $next = function ($req) use (&$receivedRequest) {
            $receivedRequest = $req;

            return new Response('File upload handled');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertNotNull($receivedRequest);
        $this->assertTrue($receivedRequest->hasFile('file'));
        $this->assertEquals('test.txt', $receivedRequest->file('file')->getClientOriginalName());
        $this->assertEquals('File upload handled', $response->getContent());

        // Clean up
        fclose($tempFile);
    }

    public function test_middleware_handles_concurrent_requests(): void
    {
        $requests = [];
        $responses = [];

        // Simulate multiple concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $request = Request::create("/admin/concurrent/{$i}", 'GET', ['request_id' => $i]);

            $next = function ($req) {
                return new Response("Processed request: {$req->input('request_id')}");
            };

            $response = $this->middleware->handle($request, $next);
            $responses[] = $response->getContent();
        }

        // Verify all requests were processed correctly
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals("Processed request: {$i}", $responses[$i]);
        }
    }

    public function test_middleware_integration_with_route_simulation(): void
    {
        // Simulate a complete request-response cycle through middleware
        $request = Request::create('/admin/dashboard', 'GET');
        $request->headers->set('Accept', 'text/html');
        $request->headers->set('User-Agent', 'Mozilla/5.0 Test Browser');

        $controllerResponse = null;
        $next = function ($req) use (&$controllerResponse) {
            // Simulate controller logic
            $controllerResponse = [
                'method' => $req->method(),
                'path' => $req->path(),
                'headers' => [
                    'accept' => $req->header('Accept'),
                    'user_agent' => $req->header('User-Agent'),
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ];

            return new Response(json_encode($controllerResponse), 200, [
                'Content-Type' => 'application/json',
            ]);
        };

        $response = $this->middleware->handle($request, $next);

        // Verify middleware allowed request to pass through
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('GET', $responseData['method']);
        $this->assertEquals('admin/dashboard', $responseData['path']);
        $this->assertEquals('text/html', $responseData['headers']['accept']);
        $this->assertEquals('Mozilla/5.0 Test Browser', $responseData['headers']['user_agent']);
    }

    public function test_middleware_error_handling_with_exception_in_next(): void
    {
        $request = Request::create('/admin/error', 'GET');

        $next = function ($req) {
            throw new \Exception('Simulated controller error');
        };

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Simulated controller error');

        $this->middleware->handle($request, $next);
    }

    // Additional Error Conditions and Edge Cases Tests

    public function test_middleware_handles_null_request(): void
    {
        // Test that middleware gracefully handles null request by allowing it to pass through
        // The actual error handling would occur in the next closure or controller
        $next = function ($req) {
            // This would typically cause an error in real application code
            return new Response('Null request passed through');
        };

        $response = $this->middleware->handle(null, $next);

        // The middleware should pass null through to the next closure
        $this->assertEquals('Null request passed through', $response->getContent());
    }

    public function test_middleware_handles_null_next_closure(): void
    {
        $request = Request::create('/admin/test', 'GET');

        $this->expectException(\TypeError::class);

        $this->middleware->handle($request, null);
    }

    public function test_middleware_handles_invalid_closure(): void
    {
        $request = Request::create('/admin/test', 'GET');

        $this->expectException(\TypeError::class);

        $this->middleware->handle($request, 'not_a_closure');
    }

    public function test_middleware_handles_closure_returning_null(): void
    {
        $request = Request::create('/admin/test', 'GET');

        $next = function ($req) {
            return null;
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertNull($response);
    }

    public function test_middleware_handles_closure_returning_invalid_response(): void
    {
        $request = Request::create('/admin/test', 'GET');

        $next = function ($req) {
            return 'invalid_response_type';
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('invalid_response_type', $response);
    }

    public function test_middleware_handles_malformed_request_uri(): void
    {
        // Test with various malformed URIs
        $malformedUris = [
            '/admin/test with spaces',
            '/admin/test%invalid',
            '/admin/test/../../../etc/passwd',
            '/admin/test?param=value&malformed=',
            '/admin/test#fragment',
        ];

        foreach ($malformedUris as $uri) {
            $request = Request::create($uri, 'GET');

            $next = function ($req) use ($uri) {
                return new Response("Handled: {$uri}");
            };

            $response = $this->middleware->handle($request, $next);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertStringContainsString('Handled:', $response->getContent());
        }
    }

    public function test_middleware_handles_extremely_long_request_uri(): void
    {
        // Create an extremely long URI (over 2000 characters)
        $longPath = '/admin/'.str_repeat('a', 2000);
        $request = Request::create($longPath, 'GET');

        $next = function ($req) {
            return new Response('Long URI handled');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('Long URI handled', $response->getContent());
    }

    public function test_middleware_handles_invalid_http_methods(): void
    {
        // Test with non-standard HTTP methods
        $invalidMethods = ['INVALID', 'CUSTOM', 'TRACE', 'CONNECT'];

        foreach ($invalidMethods as $method) {
            $request = Request::create('/admin/test', $method);

            $next = function ($req) use ($method) {
                return new Response("Method {$method} handled");
            };

            $response = $this->middleware->handle($request, $next);

            $this->assertEquals("Method {$method} handled", $response->getContent());
        }
    }

    public function test_middleware_handles_malformed_headers(): void
    {
        $request = Request::create('/admin/test', 'GET');

        // Add headers with various edge cases
        $request->headers->set('', 'empty-name-header');
        $request->headers->set('Invalid Header Name', 'spaces-in-name');
        $request->headers->set('X-Test', ''); // Empty value
        $request->headers->set('X-Unicode', 'Hello 世界 🌍');
        $request->headers->set('X-Long', str_repeat('a', 1000));

        $receivedRequest = null;
        $next = function ($req) use (&$receivedRequest) {
            $receivedRequest = $req;

            return new Response('Malformed headers handled');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertNotNull($receivedRequest);
        $this->assertEquals('Malformed headers handled', $response->getContent());
    }

    public function test_middleware_handles_corrupted_request_data(): void
    {
        $request = Request::create('/admin/test', 'POST');

        // Simulate corrupted/malformed data
        $corruptedData = [
            'null_value' => null,
            'empty_string' => '',
            'zero' => 0,
            'false' => false,
            'array_with_null' => [null, '', 0, false],
            'deeply_nested' => [
                'level1' => [
                    'level2' => [
                        'level3' => null,
                    ],
                ],
            ],
        ];

        $request->merge($corruptedData);

        $receivedRequest = null;
        $next = function ($req) use (&$receivedRequest) {
            $receivedRequest = $req;

            return new Response('Corrupted data handled');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertNotNull($receivedRequest);
        $this->assertEquals('Corrupted data handled', $response->getContent());
        $this->assertNull($receivedRequest->input('null_value'));
        $this->assertEquals('', $receivedRequest->input('empty_string'));
        $this->assertEquals(0, $receivedRequest->input('zero'));
        $this->assertFalse($receivedRequest->input('false'));
    }

    public function test_middleware_handles_memory_intensive_operations(): void
    {
        // Create a request with a very large amount of data
        $largeArray = array_fill(0, 10000, str_repeat('x', 100));
        $request = Request::create('/admin/memory-test', 'POST', ['large_data' => $largeArray]);

        $memoryBefore = memory_get_usage();

        $next = function ($req) {
            return new Response('Memory intensive operation completed');
        };

        $response = $this->middleware->handle($request, $next);

        $memoryAfter = memory_get_usage();

        $this->assertEquals('Memory intensive operation completed', $response->getContent());
        // Ensure memory usage didn't grow excessively (allow for some overhead)
        $this->assertLessThan($memoryBefore + (50 * 1024 * 1024), $memoryAfter); // 50MB threshold
    }

    public function test_middleware_handles_recursive_data_structures(): void
    {
        $request = Request::create('/admin/recursive', 'POST');

        // Create recursive-like data structure (PHP arrays can't be truly recursive, but we can simulate)
        $recursiveData = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'level5' => 'deep_value',
                        ],
                    ],
                ],
            ],
        ];

        $request->merge($recursiveData);

        $receivedRequest = null;
        $next = function ($req) use (&$receivedRequest) {
            $receivedRequest = $req;

            return new Response('Recursive data handled');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertNotNull($receivedRequest);
        $this->assertEquals('Recursive data handled', $response->getContent());
        $this->assertEquals('deep_value', $receivedRequest->input('level1.level2.level3.level4.level5'));
    }

    public function test_middleware_handles_timeout_simulation(): void
    {
        $request = Request::create('/admin/timeout', 'GET');

        $next = function ($req) {
            // Simulate a slow operation
            usleep(100000); // 0.1 seconds

            return new Response('Slow operation completed');
        };

        $startTime = microtime(true);
        $response = $this->middleware->handle($request, $next);
        $endTime = microtime(true);

        $this->assertEquals('Slow operation completed', $response->getContent());
        $this->assertGreaterThan(0.05, $endTime - $startTime); // Should take at least 0.05 seconds
    }

    public function test_middleware_handles_binary_data_in_request(): void
    {
        $binaryData = pack('H*', '48656c6c6f20576f726c64'); // "Hello World" in hex
        $request = Request::create('/admin/binary', 'POST', ['binary' => $binaryData]);

        $receivedRequest = null;
        $next = function ($req) use (&$receivedRequest) {
            $receivedRequest = $req;

            return new Response('Binary data handled');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertNotNull($receivedRequest);
        $this->assertEquals('Binary data handled', $response->getContent());
        $this->assertEquals($binaryData, $receivedRequest->input('binary'));
    }

    public function test_middleware_handles_multiple_exceptions_in_sequence(): void
    {
        $request = Request::create('/admin/multiple-errors', 'GET');

        // Test that middleware properly propagates different types of exceptions
        $exceptionTypes = [
            \InvalidArgumentException::class,
            \RuntimeException::class,
            \LogicException::class,
        ];

        foreach ($exceptionTypes as $exceptionType) {
            $next = function ($req) use ($exceptionType) {
                throw new $exceptionType("Test {$exceptionType}");
            };

            try {
                $this->middleware->handle($request, $next);
                $this->fail("Expected {$exceptionType} was not thrown");
            } catch (\Exception $e) {
                $this->assertInstanceOf($exceptionType, $e);
                $this->assertStringContainsString("Test {$exceptionType}", $e->getMessage());
            }
        }
    }

    public function test_middleware_handles_request_with_circular_reference_simulation(): void
    {
        $request = Request::create('/admin/circular', 'POST');

        // Simulate data that might cause issues with serialization
        $data = [
            'self_reference' => 'points_to_self',
            'complex_structure' => [
                'a' => ['b' => ['c' => ['back_to_a' => 'reference']]],
                'duplicate_refs' => ['same', 'same', 'same'],
            ],
        ];

        $request->merge($data);

        $receivedRequest = null;
        $next = function ($req) use (&$receivedRequest) {
            $receivedRequest = $req;

            return new Response('Circular reference simulation handled');
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertNotNull($receivedRequest);
        $this->assertEquals('Circular reference simulation handled', $response->getContent());
        $this->assertEquals('points_to_self', $receivedRequest->input('self_reference'));
    }

    public function test_middleware_handles_edge_case_content_types(): void
    {
        $edgeContentTypes = [
            'application/octet-stream',
            'multipart/form-data; boundary=something',
            'text/plain; charset=utf-8',
            'application/x-www-form-urlencoded',
            'invalid/content-type',
            '',
        ];

        foreach ($edgeContentTypes as $contentType) {
            $request = Request::create('/admin/content-type', 'POST');
            $request->headers->set('Content-Type', $contentType);

            $next = function ($req) use ($contentType) {
                return new Response("Content type {$contentType} handled");
            };

            $response = $this->middleware->handle($request, $next);

            $this->assertStringContainsString('handled', $response->getContent());
        }
    }

    public function test_middleware_handles_request_size_edge_cases(): void
    {
        // Test with minimal request
        $minimalRequest = Request::create('/', 'GET');

        $next = function ($req) {
            return new Response('Minimal request handled');
        };

        $response = $this->middleware->handle($minimalRequest, $next);
        $this->assertEquals('Minimal request handled', $response->getContent());

        // Test with request containing only special characters in path
        $specialRequest = Request::create('/admin/!@#$%^&*()', 'GET');

        $next = function ($req) {
            return new Response('Special characters in path handled');
        };

        $response = $this->middleware->handle($specialRequest, $next);
        $this->assertEquals('Special characters in path handled', $response->getContent());
    }
}
