#!/usr/bin/env bash
set -e
BRANCH=${BRANCH:-main}
REMOTE=${REMOTE:-origin}

# Fix Git safe.directory for current path to avoid 'dubious ownership' errors
if ! git config --global --get-all safe.directory | grep -q "$(pwd)"; then
  git config --global --add safe.directory "$(pwd)"
fi

git fetch --all
git reset --hard "$REMOTE/$BRANCH"
composer install --no-dev --prefer-dist --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart || true
