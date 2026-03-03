FROM php:8.2-cli-bookworm AS builder

WORKDIR /app
RUN apt-get update && apt-get install -y git unzip wget libzip-dev
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader
COPY . .
RUN php -d phar.readonly=0 scripts/build_phar.php

FROM php:8.2-cli-bookworm
WORKDIR /app
COPY --from=builder /app/build/cdd-php /usr/local/bin/cdd-php
RUN chmod +x /usr/local/bin/cdd-php
EXPOSE 8082
ENTRYPOINT ["php", "/usr/local/bin/cdd-php", "serve_json_rpc", "--port", "8082", "--listen", "0.0.0.0"]
