# API Endpoint Development Spec

## Overview
Template for developing new API endpoints following Laravel best practices.

## Endpoint Definition
- **Method**: [GET|POST|PUT|PATCH|DELETE]
- **URL**: `/api/v1/resource`
- **Purpose**: Brief description of endpoint functionality

## Requirements
- [ ] Define request/response structure
- [ ] Identify authentication requirements
- [ ] Plan rate limiting if needed
- [ ] Consider pagination for list endpoints

## Implementation
- [ ] Create Eloquent API Resource classes
- [ ] Implement Form Request validation
- [ ] Create controller method with proper return types
- [ ] Add route to `routes/api.php`
- [ ] Implement proper error handling
- [ ] Add authorization using policies/gates

## Request Structure
```json
{
  "field": "value",
  "nested": {
    "field": "value"
  }
}
```

## Response Structure
```json
{
  "data": {},
  "meta": {},
  "links": {}
}
```

## Testing
- [ ] Test successful requests
- [ ] Test validation errors
- [ ] Test authentication/authorization
- [ ] Test edge cases and error conditions
- [ ] Run `php artisan test --filter=ApiEndpointTest`

## References
- Use Laravel API Resources for consistent responses
- Follow existing API versioning patterns
- #[[file:AGENTS.md]] - Laravel Boost guidelines