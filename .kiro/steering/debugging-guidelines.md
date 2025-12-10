# Debugging Guidelines

## Laravel Debugging Tools
- Use `tinker` tool for interactive PHP debugging and Eloquent queries
- Use `database-query` tool for direct database inspection
- Use `browser-logs` tool to read browser console errors and exceptions
- Enable Laravel Telescope for request/query debugging in development
- Use `php artisan route:list` to verify route definitions

## Common Debugging Scenarios

### Database Issues
- Use `tinker` to test Eloquent queries interactively
- Check query logs: `DB::enableQueryLog()` then `DB::getQueryLog()`
- Verify relationships with `$model->relationshipName()->toSql()`
- Use `explain()` on queries to check performance
- Check for N+1 queries with Laravel Debugbar

### Authentication Problems
- Verify middleware registration in `bootstrap/app.php`
- Check user authentication: `Auth::check()` and `Auth::user()`
- Test authorization with `Gate::allows()` or `$user->can()`
- Verify session configuration and storage
- Check CSRF token issues in forms

### API Debugging
- Use browser developer tools Network tab
- Test API endpoints with tools like Postman or curl
- Check API response format and status codes
- Verify request validation rules
- Monitor API rate limiting

### Frontend Issues
- Use `browser-logs` tool to check JavaScript errors
- Verify asset compilation: `npm run build` or `npm run dev`
- Check Vite manifest for missing assets
- Inspect network requests for failed asset loads
- Verify Tailwind classes are being applied

### Queue Problems
- Check queue worker status: `php artisan queue:work --once`
- Monitor failed jobs: `php artisan queue:failed`
- Verify queue configuration in `.env`
- Check job class implementation and dependencies
- Monitor queue processing with Horizon if installed

## Debugging Techniques

### Logging Strategy
- Use appropriate log levels (debug, info, warning, error)
- Log context data with messages: `Log::info('Message', ['data' => $data])`
- Use structured logging for better searchability
- Avoid logging sensitive information
- Use different log channels for different components

### Error Handling
- Use try-catch blocks for expected exceptions
- Log exceptions with full context
- Return meaningful error messages to users
- Use Laravel's exception handling for consistency
- Implement custom exception classes when needed

### Performance Debugging
- Use Laravel Debugbar to monitor queries and performance
- Profile slow queries with `EXPLAIN`
- Monitor memory usage with `memory_get_usage()`
- Use Xdebug for step-by-step debugging
- Implement query caching for expensive operations

### Testing for Debugging
- Write tests to reproduce bugs
- Use `php artisan test --filter=TestName` for focused testing
- Test edge cases and error conditions
- Use factories to create consistent test data
- Mock external dependencies in tests

## Environment-Specific Debugging

### Development Environment
- Set `APP_DEBUG=true` for detailed error pages
- Use `dd()` and `dump()` for quick debugging
- Enable query logging and debugging tools
- Use hot reloading for frontend development
- Keep detailed logs for all operations

### Production Debugging
- Never set `APP_DEBUG=true` in production
- Use proper logging and monitoring tools
- Implement error tracking (Sentry, Bugsnag)
- Monitor application performance metrics
- Use structured logging for better analysis

## Common Issues and Solutions

### "Class not found" Errors
- Run `composer dump-autoload`
- Check namespace declarations
- Verify file naming conventions
- Check use statements at top of files

### Route Issues
- Clear route cache: `php artisan route:clear`
- Check route definitions and naming
- Verify middleware assignments
- Test route parameters and constraints

### Database Connection Issues
- Verify database credentials in `.env`
- Check database server status
- Test connection with `php artisan tinker` then `DB::connection()->getPdo()`
- Verify database permissions