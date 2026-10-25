FROM dunglas/frankenphp:1.9.1-php8.4-bookworm

RUN install-php-extensions pdo_mysql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts

COPY . .

RUN composer run-script post-autoload-dump --no-interaction \
    && mkdir -p storage/framework/cache storage/framework/sessions \
       storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY Caddyfile /etc/frankenphp/Caddyfile

EXPOSE 8000

CMD ["sh", "-c", "php artisan config:clear && php artisan migrate --force && frankenphp run --config /etc/frankenphp/Caddyfile"]

