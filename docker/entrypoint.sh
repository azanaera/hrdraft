#!/bin/sh
set -e

# Migrations only — never seed automatically. DemoDataSeeder creates
# known-password fake accounts (see database/seeders/DemoDataSeeder.php)
# that must never exist in an environment real employees use. Create the
# first real admin with: php artisan hris:create-admin
php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

# If a command was passed (e.g. Render's cron service running
# `php artisan time-off:accrue`), run that instead of starting the web
# server — same image, two roles. See render.yaml.
if [ "$#" -gt 0 ]; then
  exec "$@"
fi

php-fpm -D
exec nginx -g 'daemon off;'
