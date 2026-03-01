FROM debian:latest AS builder

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    php-cli \
    php-curl \
    php-mbstring \
    php-xml \
    php-zip \
    make \
    curl \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
COPY . /app

RUN make install_deps && php -d phar.readonly=0 scripts/build_phar.php build/cdd-php

FROM debian:latest

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    php-cli \
    php-curl \
    php-mbstring \
    php-xml \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY --from=builder /app/build/cdd-php /app/cdd-php

ENTRYPOINT ["php", "/app/cdd-php", "serve_json_rpc"]
CMD ["--port", "8082", "--listen", "0.0.0.0"]