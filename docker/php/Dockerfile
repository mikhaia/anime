FROM php:8.2-apache

# Базовые пакеты + php-расширения
RUN apt-get update && apt-get install -y \
    git zip unzip libzip-dev libonig-dev libpng-dev libfreetype6-dev libjpeg62-turbo-dev libwebp-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install pdo pdo_mysql zip gd \
 && a2enmod rewrite headers expires

# Composer (копируем бинарь из официального образа)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Настройки PHP
COPY php.ini /usr/local/etc/php/conf.d/php.ini

# Apache vhost (DocumentRoot → /public)
COPY vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
