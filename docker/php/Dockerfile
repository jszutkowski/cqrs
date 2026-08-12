# syntax=docker/dockerfile:1

##
## base — PHP 8.5 with the extensions the application needs at runtime.
##
FROM php:8.5-cli AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        librabbitmq-dev \
        unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql \
    && pecl install amqp \
    && docker-php-ext-enable amqp

COPY docker/php/php.ini /usr/local/etc/php/php.ini

WORKDIR /app

##
## dev — adds Composer and PCOV (coverage); the code is volume-mounted.
##
FROM base AS dev

RUN pecl install pcov \
    && docker-php-ext-enable pcov

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN useradd --create-home --uid 1000 app
USER app

##
## prod — self-contained image with vendors and source, no dev tooling.
##
FROM base AS prod

ENV APP_ENV=prod

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-progress

COPY bin ./bin
COPY config ./config
COPY public ./public
COPY src ./src
COPY migrations ./migrations
COPY .env ./

RUN composer dump-autoload --optimize --classmap-authoritative

RUN useradd --create-home --uid 1000 app \
    && chown -R app:app /app/var || true
USER app

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
