# Performance Optimization Guidelines

## Database Performance
- Always use eager loading to prevent N+1 queries
- Use `with()` for relationships that will be accessed
- Implement database indexing on frequently queried columns
- Use `select()` to limit columns when not all are needed
- Prefer `chunk()` for processing large datasets
- Use database transactions for multiple related operations
- Implement query caching where appropriate

## Eloquent Optimization
- Use `Model::query()` instead of `DB::` for consistency
- Implement model caching for frequently accessed data
- Use `exists()` instead of `count() > 0`
- Prefer `whereHas()` over joins when appropriate
- Use `latest()` and `oldest()` instead of `orderBy()`
- Implement soft deletes judiciously

## Caching Strategies
- Cache expensive database queries
- Use Redis for session and cache storage
- Implement view caching for static content
- Cache API responses with appropriate TTL
- Use model caching for frequently accessed records
- Implement cache tags for efficient invalidation

## Frontend Performance
- Minimize CSS and JavaScript bundles
- Use Vite for optimal asset bundling
- Implement lazy loading for images and components
- Use CDN for static assets
- Minimize HTTP requests
- Implement proper browser caching headers

## Queue Optimization
- Use queues for time-consuming operations
- Implement proper queue workers scaling
- Use different queues for different priority tasks
- Monitor queue performance and failures
- Implement queue batching for related jobs

## Memory Management
- Avoid loading large datasets into memory
- Use generators for processing large collections
- Implement proper garbage collection
- Monitor memory usage in long-running processes
- Use streaming for large file operations

## API Performance
- Implement proper pagination
- Use API resources for consistent responses
- Cache API responses appropriately
- Implement rate limiting
- Use compression for API responses
- Minimize payload sizes