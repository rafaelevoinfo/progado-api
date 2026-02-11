FROM php:8-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       build-essential \
       pkg-config \
       zlib1g-dev \
       libzip-dev \
       zip \
       unzip \
    && docker-php-ext-install -j"$(nproc)" zip mysqli pdo pdo_mysql \
    && apt-get purge -y --auto-remove build-essential pkg-config \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY api/ /var/www/html/

WORKDIR /var/www/html

EXPOSE 80
