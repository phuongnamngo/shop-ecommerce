#!/usr/bin/env sh
set -e
cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

# Compose injects env from root `.env`. If APP_KEY empty, generate for this process.
if [ -z "${APP_KEY}" ]; then
  export APP_KEY="$(php artisan key:generate --show --no-interaction)"
  echo "entrypoint: generated ephemeral APP_KEY (persist into root .env for stable sessions)"
fi

exec "$@"
