# syntax=docker/dockerfile:1.3

ARG PHP_VERSION=8.4
ARG XDEBUG_VERSION="-3.4.2"

FROM php:${PHP_VERSION}-cli as dev

ARG DEBIAN_FRONTEND=noninteractivef
ARG XDEBUG_VERSION
ENV COMPOSER_FLAGS="--prefer-dist --no-interaction"
ENV COMPOSER_ALLOW_SUPERUSER 1

COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/

RUN apt update -q \
 && apt install -y --no-install-recommends git zip unzip libzip4 libzip-dev zlib1g-dev \
 && docker-php-ext-install zip \
 && apt-get remove --autoremove -y libzip-dev zlib1g-dev \
 && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer

RUN pecl install xdebug${XDEBUG_VERSION} \
 && docker-php-ext-enable xdebug

WORKDIR /code

COPY composer.* /code/
RUN composer install $COMPOSER_FLAGS --no-scripts

COPY . /code/

