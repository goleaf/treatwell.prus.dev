# Code Review Checklist

## General Code Quality
- [ ] Code follows Laravel conventions and project standards
- [ ] Descriptive variable and method names are used
- [ ] Code is properly formatted with Pint: `vendor/bin/pint --dirty`
- [ ] No commented-out code or debug statements
- [ ] Proper error handling is implemented
- [ ] Code is DRY (Don't Repeat Yourself)

## PHP & Laravel Specific
- [ ] PHP 8.3+ features are used appropriately
- [ ] Constructor property promotion is used where applicable
- [ ] Explicit return type declarations on all methods
- [ ] Proper type hints for method parameters
- [ ] Laravel 12 conventions are followed
- [ ] Eloquent relationships have proper return types
- [ ] N+1 queries are prevented with eager loading

## Security Review
- [ ] User input is properly validated using Form Requests
- [ ] No raw SQL queries with user input
- [ ] Authentication and authorization are properly implemented
- [ ] CSRF protection is in place for forms
- [ ] Sensitive data is not logged or exposed
- [ ] Environment variables are used for secrets

## Database & Models
- [ ] Migrations are reversible and safe
- [ ] Model relationships are properly defined
- [ ] Database queries are optimized
- [ ] Proper indexing is considered
- [ ] Factories and seeders are created for new models
- [ ] `casts()` method is used instead of `$casts` property

## Testing Requirements
- [ ] All new functionality has PHPUnit tests
- [ ] Tests cover happy paths, failure paths, and edge cases
- [ ] Tests use model factories appropriately
- [ ] Tests are properly isolated and don't depend on each other
- [ ] Test names are descriptive and clear

## Frontend & Assets
- [ ] Tailwind CSS v4 conventions are followed
- [ ] Dark mode support is consistent with existing components
- [ ] Gap utilities are used for spacing instead of margins
- [ ] Assets are properly built: `npm run build`
- [ ] No hardcoded styles or inline CSS

## Performance Considerations
- [ ] Database queries are optimized
- [ ] Caching is implemented where appropriate
- [ ] Large datasets use chunking or pagination
- [ ] Queue jobs are used for time-consuming operations
- [ ] Memory usage is considered for large operations

## Documentation & Comments
- [ ] PHPDoc blocks are used appropriately
- [ ] Complex logic is documented
- [ ] API endpoints are documented
- [ ] README files are updated if needed
- [ ] Inline comments explain "why" not "what"

## Deployment Readiness
- [ ] Environment configurations are properly set
- [ ] No hardcoded values that should be configurable
- [ ] Proper logging is implemented
- [ ] Error handling provides useful feedback
- [ ] Performance impact is considered