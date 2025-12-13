# Laravel 12 API Development Workflow

## Overview
Comprehensive workflow for developing robust APIs with Laravel 12, including authentication, validation, resources, and testing.

## Pre-Development Setup

### 1. API Structure Planning
- Define API endpoints and resources
- Plan authentication strategy (Sanctum recommended)
- Design request/response formats
- Plan rate limiting and caching strategies

### 2. Environment Configuration
```bash
# API-specific environment variables
API_VERSION=v1
API_RATE_LIMIT=60
API_CACHE_TTL=3600
```

## Development Workflow

### Phase 1: Authentication Setup

#### Install Laravel Sanctum
```bash
php artisan install:api --no-interaction
```

#### Configure API Authentication
```bash
# Create API authentication middleware
php artisan make:middleware ApiAuthentication --no-interaction
```

### Phase 2: Resource Development

#### 1. Create Models with API Considerations
```bash
php artisan make:model Product -mfs --no-interaction
```

**Model Best Practices:**
- Use `casts()` method for data transformation
- Add proper relationships with return types
- Implement API-specific scopes
- Add proper validation rules

#### 2. Create API Controllers
```bash
php artisan make:controller Api/V1/ProductController --api --no-interaction
```

**Controller Structure:**
- Use explicit return type declarations
- Implement proper HTTP status codes
- Use dependency injection for services
- Keep controllers thin

#### 3. Create Form Requests
```bash
php artisan make:request Api/StoreProductRequest --no-interaction
php artisan make:request Api/UpdateProductRequest --no-interaction
```

**Validation Best Practices:**
- Include both rules and custom messages
- Use array-based validation rules
- Implement authorization logic
- Add custom validation rules if needed

#### 4. Create API Resources
```bash
php artisan make:resource ProductResource --no-interaction
php artisan make:resource ProductCollection --no-interaction
```

**Resource Structure:**
- Transform data consistently
- Include metadata (pagination, links)
- Handle relationships efficiently
- Implement conditional fields

### Phase 3: Route Configuration

#### API Routes Setup
```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('products', ProductController::class);
    });
});
```

#### Rate Limiting
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->throttleApi('60,1');
})
```

### Phase 4: Testing Implementation

#### Create API Tests
```bash
php artisan make:test Api/ProductApiTest --no-interaction
```

**Test Coverage:**
- Authentication tests
- CRUD operation tests
- Validation tests
- Authorization tests
- Rate limiting tests
- Error handling tests

#### Test Structure
```php
public function test_can_create_product(): void
{
    $user = User::factory()->create();
    $data = Product::factory()->make()->toArray();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/products', $data);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id', 'name', 'price', 'created_at'
            ]
        ]);
}
```

### Phase 5: Documentation

#### API Documentation
- Use Laravel's built-in API documentation
- Document all endpoints with examples
- Include authentication requirements
- Provide error response examples

#### Postman Collection
- Create comprehensive Postman collection
- Include environment variables
- Add pre-request scripts for authentication
- Document all endpoints with examples

## Laravel 12 Specific Features

### Streamlined API Structure
- Use `routes/api.php` for API routes
- Leverage auto-registering middleware
- Utilize `bootstrap/app.php` for API configuration

### Modern PHP Features
- Constructor property promotion in requests
- Explicit return types in controllers
- Proper type hints for all methods
- Use of enums for status values

### Performance Optimizations
- Implement eager loading to prevent N+1 queries
- Use API resource caching
- Optimize database queries
- Implement proper indexing

## Quality Assurance Checklist

### Security
- [ ] Authentication implemented (Sanctum)
- [ ] Authorization checks in place
- [ ] Input validation comprehensive
- [ ] Rate limiting configured
- [ ] CORS properly configured
- [ ] No sensitive data in responses

### Performance
- [ ] Database queries optimized
- [ ] Eager loading implemented
- [ ] Caching strategy in place
- [ ] Pagination implemented
- [ ] Response compression enabled

### Testing
- [ ] All endpoints tested
- [ ] Authentication tests included
- [ ] Validation tests comprehensive
- [ ] Error scenarios covered
- [ ] Performance tests implemented

### Documentation
- [ ] API endpoints documented
- [ ] Authentication flow explained
- [ ] Error responses documented
- [ ] Rate limiting explained
- [ ] Postman collection created

## Deployment Considerations

### Production Setup
```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

### Monitoring
- Set up API monitoring
- Configure error tracking
- Monitor rate limiting
- Track performance metrics
- Set up uptime monitoring

### Versioning Strategy
- Use URL versioning (/api/v1/)
- Maintain backward compatibility
- Plan deprecation strategy
- Document version changes

## Best Practices Summary

### Code Organization
- Use consistent naming conventions
- Implement proper error handling
- Follow Laravel 12 conventions
- Use dependency injection
- Keep code DRY and SOLID

### API Design
- Use RESTful conventions
- Implement consistent response formats
- Use proper HTTP status codes
- Include meaningful error messages
- Implement proper pagination

### Security
- Always validate input
- Use proper authentication
- Implement authorization
- Sanitize output
- Use HTTPS in production

### Performance
- Optimize database queries
- Implement caching strategies
- Use efficient serialization
- Monitor API performance
- Scale horizontally when needed