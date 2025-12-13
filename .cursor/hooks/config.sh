#!/usr/bin/env bash
# Cursor Hooks Configuration
# This file contains configuration variables for all Cursor hooks

# Auto-formatting
ENABLE_AUTO_FORMAT=true

# Auto-testing (disabled by default for speed)
ENABLE_AUTO_TEST=false

# PHPStan static analysis (disabled by default)
ENABLE_PHPSTAN=false

# Block dangerous commands
BLOCK_DANGEROUS_COMMANDS=true

# Log directory
HOOKS_LOG_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/logs"

# Project root
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

# Laravel Pint path
PINT_PATH="${PROJECT_ROOT}/vendor/bin/pint"

# PHPStan path
PHPSTAN_PATH="${PROJECT_ROOT}/vendor/bin/phpstan"

# PHPUnit/Artisan test command
TEST_COMMAND="cd ${PROJECT_ROOT} && php artisan test"

