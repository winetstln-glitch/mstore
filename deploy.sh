#!/usr/bin/env bash
set -e
BRANCH=${BRANCH:-main}
REMOTE=${REMOTE:-origin}
git fetch --all
git reset --hard "$REMOTE/$BRANCH"
composer install --no-dev --prefer-dist --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan queue:restart || true
