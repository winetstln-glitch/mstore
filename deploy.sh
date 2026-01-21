#!/bin/bash

# MStore Deployment Script
# Usage: ./deploy.sh

echo "Starting deployment..."

# 1. Pull latest changes
echo "Pulling latest changes from git..."
git pull origin main

# 2. Install Dependencies
echo "Installing PHP dependencies..."
composer install --optimize-autoloader --no-dev

echo "Installing Node dependencies..."
npm install
echo "Building assets..."
npm run build

# 3. Database Migration
echo "Running database migrations..."
php artisan migrate --force

# 4. Clear and Cache Config
echo "Clearing and caching configuration..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart Queue (if applicable)
# echo "Restarting queue..."
# php artisan queue:restart

echo "Deployment completed successfully!"
