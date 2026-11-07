FROM php:7.4-fpm

ARG UID=1000
ARG GID=1000

RUN sed -i 's|deb.debian.org|archive.debian.org|g' /etc/apt/sources.list \
    && apt-get -o Acquire::Check-Valid-Until=false update \
    && apt-get install -y --no-install-recommends \
        unzip libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install -j2 bcmath mbstring pdo_mysql xml zip \
    && groupmod --gid "${GID}" www-data \
    && usermod --uid "${UID}" --gid "${GID}" www-data \
    && rm -rf /var/lib/apt/lists/*

# Laravel 7.4's package discovery expects Composer 1's installed.json format.
COPY --from=composer:1.10 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

USER www-data
