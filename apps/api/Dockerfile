FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    $PHPIZE_DEPS git unzip libpq-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Source bind-mounted at runtime; copy only scaffolds the image
COPY . /var/www/html

EXPOSE 8000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
