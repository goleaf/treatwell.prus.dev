---
title: CRUD Resource
description: Spec for creating complete CRUD resources in Laravel
tags: [crud, resource, laravel, api]
---

# CRUD Resource Specification

## Overview
This spec provides a structured approach to creating complete CRUD (Create, Read, Update, Delete) resources in Laravel with proper validation, testing, and API endpoints.

## Requirements Analysis
- [x] Model with relationships and factories
- [x] Database migration with proper indexes
- [x] Form Request validation classes
- [x] Resource Controller with all CRUD methods
- [-] API Resource classes for JSON responses
- [ ] Comprehensive test coverage
- [ ] Frontend views (if applicable)

## Design Considerations
- Follow Laravel naming conventions
- Use Eloquent relationships properly
- Implement proper validation
- Use API Resources for consistent JSON responses
- Follow RESTful routing patterns

## Implementation Tasks

### 1. Model Creation
```bash
php artisan make:model {ModelName} -mfsr
```
- [ ] Define fillable attributes
- [ ] Add relationships with return types
- [x] Implement casts() method
- [ ] Add any custom methods

### 2. Migration
- [ ] Create proper table structure
- [ ] Add indexes for performance
- [ ] Define foreign key constraints
- [ ] Add timestamps and soft deletes if needed

### 3. Factory and Seeder
- [ ] Create realistic factory definitions
- [ ] Add factory states if needed
- [ ] Create seeder for sample data

### 4. Validation
- [x] Create Form Request for store operations
- [x] Create Form Request for update operations
- [x] Include custom error messages
- [x] Follow existing validation patterns

### 5. Controller
- [ ] Resource controller with all methods
- [ ] Proper authorization checks
- [ ] Use Form Requests for validation
- [ ] Return appropriate responses
- [ ] Handle errors gracefully

### 6. API Resources
- [ ] Create API Resource for single items
- [ ] Create API Resource Collection
- [ ] Include relationships when needed
- [ ] Follow consistent response format

### 7. Routes
- [ ] Define resource routes
- [ ] Add API routes if needed
- [ ] Apply appropriate middleware
- [ ] Use route model binding

### 8. Frontend (if applicable)
- [ ] Index view with pagination
- [ ] Create/Edit forms
- [ ] Show view for details
- [ ] Delete confirmation
- [ ] Proper error handling

### 9. Testing
- [ ] Feature tests for all CRUD operations
- [ ] API endpoint tests
- [ ] Validation tests
- [ ] Authorization tests
- [ ] Edge case tests

## File Structure
```
app/
├── Http/
│   ├── Controllers/
│   │   └── {ModelName}Controller.php
│   ├── Requests/
│   │   ├── Store{ModelName}Request.php
│   │   └── Update{ModelName}Request.php
│   └── Resources/
│       ├── {ModelName}Resource.php
│       └── {ModelName}Collection.php
├── Models/
│   └── {ModelName}.php
database/
├── factories/
│   └── {ModelName}Factory.php
├── migrations/
│   └── create_{table_name}_table.php
└── seeders/
    └── {ModelName}Seeder.php
tests/
└── Feature/
    └── {ModelName}Test.php
```

## Best Practices
- Use descriptive method names
- Implement proper error handling
- Follow existing code conventions
- Use eager loading to prevent N+1 queries
- Implement proper authorization
- Write comprehensive tests

## Validation Example
```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email'],
        'status' => ['required', 'in:active,inactive'],
    ];
}
```

## References
- Laravel Resource Controllers Documentation
- Laravel API Resources Documentation
- Laravel Validation Documentation