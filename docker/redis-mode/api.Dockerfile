FROM php:8.2-apache

ENV APP_MODE=redis
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
COPY php/conf.d/php.ini /usr/local/etc/php/conf.d/php-custom-config.ini

RUN apt-get update && \
    apt-get -y install libxml2-dev libonig-dev unzip && \
    rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install opcache intl
RUN pecl install redis && \
    docker-php-ext-enable redis
RUN a2enmod rewrite

COPY . /var/www/html

RUN /var/www/html/docker/redis-mode/install-composer.sh
RUN composer install --no-dev --no-scripts

ENTRYPOINT /var/www/html/docker/redis-mode/entrypoint.bash
