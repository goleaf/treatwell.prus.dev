# Laravel 12 Kiro Hooks System

## Overview
This directory contains comprehensive automation hooks for Laravel 12 development with Kiro AI. These hooks are designed to enforce best practices, automate repetitive tasks, and ensure code quality throughout the development lifecycle.

## Hook Categories

### 🔄 Core Development Workflow
- **comprehensive-development-workflow.json** - Main development automation (formatting, testing, caching)
- **laravel-12-code-quality.json** - Code quality assurance and best practices enforcement

### 🏗️ Laravel 12 Component Creation
- **laravel-12-model-creation.json** - Model creation with factories, seeders, and relationships
- **laravel-12-controller-creation.json** - Controller creation with Form Requests and resources
- **laravel-12-middleware-management.json** - Middleware creation and registration
- **laravel-12-service-provider.json** - Service provider management and registration

### 🗄️ Database & Migration Management
- **laravel-12-migration-workflow.json** - Migration handling with factory/seeder reminders
- **laravel-12-queue-management.json** - Queue job creation and management

### 🎨 Frontend & Assets
- **frontend-asset-optimization.json** - Tailwind CSS v4 and Vite optimization

### 🧪 Testing & Quality Assurance
- **laravel-12-test-automation.json** - PHPUnit testing workflow
- **laravel-12-security-audit.json** - Security checks and best practices

### ⚡ Performance & Optimization
- **laravel-12-performance-monitor.json** - Performance optimization monitoring
- **laravel-12-artisan-optimization.json** - Artisan command optimization

### 🚀 Deployment & Production
- **laravel-12-deployment-prep.json** - Pre-deployment checklist and optimization

### 📡 Advanced Features
- **laravel-12-event-system.json** - Event and listener management

## Laravel 12 Specific Features

### Streamlined Structure
- No middleware files in `app/Http/Middleware/` registration
- `bootstrap/app.php` for middleware, exceptions, and routing
- `bootstrap/providers.php` for service provider registration
- Commands auto-register from `app/Console/Commands/`

### Modern PHP Features
- PHP 8.3+ constructor property promotion
- Explicit return type declarations
- Proper type hints for all methods
- `casts()` method instead of `$casts` property

### Best Practices Enforcement
- Laravel Pint code formatting
- PHPUnit testing (not Pest)
- Form Request validation
- Eager loading to prevent N+1 queries
- Proper error handling and logging

## Hook Triggers

### File-Based Triggers
- `file_saved` - Triggers when files are saved
- `file_created` - Triggers when new files are created
- `file_modified` - Triggers when files are modified

### Manual Triggers
- `manual` - Triggered by user action (button click)

### Pattern Matching
Hooks use glob patterns to match specific files:
- `app/**/*.php` - All PHP files in app directory
- `database/migrations/*.php` - Migration files only
- `resources/views/**/*.blade.php` - Blade templates

## Action Types

### Commands
Execute shell commands with proper error handling:
```json
{
  "type": "command",
  "command": "php artisan test --filter=TestName",
  "description": "Run specific test"
}
```

### Messages
Display informative messages to developers:
```json
{
  "type": "message",
  "content": "Laravel 12 best practices reminder..."
}
```

### Sequences
Execute multiple actions in order:
```json
{
  "type": "sequence",
  "steps": [...]
}
```

## Priority Levels
- **critical** - Security, deployment preparation
- **high** - Core development workflow, model/controller creation
- **medium** - Performance monitoring, queue management
- **low** - Documentation, optional optimizations

## Conditional Execution
Hooks support conditional execution based on:
- File patterns: `file_matches('config/**/*.php')`
- Environment: `production_environment`
- Custom conditions: `test_class_name`, `controller_name`

## Best Practices

### Hook Development
1. Use descriptive names and clear descriptions
2. Include version numbers for tracking changes
3. Set appropriate priority levels
4. Add proper error handling
5. Use conditional execution when appropriate

### Laravel 12 Compliance
1. Follow Laravel 12 streamlined structure
2. Use modern PHP 8.3+ features
3. Enforce proper type declarations
4. Implement comprehensive testing
5. Follow security best practices

### Performance Considerations
1. Use specific file patterns to avoid unnecessary triggers
2. Implement conditional execution for expensive operations
3. Use appropriate priority levels
4. Consider hook execution order

## Customization
Hooks can be customized by:
1. Modifying trigger patterns
2. Adjusting action sequences
3. Adding custom conditions
4. Changing priority levels
5. Enabling/disabling specific hooks

## Troubleshooting

### Common Issues
1. **Hook not triggering** - Check file patterns and permissions
2. **Command failures** - Verify command syntax and dependencies
3. **Performance issues** - Review trigger patterns and conditions

### Debugging
1. Check hook execution logs
2. Verify file pattern matching
3. Test commands manually
4. Review conditional logic

## Integration with Kiro AI
These hooks are designed to work seamlessly with Kiro AI features:
- Automatic code formatting with Laravel Pint
- Intelligent test execution
- Context-aware suggestions
- Performance monitoring
- Security auditing
- Deployment preparation

## Future Enhancements
- Machine learning-based hook suggestions
- Dynamic condition evaluation
- Integration with external tools
- Custom hook templates
- Performance analytics