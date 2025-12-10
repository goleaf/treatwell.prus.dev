# Feature Development Workflow

## Pre-Development Phase
1. **Requirements Analysis**
   - Define feature requirements clearly
   - Identify affected models and relationships
   - Plan database schema changes
   - Consider security implications

2. **Design Planning**
   - Create wireframes or mockups if UI changes
   - Plan API endpoints if applicable
   - Design database relationships
   - Plan testing strategy

## Development Phase

### 1. Database Layer
- Create migrations: `php artisan make:migration create_table_name --no-interaction`
- Create models: `php artisan make:model ModelName -mfs --no-interaction`
- Define relationships in models
- Create factories and seeders
- Run migrations: `php artisan migrate`

### 2. Business Logic Layer
- Create service classes for complex logic
- Implement repository pattern if needed
- Create custom exceptions if required
- Add validation rules in Form Requests

### 3. API/Controller Layer
- Create controllers: `php artisan make:controller ControllerName --resource --no-interaction`
- Create Form Requests: `php artisan make:request RequestName --no-interaction`
- Create API Resources: `php artisan make:resource ResourceName --no-interaction`
- Define routes in appropriate route files

### 4. Frontend Layer (if applicable)
- Create Blade templates
- Add Tailwind CSS styling
- Implement JavaScript functionality
- Build assets: `npm run build`

### 5. Testing Layer
- Create feature tests: `php artisan make:test FeatureTest --no-interaction`
- Create unit tests: `php artisan make:test UnitTest --unit --no-interaction`
- Test all happy paths and edge cases
- Run tests: `php artisan test --filter=TestName`

## Quality Assurance

### Code Quality
- Format code: `vendor/bin/pint --dirty`
- Run full test suite: `php artisan test`
- Check for N+1 queries
- Verify proper error handling

### Security Review
- Validate all user inputs
- Check authorization on all endpoints
- Verify CSRF protection
- Review for SQL injection vulnerabilities

### Performance Review
- Check database query efficiency
- Implement caching where appropriate
- Verify proper indexing
- Test with realistic data volumes

## Deployment Preparation
- Update documentation
- Create deployment notes
- Plan rollback strategy
- Verify environment configurations

## Post-Development
- Monitor error logs
- Gather user feedback
- Plan iterative improvements
- Document lessons learned