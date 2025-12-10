# Kiro Laravel 12 Usage Guide

## Overview
This comprehensive guide explains how to effectively use Kiro AI for Laravel 12 development with advanced automation, best practices enforcement, and intelligent workflows.

## Laravel 12 Specific Features

### Streamlined Architecture
- **No middleware registration files** - Use `bootstrap/app.php` with `withMiddleware()`
- **Auto-registering commands** - Files in `app/Console/Commands/` automatically available
- **Service provider registration** - Use `bootstrap/providers.php`
- **Modern PHP 8.3+ features** - Constructor property promotion, explicit return types

### Enhanced Performance
- **Optimized autoloading** - Built-in performance improvements
- **Streamlined configuration** - Reduced overhead and faster bootstrapping
- **Improved caching** - Enhanced config, route, and view caching

## Specs Usage

### Available Specs (Updated for Laravel 12)
- `feature-development.md` - Complete Laravel 12 feature development workflow
- `model-creation.md` - Model creation with `casts()` method and relationships
- `crud-resource.md` - CRUD resource creation with Form Requests
- `api-endpoint.md` - API endpoint development with Laravel 12 resources
- `authentication-system.md` - Laravel 12 authentication and authorization
- `database-migration.md` - Database migration with Laravel 12 conventions
- `queue-job.md` - Queue job creation with Laravel 12 improvements
- `middleware-creation.md` - Laravel 12 middleware with `bootstrap/app.php` registration
- `event-listener-system.md` - Event-driven architecture with Laravel 12 features
- `notification-system.md` - Multi-channel notifications with Laravel 12 enhancements
- `command-creation.md` - Auto-registering Artisan commands
- `service-provider.md` - Service provider with `bootstrap/providers.php` registration

### Using Specs
1. Choose appropriate spec for your task
2. Follow the structured workflow in the spec
3. Use checkboxes to track progress
4. Reference related files and examples

## Advanced Hooks Automation (Laravel 12 Optimized)

### Core Development Workflow
- **comprehensive-development-workflow.json** - Complete automation (formatting, testing, caching)
- **laravel-12-code-quality.json** - Laravel 12 best practices enforcement
- **laravel-12-artisan-optimization.json** - Artisan command optimization

### Component Creation Automation
- **laravel-12-model-creation.json** - Model creation with Laravel 12 conventions
- **laravel-12-controller-creation.json** - Controller with Form Requests and resources
- **laravel-12-middleware-management.json** - Middleware with `bootstrap/app.php` registration
- **laravel-12-service-provider.json** - Service provider with `bootstrap/providers.php`

### Database & Migration Management
- **laravel-12-migration-workflow.json** - Migration with factory/seeder automation
- **laravel-12-queue-management.json** - Queue job creation and monitoring

### Frontend & Assets (Tailwind CSS v4)
- **frontend-asset-optimization.json** - Tailwind CSS v4 and Vite optimization

### Testing & Quality Assurance
- **laravel-12-test-automation.json** - PHPUnit testing workflow (Laravel 12 compliant)
- **laravel-12-security-audit.json** - Security checks and vulnerability scanning

### Performance & Optimization
- **laravel-12-performance-monitor.json** - Performance monitoring and optimization
- **laravel-12-deployment-prep.json** - Pre-deployment checklist and optimization

### Advanced Features
- **laravel-12-event-system.json** - Event and listener management

### Hook Management
- Hooks automatically trigger based on file changes
- Enable/disable hooks by editing the JSON files
- Monitor hook execution in Kiro's hook panel

## Steering Guidelines

### Available Guidelines
- `laravel-conventions.md` - Laravel coding standards and conventions
- `frontend-guidelines.md` - Frontend development with Tailwind CSS v4
- `api-development.md` - API development best practices
- `testing-standards.md` - Testing requirements and patterns
- `security-guidelines.md` - Security best practices
- `performance-optimization.md` - Performance optimization strategies
- `deployment-guidelines.md` - Deployment and production guidelines
- `debugging-guidelines.md` - Debugging tools and techniques
- `code-review-checklist.md` - Code review requirements
- `project-structure.md` - Project organization and structure

### Steering Integration
- Guidelines are automatically included in relevant contexts
- Use `#steering-file-name` to manually include specific guidelines
- Guidelines provide context-aware assistance

## System Templates

### Available Templates
- `controller-template.php` - Resource controller template
- `model-template.php` - Eloquent model template
- `request-template.php` - Form request validation template

### Using Templates
- Templates provide starting points for common classes
- Customize placeholders ({{ModelName}}, {{modelName}}, etc.)
- Follow established patterns and conventions

## Workflows

### Feature Development Workflow
1. Use `feature-development.md` spec
2. Follow the structured development phases
3. Leverage hooks for automation
4. Apply steering guidelines throughout

### Testing Workflow
1. Write tests alongside feature development
2. Use `testing-standards.md` guidelines
3. Run tests with `test-on-save.json` hook
4. Follow PHPUnit conventions

### Deployment Workflow
1. Follow `deployment-guidelines.md`
2. Use pre-deployment checklist
3. Verify all tests pass
4. Format code with Pint

## Best Practices

### Development Flow
1. Start with appropriate spec
2. Let hooks automate repetitive tasks
3. Follow steering guidelines
4. Use templates for consistency
5. Test continuously

### Code Quality
- Format code automatically with hooks
- Follow Laravel conventions
- Write comprehensive tests
- Use proper type hints and documentation

### Performance
- Follow performance optimization guidelines
- Monitor database queries
- Implement caching strategies
- Use queues for heavy operations

## Troubleshooting

### Common Issues
- Hook not triggering: Check file patterns and permissions
- Tests failing: Verify database setup and factories
- Assets not building: Check Node.js dependencies
- Code formatting: Ensure Pint is installed and configured

### Getting Help
- Check relevant steering guidelines
- Review spec requirements
- Use Kiro's debugging tools
- Consult Laravel documentation