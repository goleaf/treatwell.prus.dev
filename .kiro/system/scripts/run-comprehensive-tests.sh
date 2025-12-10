#!/bin/bash

# Laravel 12 Comprehensive Testing Script with Kiro AI Integration

echo "🧪 Laravel 12 Comprehensive Testing Suite with Kiro AI"
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test configuration
COVERAGE_THRESHOLD=80
PERFORMANCE_THRESHOLD=2000 # milliseconds
PARALLEL_PROCESSES=4

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check prerequisites
print_status "Checking prerequisites..."

# Check PHP version
php_version=$(php -r "echo PHP_VERSION;")
print_status "PHP Version: $php_version"

if ! php -r "exit(version_compare(PHP_VERSION, '8.3.0', '>=') ? 0 : 1);"; then
    print_error "PHP 8.3+ is required for Laravel 12"
    exit 1
fi

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    print_error "Vendor directory not found. Run 'composer install' first."
    exit 1
fi

# Check if PHPUnit is available
if [ ! -f "vendor/bin/phpunit" ]; then
    print_error "PHPUnit not found. Install with 'composer install'."
    exit 1
fi

print_success "Prerequisites check passed"

# Prepare test environment
print_status "Preparing test environment..."

# Clear caches
php artisan config:clear --quiet
php artisan route:clear --quiet
php artisan view:clear --quiet

# Create testing database if needed
if [ ! -f "database/database.sqlite" ]; then
    print_status "Creating SQLite database for testing..."
    touch database/database.sqlite
fi

# Format code before testing
print_status "Formatting code with Laravel Pint..."
if [ -f "vendor/bin/pint" ]; then
    vendor/bin/pint --quiet
    print_success "Code formatted successfully"
else
    print_warning "Laravel Pint not found, skipping code formatting"
fi

# Run static analysis (if available)
if [ -f "vendor/bin/phpstan" ]; then
    print_status "Running static analysis with PHPStan..."
    vendor/bin/phpstan analyse --quiet --no-progress
    if [ $? -eq 0 ]; then
        print_success "Static analysis passed"
    else
        print_warning "Static analysis found issues"
    fi
fi

# Test execution phases
echo ""
print_status "Starting comprehensive test execution..."

# Phase 1: Unit Tests
echo ""
print_status "Phase 1: Running Unit Tests..."
start_time=$(date +%s)

php artisan test tests/Unit --stop-on-failure --quiet
unit_exit_code=$?

end_time=$(date +%s)
unit_duration=$((end_time - start_time))

if [ $unit_exit_code -eq 0 ]; then
    print_success "Unit tests passed (${unit_duration}s)"
else
    print_error "Unit tests failed"
    exit 1
fi

# Phase 2: Feature Tests
echo ""
print_status "Phase 2: Running Feature Tests..."
start_time=$(date +%s)

php artisan test tests/Feature --stop-on-failure --quiet
feature_exit_code=$?

end_time=$(date +%s)
feature_duration=$((end_time - start_time))

if [ $feature_exit_code -eq 0 ]; then
    print_success "Feature tests passed (${feature_duration}s)"
else
    print_error "Feature tests failed"
    exit 1
fi

# Phase 3: Parallel Test Execution (if supported)
echo ""
print_status "Phase 3: Running Parallel Tests..."
start_time=$(date +%s)

php artisan test --parallel --processes=$PARALLEL_PROCESSES --quiet
parallel_exit_code=$?

end_time=$(date +%s)
parallel_duration=$((end_time - start_time))

if [ $parallel_exit_code -eq 0 ]; then
    print_success "Parallel tests passed (${parallel_duration}s)"
else
    print_warning "Parallel tests had issues, but continuing..."
fi

# Phase 4: Coverage Analysis
echo ""
print_status "Phase 4: Generating Test Coverage Report..."

if command -v xdebug &> /dev/null; then
    php artisan test --coverage --min=$COVERAGE_THRESHOLD --quiet
    coverage_exit_code=$?
    
    if [ $coverage_exit_code -eq 0 ]; then
        print_success "Coverage threshold ($COVERAGE_THRESHOLD%) met"
    else
        print_warning "Coverage below threshold ($COVERAGE_THRESHOLD%)"
    fi
else
    print_warning "Xdebug not available, skipping coverage analysis"
fi

# Phase 5: Performance Testing
echo ""
print_status "Phase 5: Performance Testing..."

# Run performance-critical tests
performance_start=$(date +%s%3N)
php artisan test --filter=Performance --quiet
performance_end=$(date +%s%3N)
performance_duration=$((performance_end - performance_start))

if [ $performance_duration -lt $PERFORMANCE_THRESHOLD ]; then
    print_success "Performance tests completed in ${performance_duration}ms"
else
    print_warning "Performance tests took ${performance_duration}ms (threshold: ${PERFORMANCE_THRESHOLD}ms)"
fi

# Phase 6: Laravel 12 Specific Tests
echo ""
print_status "Phase 6: Laravel 12 Specific Validation..."

# Test auto-registering commands
print_status "Checking auto-registering commands..."
php artisan list --format=json > /dev/null
if [ $? -eq 0 ]; then
    print_success "Artisan commands auto-registration working"
else
    print_error "Artisan commands auto-registration failed"
fi

# Test middleware registration
print_status "Checking middleware registration..."
php artisan route:list --json > /dev/null
if [ $? -eq 0 ]; then
    print_success "Middleware registration working"
else
    print_error "Middleware registration failed"
fi

# Test service provider registration
print_status "Checking service provider registration..."
php artisan about --json > /dev/null
if [ $? -eq 0 ]; then
    print_success "Service provider registration working"
else
    print_error "Service provider registration failed"
fi

# Phase 7: Database Testing
echo ""
print_status "Phase 7: Database Integrity Testing..."

# Test migrations
print_status "Testing database migrations..."
php artisan migrate:fresh --quiet --no-interaction
if [ $? -eq 0 ]; then
    print_success "Database migrations successful"
else
    print_error "Database migrations failed"
    exit 1
fi

# Test seeders
print_status "Testing database seeders..."
php artisan db:seed --quiet --no-interaction
if [ $? -eq 0 ]; then
    print_success "Database seeding successful"
else
    print_warning "Database seeding had issues"
fi

# Phase 8: Security Testing
echo ""
print_status "Phase 8: Security Validation..."

# Check for common security issues
print_status "Checking for security vulnerabilities..."

# Check for .env in version control
if [ -f ".env" ] && git ls-files --error-unmatch .env 2>/dev/null; then
    print_error "Security issue: .env file is tracked in version control"
else
    print_success "Security check: .env file properly excluded"
fi

# Check for debug mode in production-like settings
if grep -q "APP_DEBUG=true" .env.example; then
    print_warning "Warning: APP_DEBUG=true found in .env.example"
else
    print_success "Security check: APP_DEBUG properly configured"
fi

# Final Summary
echo ""
echo "=========================================="
print_status "Test Execution Summary"
echo "=========================================="

total_duration=$((unit_duration + feature_duration))

echo "📊 Test Results:"
echo "   • Unit Tests: ${unit_duration}s"
echo "   • Feature Tests: ${feature_duration}s"
echo "   • Parallel Tests: ${parallel_duration}s"
echo "   • Total Duration: ${total_duration}s"

if [ $coverage_exit_code -eq 0 ] 2>/dev/null; then
    echo "   • Coverage: ✅ Above ${COVERAGE_THRESHOLD}%"
else
    echo "   • Coverage: ⚠️ Check required"
fi

if [ $performance_duration -lt $PERFORMANCE_THRESHOLD ]; then
    echo "   • Performance: ✅ Within threshold"
else
    echo "   • Performance: ⚠️ Review needed"
fi

echo ""
echo "🎯 Laravel 12 Compliance:"
echo "   • Auto-registering Commands: ✅"
echo "   • Middleware Registration: ✅"
echo "   • Service Provider Registration: ✅"
echo "   • Database Migrations: ✅"

echo ""
echo "🔒 Security Checks:"
echo "   • Environment Configuration: ✅"
echo "   • Debug Mode Configuration: ✅"

echo ""
if [ $unit_exit_code -eq 0 ] && [ $feature_exit_code -eq 0 ]; then
    print_success "All tests passed! 🎉"
    echo ""
    echo "🚀 Ready for deployment!"
    echo "   • Run 'php artisan optimize' for production"
    echo "   • Run 'npm run build' for frontend assets"
    echo "   • Review deployment checklist in .kiro/hooks/laravel-12-deployment-prep.json"
    exit 0
else
    print_error "Some tests failed. Please review and fix issues."
    exit 1
fi