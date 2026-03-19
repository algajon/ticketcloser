#!/usr/bin/env bash
# exit on error
set -o errexit

echo "Running composer install..."
composer install --no-dev --optimize-autoloader

echo "Caching configuration and routes..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Building frontend assets..."
npm install
npm run build

echo "Running migrations..."
php artisan migrate --force
