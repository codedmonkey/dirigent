FROM composer:2 AS composer_build

WORKDIR /srv/app

COPY composer.json composer.lock ./

RUN composer install \
        --no-ansi \
        --no-autoloader \
        --no-interaction \
        --no-plugins \
        --no-progress \
        --no-scripts \
        --prefer-dist

FROM node:latest AS node_build

WORKDIR /srv/app

COPY package.json package-lock.json tsconfig.json webpack.config.js ./
COPY assets assets/

RUN set -e; \
    npm install; \
    npm run production;

FROM alpine:3.19

LABEL org.opencontainers.image.source=https://github.com/codedmonkey/conductor
LABEL org.opencontainers.image.description="Conductor PHP Package Registry"
LABEL org.opencontainers.image.licenses=FSL-1.1-MIT

ARG UID=1000
ARG GID=1000

RUN set -e; \
    addgroup -g $GID -S conductor; \
    adduser -u $UID -S -G conductor conductor; \
    apk upgrade --no-cache; \
    apk add --no-cache --upgrade \
        caddy \
        curl \
        git \
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
        postgresql \
        supervisor; \
    ln -s /usr/sbin/php-fpm82 /usr/sbin/php-fpm; \
    mkdir -p /run/postgresql /srv/config; \
    chown -R conductor:conductor /run /srv;

COPY --from=composer_build /usr/bin/composer /usr/bin/composer

COPY docker/init.sh /
COPY docker/Caddyfile /etc/caddy/
COPY docker/php.ini /etc/php82/conf.d/
COPY docker/php-fpm.conf /etc/php82/
COPY docker/supervisord.conf /etc/
COPY docker/process /srv/process/

USER conductor

ENV APP_ENV="prod"
ENV DATABASE_URL="postgresql://conductor@127.0.0.1:5432/conductor?serverVersion=16&charset=utf8"
ENV CONDUCTOR_IMAGE=1

WORKDIR /srv/app

COPY --chown=conductor:conductor --from=composer_build /srv/app ./
COPY --chown=conductor:conductor --from=node_build /srv/app/public/build public/build/
COPY --chown=conductor:conductor readme.md license.md ./
COPY --chown=conductor:conductor .env.conductor ./
COPY --chown=conductor:conductor bin bin/
COPY --chown=conductor:conductor config config/
COPY --chown=conductor:conductor migrations migrations/
COPY --chown=conductor:conductor public public/
COPY --chown=conductor:conductor src src/
COPY --chown=conductor:conductor translations translations/
COPY --chown=conductor:conductor templates templates/

COPY docker/conductor.yaml /srv/app/config/packages/

RUN set -e; \
    chmod +x bin/console; \
    composer dump-autoload --classmap-authoritative --no-ansi --no-interaction

VOLUME /srv/data

EXPOSE 7015

CMD sh /init.sh
