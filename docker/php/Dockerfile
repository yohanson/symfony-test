FROM php:8.4-fpm

RUN apt-get update && \
	apt-get install -y --no-install-recommends \
    libzip-dev \
    libpng-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql zip gd
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
