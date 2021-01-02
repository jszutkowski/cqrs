FROM php:7.4-buster

ENV SERVER_NAME='app.dev'

RUN apt-get -y update \
    && apt-get -y --no-install-recommends install \
    nano \
    mc \
    git \
    zip \
    unzip \
    software-properties-common \
    wget \
    bash \
    openssl \
    curl \
    libtool \
    make \
    g++ \
    autoconf \
    perl \
    libmcrypt-dev \
    libpng-dev \
    sqlite3 \
    librabbitmq-dev

RUN docker-php-ext-install pdo_mysql

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && mv composer.phar /usr/bin/composer \
    && php -r "unlink('composer-setup.php');"

RUN pecl install xdebug-2.9.8 \
    && docker-php-ext-enable xdebug

RUN pecl install amqp \
    && docker-php-ext-enable amqp

COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

EXPOSE 80
EXPOSE 9000

WORKDIR /app
