ARG PHP_IMAGE=php:8.3-cli

FROM ${PHP_IMAGE}

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
    && docker-php-ext-install zip \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/am-driver
