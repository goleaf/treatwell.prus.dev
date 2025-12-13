#!/bin/bash

# Laravel Development Environment Setup Script

echo "🚀 Setting up Laravel development environment..."

# Check if .env exists
if [ ! -f .env ]; then
    echo "📋 Creating .env file from .env.example..."
    cp .env.example .env
fi

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install

# Generate application key
echo "🔑 Generating application key..."
php artisan key:generate

# Install Node.js dependencies
echo "📦 Installing Node.js dependencies..."
npm install

# Create database if it doesn't exist (SQLite)
if [ ! -f database/database.sqlite ]; then
    echo "🗄️ Creating SQLite database..."
    touch database/database.sqlite
fi

# Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate

# Seed database with sample data
echo "🌱 Seeding database..."
php artisan db:seed

# Clear and cache configurations
echo "⚡ Optimizing application..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Build frontend assets
echo "🎨 Building frontend assets..."
npm run build

# Set proper permissions
echo "🔒 Setting proper permissions..."
chmod -R 755 storage bootstrap/cache

echo "✅ Development environment setup complete!"
echo ""
echo "🎯 Next steps:"
echo "   • Start development server: php artisan serve"
echo "   • Start asset watcher: npm run dev"
echo "   • Run tests: php artisan test"
echo ""