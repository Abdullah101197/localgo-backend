#!/bin/bash
set -e

# Run migrations (careful with this in production, but convenient for simple apps)
echo "Running migrations..."
php artisan migrate --force

# Cache configuration
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start the server
# Use PORT env var if available (Render/Railway), default to 8000
PORT=${PORT:-8000}
echo "Starting server on port $PORT..."
php artisan serve --host=0.0.0.0 --port=$PORT
