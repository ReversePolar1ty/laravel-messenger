# Используем официальный образ PHP 8.4 FPM
FROM php:8.4-fpm

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev

# Устанавливаем расширения PHP
RUN docker-php-ext-install pdo_mysql bcmath gd zip

#Установка Node.JS
RUN apt-get update && apt-get install -y nodejs npm

# Устанавливаем расширение MongoDB через PECL
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
