FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    git \
    unzip \
    icu-dev \
    libzip-dev \
    freetype-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libxml2-dev \
    oniguruma-dev \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        intl \
        zip \
        gd \
        opcache \
        mbstring \
        xml

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-gose.ini

WORKDIR /var/www/html

COPY composer.json composer.lock* ./

# --no-scripts : le projet n'est pas encore copié en entier à ce stade
RUN if [ -f composer.json ]; then composer install --no-interaction --no-scripts --no-progress --prefer-dist || true; fi

COPY . .

RUN composer install --no-interaction --optimize-autoloader --no-progress || true

RUN chown -R www-data:www-data /var/www/html/var 2>/dev/null || true

EXPOSE 9000
CMD ["php-fpm"]
