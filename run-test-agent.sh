#!/bin/bash

# Test Coverage Agent Runner
cd "$(dirname "$0")"

echo "Starting Test Coverage Agent..."
echo "Press Ctrl+C to stop"
echo ""

php test-coverage-agent.php
