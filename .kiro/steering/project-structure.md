# Project Structure Guidelines

## Laravel 12 Directory Structure
This project follows Laravel 12's streamlined structure with specific organizational patterns.

### Core Application Structure
```
app/
├── Console/Commands/          # Auto-registered Artisan commands
├── Events/                    # Application events
├── Exceptions/                # Custom exception classes
├── Http/
│   ├── Controllers/           # HTTP controllers
│   ├── Requests/              # Form request validation classes
│   └── Resources/             # API resource classes
├── Jobs/                      # Queue job classes
├── Listeners/                 # Event listeners
├── Mail/                      # Mailable classes
├── Models/                    # Eloquent models
├── Notifications/             # Notification classes
├── Policies/                  # Authorization policies
├── Providers/                 # Service providers
└── Services/                  # Business logic services
```

### Configuration & Bootstrap
```
bootstrap/
├── app.php                    # Application bootstrap (middleware, routing)
└── providers.php              # Service provider registration

config/                        # Configuration files
├── app.php                    # Application configuration
├── database.php               # Database configuration
└── ...                        # Other config files
```

### Database Structure
```
database/
├── factories/                 # Model factories
├── migrations/                # Database migrations
└── seeders/                   # Database seeders
```

### Frontend Assets
```
resources/
├── css/                       # Stylesheets (Tailwind CSS v4)
├── js/                        # JavaScript files
└── views/                     # Blade templates
```

### Testing Structure
```
tests/
├── Feature/                   # Feature tests (HTTP, integration)
├── Unit/                      # Unit tests (isolated logic)
└── TestCase.php               # Base test class
```

## Naming Conventions

### Files and Classes
- Controllers: `UserController.php` (singular, PascalCase)
- Models: `User.php` (singular, PascalCase)
- Migrations: `create_users_table.php` (snake_case)
- Requests: `StoreUserRequest.php` (descriptive, PascalCase)
- Resources: `UserResource.php` (singular, PascalCase)
- Jobs: `ProcessPaymentJob.php` (descriptive, PascalCase)
- Events: `UserRegistered.php` (past tense, PascalCase)
- Listeners: `SendWelcomeEmail.php` (descriptive action, PascalCase)

### Database Conventions
- Table names: `users`, `user_profiles` (plural, snake_case)
- Column names: `first_name`, `created_at` (snake_case)
- Foreign keys: `user_id`, `category_id` (singular_snake_case + _id)
- Pivot tables: `user_roles` (alphabetical order, singular)

### Routes and URLs
- Route names: `users.index`, `users.show` (resource.action)
- URLs: `/users`, `/users/123/edit` (plural resources)
- API routes: `/api/v1/users` (versioned)

## Organization Patterns

### Service Layer Organization
- Create service classes for complex business logic
- Place in `app/Services/` directory
- Use dependency injection for service dependencies
- Keep controllers thin by delegating to services

### Repository Pattern (Optional)
- Use for complex data access patterns
- Place in `app/Repositories/` directory
- Bind interfaces to implementations in service providers
- Useful for testing and multiple data sources

### Feature Organization
- Group related functionality together
- Use subdirectories for large features
- Example: `app/Http/Controllers/Admin/` for admin features
- Keep related classes close to each other

## File Organization Best Practices

### Controller Organization
- Use resource controllers for CRUD operations
- Group related controllers in subdirectories
- Keep controller methods focused and thin
- Use Form Requests for validation

### Model Organization
- Place models in appropriate subdirectories if many exist
- Use traits for shared model functionality
- Keep model files focused on data representation
- Use observers for model event handling

### Migration Organization
- Use descriptive migration names
- Order migrations chronologically
- Use separate migrations for different concerns
- Include rollback logic in down() methods

### Test Organization
- Mirror application structure in tests
- Use descriptive test method names
- Group related tests in the same class
- Use factories for test data creation

## Configuration Management

### Environment Configuration
- Use `.env` for environment-specific values
- Define defaults in config files
- Never commit `.env` files
- Use `config()` helper, never `env()` directly in code

### Service Configuration
- Create dedicated config files for services
- Use array structures for complex configuration
- Validate configuration values in service providers
- Document configuration options