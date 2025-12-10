# Laravel 12 Testing Strategy

## Overview
Comprehensive testing strategy for Laravel 12 applications using PHPUnit, focusing on quality assurance, performance, and maintainability.

## Testing Philosophy

### Laravel 12 Testing Principles
- **PHPUnit over Pest** - Use PHPUnit for consistency with Laravel 12
- **Feature-focused testing** - Test behavior, not implementation
- **Factory-driven data** - Use model factories for consistent test data
- **Isolated tests** - Each test should be independent
- **Comprehensive coverage** - Test happy paths, edge cases, and failures

## Test Structure Organization

### Directory Structure
```
tests/
├── Feature/           # Integration and HTTP tests
│   ├── Auth/         # Authentication tests
│   ├── Api/          # API endpoint tests
│   └── Web/          # Web interface tests
├── Unit/             # Isolated unit tests
│   ├── Models/       # Model tests
│   ├── Services/     # Service class tests
│   └── Helpers/      # Helper function tests
└── TestCase.php      # Base test class
```

### Test Categories

#### 1. Feature Tests
- HTTP request/response testing
- Authentication and authorization
- Database interactions
- File uploads and processing
- Email and notification sending

#### 2. Unit Tests
- Model methods and relationships
- Service class logic
- Helper functions
- Validation rules
- Custom classes

#### 3. Integration Tests
- Third-party service integration
- Queue job processing
- Event and listener interactions
- Cache operations

## Laravel 12 Testing Setup

### Base Test Configuration
```php
// tests/TestCase.php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Laravel 12 specific setup
        $this->withoutVite();
        
        // Disable middleware for testing
        $this->withoutMiddleware([
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);
    }
}
```

### Factory Configuration
```php
// database/factories/UserFactory.php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }
}
```

## Testing Patterns

### 1. Model Testing
```php
<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Post;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_user_has_posts_relationship(): void
    {
        $user = User::factory()->create();
        $posts = Post::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $user->posts);
        $this->assertCount(3, $user->posts);
    }

    public function test_user_full_name_attribute(): void
    {
        $user = User::factory()->make([
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        $this->assertEquals('John Doe', $user->full_name);
    }

    public function test_user_can_be_created_with_factory(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email
        ]);
    }
}
```

### 2. Controller Testing
```php
<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Post;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    public function test_authenticated_user_can_create_post(): void
    {
        $user = User::factory()->create();
        $postData = [
            'title' => 'Test Post',
            'content' => 'This is test content',
            'status' => 'published'
        ];

        $response = $this->actingAs($user)
            ->post('/posts', $postData);

        $response->assertRedirect('/posts');
        $this->assertDatabaseHas('posts', $postData);
    }

    public function test_guest_cannot_create_post(): void
    {
        $postData = [
            'title' => 'Test Post',
            'content' => 'This is test content'
        ];

        $response = $this->post('/posts', $postData);

        $response->assertRedirect('/login');
        $this->assertDatabaseMissing('posts', $postData);
    }

    public function test_post_creation_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/posts', []);

        $response->assertSessionHasErrors(['title', 'content']);
    }
}
```

### 3. API Testing
```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    public function test_can_fetch_posts_with_authentication(): void
    {
        Sanctum::actingAs(User::factory()->create());
        
        $posts = Post::factory()->count(5)->create();

        $response = $this->getJson('/api/posts');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'content', 'created_at']
                ]
            ]);
    }

    public function test_api_returns_401_without_authentication(): void
    {
        $response = $this->getJson('/api/posts');

        $response->assertStatus(401);
    }

    public function test_can_create_post_via_api(): void
    {
        Sanctum::actingAs(User::factory()->create());
        
        $postData = [
            'title' => 'API Test Post',
            'content' => 'Content created via API'
        ];

        $response = $this->postJson('/api/posts', $postData);

        $response->assertStatus(201)
            ->assertJsonFragment($postData);
    }
}
```

### 4. Job Testing
```php
<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessPayment;
use App\Models\Order;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessPaymentTest extends TestCase
{
    public function test_job_is_dispatched(): void
    {
        Queue::fake();
        
        $order = Order::factory()->create();
        
        ProcessPayment::dispatch($order);
        
        Queue::assertPushed(ProcessPayment::class);
    }

    public function test_job_processes_payment_correctly(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);
        
        $job = new ProcessPayment($order);
        $job->handle();
        
        $order->refresh();
        $this->assertEquals('paid', $order->status);
    }
}
```

## Testing Best Practices

### 1. Test Data Management
- Use factories for all test data creation
- Create specific factory states for different scenarios
- Use `RefreshDatabase` trait for database tests
- Avoid hardcoded test data

### 2. Test Organization
- Group related tests in the same class
- Use descriptive test method names
- Follow AAA pattern (Arrange, Act, Assert)
- Keep tests focused and single-purpose

### 3. Mocking and Faking
```php
// Mock external services
public function test_external_api_integration(): void
{
    Http::fake([
        'api.example.com/*' => Http::response(['status' => 'success'], 200)
    ]);
    
    $result = $this->service->callExternalApi();
    
    $this->assertTrue($result);
}

// Fake queues
public function test_job_is_queued(): void
{
    Queue::fake();
    
    $this->service->processOrder($order);
    
    Queue::assertPushed(ProcessOrderJob::class);
}
```

### 4. Performance Testing
```php
public function test_endpoint_performance(): void
{
    $startTime = microtime(true);
    
    $response = $this->get('/api/posts');
    
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
    
    $response->assertStatus(200);
    $this->assertLessThan(500, $executionTime, 'Endpoint took too long to respond');
}
```

## Test Execution Strategies

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/PostControllerTest.php

# Run tests with filter
php artisan test --filter=test_user_can_create_post

# Run tests with coverage
php artisan test --coverage

# Run tests in parallel
php artisan test --parallel
```

### Continuous Integration
```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  tests:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: 8.3
        
    - name: Install dependencies
      run: composer install
      
    - name: Run tests
      run: php artisan test --coverage
```

## Quality Metrics

### Test Coverage Goals
- **Minimum 80%** overall code coverage
- **90%+ coverage** for critical business logic
- **100% coverage** for security-related code
- **Feature test coverage** for all user-facing functionality

### Performance Benchmarks
- API endpoints should respond within **200ms**
- Database queries should complete within **50ms**
- Page loads should complete within **1 second**
- Test suite should complete within **2 minutes**

## Laravel 12 Specific Considerations

### Modern PHP Features
- Use constructor property promotion in test classes
- Leverage PHP 8.3+ features in test assertions
- Use proper type hints for test methods
- Implement explicit return types

### Framework Features
- Test auto-registering commands
- Verify middleware registration in `bootstrap/app.php`
- Test service provider registration in `bootstrap/providers.php`
- Validate `casts()` method usage in models

### Performance Testing
- Test eager loading effectiveness
- Verify cache implementation
- Test queue job processing
- Validate database query optimization

## Troubleshooting Common Issues

### Database Issues
- Ensure `RefreshDatabase` trait is used
- Check factory relationships
- Verify migration rollbacks work
- Test with realistic data volumes

### Authentication Issues
- Use `Sanctum::actingAs()` for API tests
- Verify middleware configuration
- Test different user roles and permissions
- Check session handling in tests

### Performance Issues
- Profile slow tests with `--profile`
- Use database transactions for faster tests
- Mock external services
- Optimize factory creation