# Kiro Hooks

This directory contains automated hooks for Laravel 12 development workflow optimization.

## Hook Structure

All hooks follow a standardized JSON structure:

```json
{
  "enabled": true,
  "name": "Hook Name",
  "description": "Hook description",
  "version": "1.0",
  "when": {
    "type": "fileEdited|fileCreated|sessionStart|manual",
    "patterns": ["file/patterns/**/*.php"]
  },
  "then": {
    "type": "askAgent",
    "prompt": "Detailed prompt for the AI agent..."
  }
}
```

## Available Hooks

### Core Development Hooks
- **code-quality-analyzer**: Analyzes code for quality improvements, code smells, and best practices
- **comprehensive-development-workflow**: Complete Laravel 12 development workflow automation
- **migration-check**: Checks for pending migrations on session start
- **performance-check**: Manual performance analysis and optimization
- **security-audit**: Manual security audit of the codebase

### Laravel 12 Specific Hooks
- **laravel-12-artisan-optimization**: Artisan command optimization and best practices
- **laravel-12-code-quality**: Code quality assurance for Laravel 12
- **laravel-12-controller-creation**: Controller creation workflow with Form Requests
- **laravel-12-deployment-prep**: Comprehensive deployment preparation
- **laravel-12-event-system**: Event and listener management
- **laravel-12-middleware-management**: Middleware creation and registration
- **laravel-12-migration-workflow**: Migration handling with factory/seeder reminders
- **laravel-12-model-creation**: Model creation with Laravel 12 best practices
- **laravel-12-performance-monitor**: Performance monitoring and optimization
- **laravel-12-queue-management**: Queue job creation and management
- **laravel-12-security-audit**: Security checks and best practices
- **laravel-12-service-provider**: Service provider management
- **laravel-12-test-automation**: PHPUnit testing workflow

### Frontend Hooks
- **frontend-asset-optimization**: Tailwind CSS v4 and Vite optimization

### Deployment Hooks
- **deployment-prep**: General deployment preparation

## Hook Types

### Trigger Types
- `fileEdited`: Triggered when files matching patterns are modified
- `fileCreated`: Triggered when new files matching patterns are created
- `sessionStart`: Triggered when a new session starts
- `manual`: Triggered manually by user action

### Action Types
- `askAgent`: Sends a prompt to the AI agent for processing

## Best Practices

1. **Consistent Structure**: All hooks follow the same JSON structure
2. **Descriptive Names**: Hook names clearly indicate their purpose
3. **Specific Patterns**: File patterns are specific to avoid unnecessary triggers
4. **Actionable Prompts**: Prompts provide clear, actionable guidance
5. **Laravel 12 Focus**: Hooks are optimized for Laravel 12 conventions and features

## Validation

All hooks have been validated for:
- ✅ Valid JSON structure
- ✅ Required fields present
- ✅ Consistent naming conventions
- ✅ Appropriate file patterns
- ✅ Clear, actionable prompts