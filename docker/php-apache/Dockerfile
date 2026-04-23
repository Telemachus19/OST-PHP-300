FROM php:8.2-apache

# Install common XAMPP-like PHP extensions.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       libpng-dev \
       libjpeg62-turbo-dev \
       libfreetype6-dev \
       libzip-dev \
       unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" mysqli pdo pdo_mysql gd zip \
    && a2enmod rewrite \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
