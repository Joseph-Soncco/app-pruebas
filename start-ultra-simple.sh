#!/bin/bash

# Ultra-simple Railway deployment script with debug
# This will show us exactly what's happening

echo "=== Railway PHP Deployment Debug ==="
echo "Port: $PORT"
echo "Environment: $CI_ENVIRONMENT"
echo "Working directory: $(pwd)"
echo "PHP version: $(php --version | head -1)"

# Create logs directory
mkdir -p /app/writable/logs
echo "Created logs directory"

# Change to public directory
cd /app/public
echo "Changed to public directory: $(pwd)"

# List files in public directory
echo "Files in public directory:"
ls -la

# Test if health.php exists
if [ -f "health.php" ]; then
    echo "✓ health.php exists"
else
    echo "✗ health.php NOT FOUND"
fi

# Test if router.php exists
if [ -f "router.php" ]; then
    echo "✓ router.php exists"
else
    echo "✗ router.php NOT FOUND"
fi

# Test if index.php exists
if [ -f "index.php" ]; then
    echo "✓ index.php exists"
else
    echo "✗ index.php NOT FOUND"
fi

# Start PHP built-in server with verbose output
echo "Starting PHP server on 0.0.0.0:$PORT"
echo "Command: php -S 0.0.0.0:$PORT router.php"
exec php -S 0.0.0.0:$PORT router.php
