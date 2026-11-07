FROM php:8.5-fpm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libcurl4-openssl-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install -j2 bcmath curl mbstring pdo_mysql xml zip \
    && groupmod --gid "${GID}" www-data \
    && usermod --uid "${UID}" --gid "${GID}" www-data \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_HOME=/tmp/composer

WORKDIR /var/www/html

USER www-data
