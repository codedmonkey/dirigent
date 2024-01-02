FROM composer:2 AS build

WORKDIR /srv

COPY composer.json composer.lock ./

RUN set -eux; \
    composer install \
        --no-interaction \
        --no-ansi \
        --no-scripts \
        --no-plugins \
        --prefer-dist \
        --no-progress \
        --no-suggest \
        --no-autoloader;

FROM alpine:3.19

RUN set -eux; \
    apk upgrade --no-cache; \
    apk add --no-cache --upgrade \
        caddy \
        curl \
        php82 \
        php82-ctype \
        php82-curl \
        php82-dom \
        php82-fileinfo \
        php82-fpm \
        php82-iconv \
        php82-mbstring \
        php82-openssl \
        php82-phar \
        php82-session \
        php82-simplexml \
        php82-tokenizer \
        php82-xml; \
    ln -s /usr/sbin/php-fpm82 /usr/sbin/php-fpm;

COPY --from=build /usr/bin/composer /usr/bin/composer

COPY docker/Caddyfile /etc/caddy/
COPY docker/php-fpm.conf /etc/php82/
COPY docker/run-script.sh /run-conductor.sh

RUN addgroup -S conductor; adduser -S -G conductor conductor;

WORKDIR /srv

COPY --from=build /srv ./
COPY .env importmap.php ./
COPY assets assets/
COPY bin bin/
COPY config config/
COPY migrations migrations/
COPY public public/
COPY src src/
COPY templates templates/

RUN mkdir -p storage; chown conductor:conductor storage;

ENV APP_ENV="prod"

EXPOSE 7015

CMD sh /run-conductor.sh
