FROM php:8.4-apache
# Refresh inherited curl packages as well as installing extension dependencies;
# the PHP base image can lag behind Debian security updates.
RUN apt-get update && apt-get install -y --no-install-recommends curl libcurl4t64 libpng-dev libjpeg62-turbo-dev libwebp-dev unzip && docker-php-ext-configure gd --with-jpeg --with-webp && docker-php-ext-install pdo_mysql gd && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --optimize-autoloader
COPY . /app
RUN mkdir -p /app/public/assets && cp -R /app/assets/. /app/public/assets/ && mkdir -p /app/storage/media && chown -R www-data:www-data /app/storage && a2enmod rewrite headers && cp /app/config/docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY config/docker/php.ini /usr/local/etc/php/conf.d/production.ini
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s CMD curl -fsS http://localhost/KrishiDisha/health.php || exit 1
