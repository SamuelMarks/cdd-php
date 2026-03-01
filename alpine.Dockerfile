FROM alpine:latest AS builder

RUN apk update && apk add --no-cache \ 
    php \
    php-ctype \
    php-phar \
    php-json \
    php-openssl \
    php-curl \
    php-mbstring \
    php-tokenizer \
    php-dom \
    php-xml \
    php-xmlwriter \
    php-simplexml \
    make \
    curl \
    git \
    bash \
    unzip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
COPY . /app

RUN make install_deps && php -d phar.readonly=0 scripts/build_phar.php build/cdd-php

FROM alpine:latest

RUN apk update && apk add --no-cache \ 
    php \
    php-json \
    php-phar \
    php-openssl \
    php-curl \
    php-mbstring \
    php-ctype \
    php-tokenizer \
    php-dom \
    php-xml

WORKDIR /app
COPY --from=builder /app/build/cdd-php /app/cdd-php

ENTRYPOINT ["php", "/app/cdd-php", "serve_json_rpc"]
CMD ["--port", "8082", "--listen", "0.0.0.0"]