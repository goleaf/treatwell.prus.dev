# Deployment Guidelines

## Pre-Deployment Checklist
- Run full test suite: `php artisan test`
- Format code: `vendor/bin/pint --dirty`
- Build frontend assets: `npm run build`
- Clear and cache configurations: `php artisan config:cache`
- Optimize autoloader: `composer install --optimize-autoloader --no-dev`
- Cache routes: `php artisan route:cache`
- Cache views: `php artisan view:cache`

## Environment Configuration
- Never deploy with `APP_DEBUG=true`
- Set `APP_ENV=production`
- Use strong `APP_KEY` in production
- Configure proper database credentials
- Set up Redis for caching and sessions
- Configure mail settings for production
- Set proper file permissions (755 for directories, 644 for files)

## Database Deployment
- Always backup database before migrations
- Run migrations: `php artisan migrate --force`
- Seed production data if needed: `php artisan db:seed --class=ProductionSeeder`
- Verify database connections and permissions
- Monitor migration performance on large tables

## Asset Management
- Build production assets: `npm run build`
- Use CDN for static assets when possible
- Implement proper cache headers
- Compress images and optimize assets
- Use asset versioning for cache busting

## Server Configuration
- Configure web server (Nginx/Apache) properly
- Set up SSL certificates
- Configure proper PHP settings (memory_limit, max_execution_time)
- Set up log rotation
- Configure firewall rules
- Implement proper backup strategies

## Monitoring & Logging
- Set up application monitoring
- Configure error tracking (Sentry, Bugsnag)
- Monitor database performance
- Set up uptime monitoring
- Configure log aggregation
- Monitor disk space and server resources

## Zero-Downtime Deployment
- Use deployment tools (Envoy, Deployer)
- Implement blue-green deployment strategy
- Use queue workers that can be gracefully restarted
- Implement health checks
- Use load balancers for traffic management
- Plan rollback strategies

## Post-Deployment Verification
- Verify application functionality
- Check error logs for issues
- Monitor performance metrics
- Verify queue workers are running
- Test critical user flows
- Monitor database performance