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
    libssl-dev \
    libicu-dev

# Устанавливаем расширения PHP
RUN docker-php-ext-install pdo_mysql bcmath gd zip pcntl intl

#Установка Node.JS
RUN apt-get update && apt-get install -y nodejs npm

# Устанавливаем расширение MongoDB через PECL
RUN pecl install mongodb redis && docker-php-ext-enable mongodb redis

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
