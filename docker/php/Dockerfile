FROM php:7.4-buster

ENV SERVER_NAME='app.dev' \
    PHP_XDEBUG_DEFAULT_ENABLE=0 \
    PHP_XDEBUG_REMOTE_ENABLE=0 \
    PHP_XDEBUG_REMOTE_AUTOSTART=0 \
    PHP_XDEBUG_REMOTE_HOST='' \
    PHP_XDEBUG_REMOTE_PORT=9001 \
    PHP_XDEBUG_IDE_KEY='docker' \
    PHP_XDEBUG_PROFILER_ENABLE=0 \
    PHP_XDEBUG_PROFILER_OUTPUT_DIR='' \
    PHP_XDEBUG_MAX_NESTING_LEVEL=700

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
#    shadow \
    openssl \
#    icu \
#    icu-dev \
    curl \
    libtool \
#    imagemagick-dev \
    make \
    g++ \
    autoconf \
    perl \
#    rabbitmq-c-dev \
#    freetype-dev \
#    libjpeg-turbo-dev \
    libmcrypt-dev \
    libpng-dev \
    sqlite3
#    pcre-dev

RUN docker-php-ext-install pdo_mysql
#RUN docker-php-ext-enable opcache

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && mv composer.phar /usr/bin/composer \
    && php -r "unlink('composer-setup.php');"

RUN pecl install xdebug-3.0.1 \
    && docker-php-ext-enable xdebug

COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
#COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
#COPY docker/php/start.sh /usr/local/bin/start
#COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

#RUN chown -R www-data:www-data /var/www/html
#RUN chmod +x /usr/local/bin/start

#RUN a2enmod rewrite \
#    && a2ensite 000-default.conf

#CMD ["/usr/local/bin/start"]

WORKDIR /app
