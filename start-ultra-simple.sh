#!/bin/bash

# Ultra-simple Railway deployment script
# This should work 100% of the time

echo "=== Railway PHP Deployment ==="
echo "Port: $PORT"
echo "Environment: $CI_ENVIRONMENT"

# Create logs directory
mkdir -p /app/writable/logs

# Change to public directory
cd /app/public

# Start PHP built-in server
echo "Starting PHP server on 0.0.0.0:$PORT"
exec php -S 0.0.0.0:$PORT router.php
