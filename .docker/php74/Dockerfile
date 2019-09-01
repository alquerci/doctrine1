FROM php:7.4-rc-cli

RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-upgrade --no-install-recommends \
        libonig-dev \
    ; \
    \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/*; \
    \
    docker-php-ext-install mbstring
