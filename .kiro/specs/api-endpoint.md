# API Endpoint Development Spec

## Overview
Standardized API endpoint structures for the Treatwell venue booking system following Laravel best practices with proper API Resources, validation, and consistent response formatting.

## Endpoint Definition
- **Method**: GET, POST, PUT, PATCH, DELETE
- **URL**: `/api/v1/{resource}`
- **Purpose**: Provide consistent, well-structured API endpoints for venue and treatment management

## Requirements
- [x] Define request/response structure
- [x] Identify authentication requirements
- [ ] Plan rate limiting if needed
- [ ] Consider pagination for list endpoints

## Implementation
- [ ] Install and configure Laravel Sanctum for API authentication
- [ ] Create Eloquent API Resource classes
- [ ] Implement Form Request validation
- [ ] Create controller method with proper return types
- [ ] Add route to `routes/api.php`
- [ ] Implement proper error handling
- [ ] Add authorization using policies/gates
- [ ] Create user roles and permissions system
- [ ] Implement API token management

## Validation Rules

### Venue Listing Validation
- `search`: optional|string|max:255
- `city_id`: optional|integer|exists:cities,id
- `rating`: optional|numeric|between:1,5
- `type`: optional|string|max:100
- `sort`: optional|in:rating,name,newest,oldest,name_desc,rating_asc
- `per_page`: optional|integer|between:1,100
- `page`: optional|integer|min:1

### Venue Creation Validation
- `name`: required|string|max:255
- `slug`: required|string|max:255|unique:venues,slug
- `description`: optional|string|max:2000
- `address`: required|string|max:500
- `city_id`: required|integer|exists:cities,id
- `latitude`: optional|numeric|between:-90,90
- `longitude`: optional|numeric|between:-180,180
- `phone`: optional|string|max:50
- `email`: optional|email|max:255
- `website`: optional|url|max:500
- `type_name`: optional|string|max:100
- `is_active`: optional|boolean

### Treatment Filtering Validation
- `category`: optional|string|max:100
- `name`: optional|string|max:255
- `min_price`: optional|numeric|min:0
- `max_price`: optional|numeric|min:0|gte:min_price
- `city_id`: optional|integer|exists:cities,id
- `rating`: optional|numeric|between:1,5
- `per_page`: optional|integer|between:1,100
- `page`: optional|integer|min:1

## Request Structures

### Venue Listing Request (GET /api/v1/venues)
```json
{
  "search": "spa name",
  "city_id": 1,
  "rating": 4.0,
  "type": "spa",
  "sort": "rating|name|newest|oldest",
  "per_page": 15,
  "page": 1
}
```

### Venue Creation Request (POST /api/v1/venues)
```json
{
  "name": "Luxury Spa & Wellness",
  "slug": "luxury-spa-wellness",
  "description": "Premium spa services in the heart of the city",
  "address": "123 Main Street, City Center",
  "city_id": 1,
  "latitude": 51.5074,
  "longitude": -0.1278,
  "phone": "+44 20 1234 5678",
  "email": "info@luxuryspa.com",
  "website": "https://luxuryspa.com",
  "type_name": "spa",
  "is_active": true
}
```

### Treatment Filtering Request (GET /api/v1/treatments/venues)
```json
{
  "category": "massage",
  "name": "deep tissue",
  "min_price": 50.00,
  "max_price": 150.00,
  "city_id": 1,
  "rating": 4.0,
  "per_page": 15,
  "page": 1
}
```

## Response Structures

### Successful List Response
```json
{
  "data": [
    {
      "id": 1,
      "name": "Luxury Spa & Wellness",
      "slug": "luxury-spa-wellness",
      "description": "Premium spa services in the heart of the city",
      "address": "123 Main Street, City Center",
      "type_name": "spa",
      "rating": {
        "weighted_average": 4.5,
        "total_reviews": 127
      },
      "location": {
        "id": 1,
        "latitude": 51.5074,
        "longitude": -0.1278,
        "city": {
          "id": 1,
          "name": "London"
        }
      },
      "images": [
        {
          "id": 1,
          "url": "https://example.com/image1.jpg",
          "alt": "Spa interior"
        }
      ],
      "contact": {
        "phone": "+44 20 1234 5678",
        "email": "info@luxuryspa.com",
        "website": "https://luxuryspa.com"
      },
      "is_active": true,
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 67
  },
  "links": {
    "first": "https://api.example.com/v1/venues?page=1",
    "last": "https://api.example.com/v1/venues?page=5",
    "prev": null,
    "next": "https://api.example.com/v1/venues?page=2"
  }
}
```

### Successful Single Resource Response
```json
{
  "data": {
    "id": 1,
    "name": "Luxury Spa & Wellness",
    "slug": "luxury-spa-wellness",
    "description": "Premium spa services in the heart of the city",
    "address": "123 Main Street, City Center",
    "type_name": "spa",
    "rating": {
      "weighted_average": 4.5,
      "total_reviews": 127,
      "breakdown": {
        "5_star": 65,
        "4_star": 45,
        "3_star": 12,
        "2_star": 3,
        "1_star": 2
      }
    },
    "location": {
      "id": 1,
      "latitude": 51.5074,
      "longitude": -0.1278,
      "city": {
        "id": 1,
        "name": "London",
        "country": "United Kingdom"
      }
    },
    "images": [
      {
        "id": 1,
        "url": "https://example.com/image1.jpg",
        "alt": "Spa interior",
        "is_primary": true
      }
    ],
    "treatments": [
      {
        "id": 1,
        "name": "Deep Tissue Massage",
        "description": "Therapeutic massage for muscle tension",
        "category_name": "massage",
        "duration": 60,
        "price": 85.00,
        "min_price": 75.00,
        "max_price": 95.00
      }
    ],
    "opening_hours": [
      {
        "day": "monday",
        "open": "09:00",
        "close": "18:00",
        "is_closed": false
      }
    ],
    "contact": {
      "phone": "+44 20 1234 5678",
      "email": "info@luxuryspa.com",
      "website": "https://luxuryspa.com"
    },
    "is_active": true,
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  }
}
```

### Error Response Structure
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": [
      "The name field is required."
    ],
    "email": [
      "The email must be a valid email address."
    ]
  }
}
```

### Not Found Response
```json
{
  "message": "Venue not found"
}
```

### Server Error Response
```json
{
  "message": "Internal server error",
  "error_code": "INTERNAL_ERROR"
}
```

## Authentication Requirements

### Current Authentication Status
- **Public API**: All current API endpoints are publicly accessible without authentication
- **Admin Panel**: Uses Backpack CRUD with session-based authentication (currently bypassed)
- **Sanctum**: Not currently installed but recommended for API token authentication

### Authentication Strategy by Endpoint Type

#### Public Read-Only Endpoints (No Authentication Required)
- `GET /api/v1/venues` - Venue listing with filters
- `GET /api/v1/venues/{slug}` - Individual venue details
- `GET /api/v1/cities` - City listings
- `GET /api/v1/types` - Venue type listings
- `GET /api/v1/stats` - Public statistics
- `GET /api/v1/treatments/categories` - Treatment categories
- `GET /api/v1/treatments/venues` - Venues by treatment
- `GET /api/v1/treatments/venues/price-range` - Venues by price range
- `GET /api/v1/treatments/price-stats` - Treatment price statistics

#### Protected Endpoints (Authentication Required)
- `POST /api/v1/venues` - Create new venue (Admin/Venue Owner)
- `PUT /api/v1/venues/{id}` - Update venue (Admin/Venue Owner)
- `PATCH /api/v1/venues/{id}` - Partial venue update (Admin/Venue Owner)
- `DELETE /api/v1/venues/{id}` - Delete venue (Admin only)
- `POST /api/v1/treatments` - Create treatment (Admin/Venue Owner)
- `PUT /api/v1/treatments/{id}` - Update treatment (Admin/Venue Owner)
- `DELETE /api/v1/treatments/{id}` - Delete treatment (Admin/Venue Owner)

### Recommended Authentication Implementation

#### 1. Laravel Sanctum Integration
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

#### 2. User Roles and Permissions
- **Admin**: Full CRUD access to all resources
- **Venue Owner**: CRUD access to their own venues and treatments
- **Public**: Read-only access to public endpoints

#### 3. API Token Authentication
```php
// Add to User model
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    // ...
}
```

#### 4. Authentication Middleware Configuration
```php
// In bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    ]);
})
```

#### 5. Route Protection Examples
```php
// Protected routes in routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/venues', [VenueController::class, 'store']);
    Route::put('/venues/{venue}', [VenueController::class, 'update']);
    Route::delete('/venues/{venue}', [VenueController::class, 'destroy']);
});
```

### Authentication Headers

#### API Token Authentication
```http
Authorization: Bearer {api_token}
Content-Type: application/json
Accept: application/json
```

#### Session Authentication (for web interface)
```http
X-CSRF-TOKEN: {csrf_token}
Content-Type: application/json
Accept: application/json
```

### Authentication Error Responses

#### 401 Unauthorized Response
```json
{
  "message": "Unauthenticated.",
  "error_code": "UNAUTHENTICATED"
}
```

#### 403 Forbidden Response
```json
{
  "message": "This action is unauthorized.",
  "error_code": "FORBIDDEN"
}
```

#### 422 Invalid Token Response
```json
{
  "message": "The provided token is invalid.",
  "error_code": "INVALID_TOKEN"
}
```

### Security Considerations
- API tokens should have expiration dates
- Implement token scopes for different permission levels
- Use HTTPS in production for all authenticated endpoints
- Implement rate limiting on authentication endpoints
- Log authentication attempts for security monitoring
- Consider implementing API key authentication for third-party integrations

## HTTP Status Codes

### Success Responses
- `200 OK`: Successful GET, PUT, PATCH requests
- `201 Created`: Successful POST requests (resource creation)
- `204 No Content`: Successful DELETE requests

### Client Error Responses
- `400 Bad Request`: Invalid request format or parameters
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation errors
- `429 Too Many Requests`: Rate limit exceeded

### Server Error Responses
- `500 Internal Server Error`: Unexpected server error
- `503 Service Unavailable`: Service temporarily unavailable

## Testing
- [ ] Test successful venue listing with filters (search, city_id, rating, type, sort)
- [ ] Test venue creation with valid data
- [ ] Test venue retrieval by ID and slug
- [ ] Test treatment filtering by category, price range, and location
- [ ] Test pagination functionality
- [ ] Test validation errors for required fields
- [ ] Test validation errors for invalid data types
- [ ] Test authentication/authorization requirements
- [ ] Test API token authentication with valid tokens
- [ ] Test API token authentication with invalid/expired tokens
- [ ] Test unauthorized access to protected endpoints
- [ ] Test role-based access control (Admin vs Venue Owner)
- [ ] Test rate limiting behavior
- [ ] Test 404 responses for non-existent resources
- [ ] Test server error handling
- [ ] Run `php artisan test --filter=VenueApiTest`
- [ ] Run `php artisan test --filter=TreatmentApiTest`
- [ ] Run `php artisan test --filter=AuthenticationTest`

## References
- Use Laravel API Resources for consistent response formatting
- Implement Form Request classes for validation
- Follow RESTful API design principles
- Use proper HTTP status codes for different scenarios
- Implement API versioning with `/api/v1/` prefix
- Use pagination for list endpoints to improve performance
- Include proper error handling and meaningful error messages
- #[[file:AGENTS.md]] - Laravel Boost guidelines

## Implementation Notes
- All timestamps should be in ISO 8601 format (UTC)
- Decimal values (prices, ratings) should have consistent precision
- Use snake_case for JSON keys to match Laravel conventions
- Include relationship data using eager loading to prevent N+1 queries
- Implement proper caching strategies for frequently accessed data
- Use database transactions for operations that modify multiple resources