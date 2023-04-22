FROM php:8.2-cli

ENV APP_MODE=redis
COPY php/conf.d/php.ini /usr/local/etc/php/conf.d/php-custom-config.ini

RUN apt-get update && \
    apt-get -y install libxml2-dev libonig-dev unzip && \
    rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install opcache intl pcntl
RUN pecl install redis && \
    docker-php-ext-enable redis

COPY . /app

RUN /app/docker/redis-mode/install-composer.sh

WORKDIR /app
RUN composer install --no-dev --no-scripts

ENTRYPOINT ["/app/bin/console", "messenger:consume", "async", "--memory-limit=256M", "-vv"]
