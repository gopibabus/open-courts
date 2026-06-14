FROM node:lts AS nodebuild
FROM composer:lts AS composerbuild
FROM php:8.4-apache

LABEL Maintainer="Gopibabu Srungavarapu <s.gopibabu@gmail.com>" \
      Description="Open-Tennis: Laravel 12 + React (Inertia) multi-tenant application"

###############################################################################
# environment
###############################################################################
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_PROCESS_TIMEOUT=2000

###############################################################################
# system packages + PHP extensions
#   pdo_mysql/mysqli : MySQL connectivity
#   intl/mbstring    : Laravel framework requirements
#   zip              : composer package extraction
#   gd               : image handling (court/club photos, later)
#   bcmath           : precise numeric work (scoring, money)
#   pcntl            : queue workers / scheduler
#   opcache          : production performance
###############################################################################
RUN apt-get -yqq update \
    && apt-get -y install \
        zip unzip \
        supervisor \
        build-essential \
        libzip-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libpng-dev \
        libwebp-dev \
        curl \
        git \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        pcntl \
        intl \
        zip \
        gd \
        bcmath \
        opcache \
    && a2enmod rewrite expires headers \
    && rm -rf /var/lib/apt/lists/*

###############################################################################
# configuration
###############################################################################
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/config/php/php.ini /usr/local/etc/php/php.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf

COPY docker/start.sh /usr/local/bin/start
RUN chmod +x /usr/local/bin/start

###############################################################################
# node + composer binaries (from the build stages above)
###############################################################################
COPY --from=nodebuild /usr/local /usr/local
COPY --from=composerbuild /usr/bin/composer /usr/bin/composer

###############################################################################
# application
###############################################################################
WORKDIR /var/www/html
COPY . /var/www/html

# Bake deps + assets into the image (used when running WITHOUT a bind mount).
# In the dev compose setup the bind mount supersedes these; start.sh rebuilds
# anything missing on first boot.
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN npm ci && npm run build && rm -rf node_modules

RUN chown -R www-data:www-data /var/www/html \
    && chgrp -R www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

###############################################################################
# mod_php requires the prefork MPM
###############################################################################
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true
RUN a2enmod mpm_prefork

EXPOSE 80

CMD ["/usr/local/bin/start"]
