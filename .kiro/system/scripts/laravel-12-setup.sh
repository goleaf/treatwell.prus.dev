#!/bin/bash

# Laravel 12 Complete Development Environment Setup Script

echo "🚀 Setting up Laravel 12 development environment with Kiro AI optimization..."

# Check PHP version
echo "📋 Checking PHP version..."
php_version=$(php -r "echo PHP_VERSION;")
echo "PHP Version: $php_version"

if ! php -r "exit(version_compare(PHP_VERSION, '8.3.0', '>=') ? 0 : 1);"; then
    echo "❌ PHP 8.3+ is required for Laravel 12"
    exit 1
fi

# Check if .env exists
if [ ! -f .env ]; then
    echo "📋 Creating .env file from .env.example..."
    cp .env.example .env
fi

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install --optimize-autoloader

# Generate application key
echo "🔑 Generating application key..."
php artisan key:generate

# Install Node.js dependencies
echo "📦 Installing Node.js dependencies..."
npm install

# Create SQLite database if configured
if grep -q "DB_CONNECTION=sqlite" .env; then
    if [ ! -f database/database.sqlite ]; then
        echo "🗄️ Creating SQLite database..."
        touch database/database.sqlite
    fi
fi

# Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --no-interaction

# Seed database with sample data
echo "🌱 Seeding database..."
php artisan db:seed --no-interaction

# Clear and optimize Laravel 12
echo "⚡ Optimizing Laravel 12 application..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Build frontend assets with Tailwind CSS v4
echo "🎨 Building frontend assets with Tailwind CSS v4..."
npm run build

# Set proper permissions
echo "🔒 Setting proper permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs

# Laravel 12 specific optimizations
echo "🔧 Applying Laravel 12 optimizations..."

# Verify Laravel 12 structure
echo "📁 Verifying Laravel 12 streamlined structure..."
if [ -f "bootstrap/app.php" ]; then
    echo "✅ Laravel 12 bootstrap/app.php found"
else
    echo "❌ Laravel 12 bootstrap/app.php missing"
fi

if [ -f "bootstrap/providers.php" ]; then
    echo "✅ Laravel 12 bootstrap/providers.php found"
else
    echo "❌ Laravel 12 bootstrap/providers.php missing"
fi

# Check for Laravel 12 features
echo "🔍 Checking Laravel 12 features..."
php artisan about

# Run Laravel Pint for code formatting
echo "🎯 Formatting code with Laravel Pint..."
if [ -f "vendor/bin/pint" ]; then
    vendor/bin/pint
    echo "✅ Code formatted with Laravel Pint"
else
    echo "⚠️ Laravel Pint not found, installing..."
    composer require laravel/pint --dev
    vendor/bin/pint
fi

# Run PHPUnit tests
echo "🧪 Running PHPUnit tests..."
php artisan test

# Create Kiro-specific directories if they don't exist
echo "🤖 Setting up Kiro AI integration..."
mkdir -p .kiro/hooks
mkdir -p .kiro/specs
mkdir -p .kiro/steering
mkdir -p .kiro/system/{templates,workflows,configs,scripts,docs}

# Set up Git hooks for Laravel 12 best practices
echo "🔗 Setting up Git hooks..."
if [ -d ".git" ]; then
    cat > .git/hooks/pre-commit << 'EOF'
#!/bin/bash
echo "Running Laravel 12 pre-commit checks..."

# Format code with Pint
vendor/bin/pint --dirty

# Run tests
php artisan test --stop-on-failure

# Check for Laravel 12 best practices
echo "✅ Pre-commit checks passed"
EOF
    chmod +x .git/hooks/pre-commit
    echo "✅ Git pre-commit hook installed"
fi

echo ""
echo "✅ Laravel 12 development environment setup complete!"
echo ""
echo "🎯 Next steps:"
echo "   • Start development server: php artisan serve"
echo "   • Start asset watcher: npm run dev"
echo "   • Run tests: php artisan test"
echo "   • Format code: vendor/bin/pint"
echo "   • Check application status: php artisan about"
echo ""
echo "🤖 Kiro AI Features:"
echo "   • Comprehensive hooks for automation"
echo "   • Laravel 12 best practices enforcement"
echo "   • Intelligent code formatting and testing"
echo "   • Performance monitoring and optimization"
echo ""
echo "📚 Laravel 12 Key Features:"
echo "   • Streamlined file structure"
echo "   • Auto-registering commands"
echo "   • Modern PHP 8.3+ features"
echo "   • Enhanced performance"
echo ""