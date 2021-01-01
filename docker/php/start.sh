#!/usr/bin/env bash

set -e

env=${APP_ENV:-prod}

if [ "$env" != "dev" ]; then
    echo "Caching configuration\n"

    (cd /var/www/html &&
      php bin/console cache:clear
      php bin/console cache:warmup
      php bin/console assets:install
      )
    echo "Removing XDebug\n"
    rm -rf /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
else
  rm -rf /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
fi

exec apache2-foreground
