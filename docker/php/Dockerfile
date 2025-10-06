FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    bash git curl libpng libpng-dev libjpeg-turbo-dev libwebp-dev \
    libzip-dev oniguruma-dev icu-dev freetype-dev \
    libxml2-dev $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) gd intl pdo pdo_mysql zip bcmath opcache pcntl \
 && pecl install redis \
 && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN { \
  echo 'opcache.enable=1'; \
  echo 'opcache.enable_cli=1'; \
  echo 'opcache.validate_timestamps=0'; \
  echo 'opcache.jit=1255'; \
  echo 'opcache.jit_buffer_size=128M'; \
} > /usr/local/etc/php/conf.d/opcache.ini

RUN addgroup -g 1000 wwwgrp && adduser -D -u 1000 -G wwwgrp www-data || true

CMD ["php-fpm"]
