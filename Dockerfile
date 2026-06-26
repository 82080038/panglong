FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    mysql-client mysql-dev \
    sqlite-dev \
    libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql pdo_sqlite mysqli gd zip mbstring bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod 666 /var/www/html/database/database.sqlite \
    && chmod 777 /var/www/html/database

EXPOSE 9000

CMD ["php-fpm"]
