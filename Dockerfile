FROM composer:2 AS build

WORKDIR /srv

COPY composer.json composer.lock ./

RUN set -eux; \
    composer install \
        --no-ansi \
        --no-autoloader \
        --no-interaction \
        --no-plugins \
        --no-progress \
        --no-scripts \
        --no-suggest \
        --prefer-dist;

FROM alpine:3.19

LABEL org.opencontainers.image.source=https://github.com/codedmonkey/conductor
LABEL org.opencontainers.image.description="Conductor PHP Package Registry"
LABEL org.opencontainers.image.licenses=MIT

ARG UID=1000
ARG GID=1000

RUN set -eux; \
    addgroup -g $GID -S conductor; \
    adduser -u $UID -S -G conductor conductor;

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
        php82-intl \
        php82-mbstring \
        php82-openssl \
        php82-pdo \
        php82-pdo_pgsql \
        php82-phar \
        php82-session \
        php82-simplexml \
        php82-tokenizer \
        php82-xml \
        postgresql; \
    ln -s /usr/sbin/php-fpm82 /usr/sbin/php-fpm; \
    mkdir -p /run/postgresql /var/lib/postgresql/data; \
    chown -R conductor:conductor /run /srv /var/lib/postgresql;

COPY --from=build /usr/bin/composer /usr/bin/composer

COPY docker/Caddyfile /etc/caddy/
COPY docker/php-fpm.conf /etc/php82/
COPY docker/init.sh /

USER conductor

RUN set -eux; \
    initdb /var/lib/postgresql/data;

VOLUME /var/lib/postgresql/data

ENV APP_ENV="prod"

WORKDIR /srv

COPY --chown=conductor:conductor --from=build /srv ./
COPY --chown=conductor:conductor .env importmap.php readme.md license.md ./
COPY --chown=conductor:conductor assets assets/
COPY --chown=conductor:conductor bin bin/
COPY --chown=conductor:conductor config config/
COPY --chown=conductor:conductor migrations migrations/
COPY --chown=conductor:conductor public public/
COPY --chown=conductor:conductor src src/
COPY --chown=conductor:conductor templates templates/

RUN set -eux; \
    chmod +x bin/console; \
    composer dump-autoload --classmap-authoritative --no-ansi --no-interaction; \
    mkdir -p storage;

EXPOSE 7015

CMD sh /init.sh
