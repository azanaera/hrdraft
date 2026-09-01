# Production image for the pilot deployment — see docs/PILOT_READINESS.md
# and docs/DEPLOYMENT.md. Single container: nginx serves the built React app
# and proxies /api + /sanctum to php-fpm, same topology Vite's dev proxy uses
# locally (see apps/web/vite.config.ts) — one origin, no CORS complexity for
# Sanctum's SPA cookie auth.

# --- Stage 1: build the web app ---
FROM node:20-alpine AS webbuild
WORKDIR /app
COPY package.json package-lock.json ./
COPY apps/web/package.json apps/web/package.json
COPY apps/mobile/package.json apps/mobile/package.json
COPY packages/shared-types/package.json packages/shared-types/package.json
COPY packages/api-client/package.json packages/api-client/package.json
COPY packages/ui-tokens/package.json packages/ui-tokens/package.json
RUN npm ci
COPY apps/web apps/web
COPY packages packages
RUN npm run build:web

# --- Stage 2: PHP + nginx runtime ---
FROM php:8.2-fpm AS runtime

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx libpq-dev libzip-dev unzip git \
    && docker-php-ext-install pdo_pgsql pgsql mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY apps/api ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY --from=webbuild /app/apps/web/dist ./public

COPY docker/nginx.conf /etc/nginx/sites-enabled/default
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
