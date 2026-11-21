# PHP 8.5 runtime for the KeyChain sandbox (dev/up). Craft 6 needs PHP 8.5, which most machines don't have yet.
FROM php:8.5-cli
RUN apt-get update && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev libsqlite3-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath intl zip pdo_sqlite gd \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN echo 'memory_limit=1G' > /usr/local/etc/php/conf.d/memory.ini
WORKDIR /app
