---
inclusion: always
---

# Laravel Application Conventions

## Code Style and Standards
- Always run `vendor/bin/pint --dirty` before committing changes
- Use PHP 8.3+ features including constructor property promotion
- Follow existing naming conventions in the codebase
- Use descriptive method and variable names

## Laravel 12 Specific Guidelines
- Use streamlined file structure (no app/Http/Middleware/)
- Register middleware in `bootstrap/app.php`
- Commands auto-register from `app/Console/Commands/`
- Use `casts()` method instead of `$casts` property in models

## Database and Models
- Always use Eloquent relationships with proper return types
- Prevent N+1 queries with eager loading
- Use `Model::query()` instead of `DB::`
- Create factories and seeders for new models

## Validation and Forms
- Always create Form Request classes for validation
- Include both rules and custom error messages
- Check sibling Form Requests for array vs string rule conventions

## Testing Requirements
- Write PHPUnit tests (convert any Pest tests to PHPUnit)
- Test happy paths, failure paths, and edge cases
- Run minimal tests with filters: `php artisan test --filter=TestName`
- Use model factories in tests

## Frontend Guidelines
- Use Tailwind CSS v4 with `@import "tailwindcss"`
- Support dark mode if existing components do
- Use gap utilities for spacing instead of margins
- Run `npm run build` or `composer run dev` for frontend changes

## Documentation Tools
- Use `search-docs` tool for Laravel ecosystem documentation
- Use `list-artisan-commands` tool before running artisan commands
- Use `get-absolute-url` tool for project URLs
- Use `tinker` tool for PHP debugging