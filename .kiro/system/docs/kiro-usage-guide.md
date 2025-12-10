# Kiro Usage Guide

## Overview
This guide explains how to effectively use Kiro for Laravel development with the configured specs, hooks, and steering files.

## Specs Usage

### Available Specs
- `feature-development.md` - Complete feature development workflow
- `model-creation.md` - Model creation with relationships and factories
- `crud-resource.md` - CRUD resource creation with controllers and views
- `api-endpoint.md` - API endpoint development with resources
- `authentication-system.md` - Authentication and authorization setup
- `database-migration.md` - Database migration and schema changes
- `queue-job.md` - Queue job creation and processing
- `middleware-creation.md` - Custom middleware development
- `event-listener-system.md` - Event-driven architecture setup
- `notification-system.md` - Multi-channel notification system
- `command-creation.md` - Artisan command development
- `service-provider.md` - Service provider creation and binding

### Using Specs
1. Choose appropriate spec for your task
2. Follow the structured workflow in the spec
3. Use checkboxes to track progress
4. Reference related files and examples

## Hooks Automation

### Active Hooks
- `format-on-save.json` - Auto-format code with Pint on save
- `test-on-save.json` - Run relevant tests when files change
- `build-frontend.json` - Build frontend assets when needed
- `migration-reminder.json` - Remind to run migrations
- `env-validation.json` - Validate environment configuration
- `composer-update.json` - Remind about composer updates
- `route-cache-clear.json` - Clear route cache on route changes
- `view-cache-clear.json` - Clear view cache on template changes

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