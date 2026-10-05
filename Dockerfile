# TimeCapsule (pure PHP, functional style) - Apache + mod_php in one container.

# Stage 1: install the Composer packages (vendor/) with the official Composer image.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader

# Stage 2: the web server image.
FROM php:8.3-apache

# PostgreSQL driver for PDO (libpq-dev provides the client library and headers).
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Apache: serve only the public/ folder and send unknown URLs to public/index.php.
RUN a2enmod rewrite
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# PHP settings: upload limits (the app itself allows MAX_UPLOAD_MB = 5).
COPY docker/php.ini /usr/local/etc/php/conf.d/timecapsule.ini

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor/ vendor/
RUN chmod +x docker/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["apache2-foreground"]
