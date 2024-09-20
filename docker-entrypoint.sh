#!/bin/bash
set -e

# Generate application key if not set
php artisan key:generate --no-interaction --force

# Run storage link command
php artisan storage:link

# Set correct permissions
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Execute CMD
exec "$@"