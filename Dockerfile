FROM composer:2 AS composer_build

WORKDIR /srv/app

COPY composer.json composer.lock ./

RUN composer install \
        --ignore-platform-reqs \
        --no-ansi \
        --no-autoloader \
        --no-dev \
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

LABEL org.opencontainers.image.source=https://github.com/codedmonkey/dirigent
LABEL org.opencontainers.image.description="Dirigent PHP Package Registry"
LABEL org.opencontainers.image.licenses=FSL-1.1-MIT

ARG UID=1000
ARG GID=1000

RUN set -e; \
    addgroup -g $GID -S dirigent; \
    adduser -u $UID -S -G dirigent dirigent; \
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
    mkdir -p /run/postgresql /srv/config /srv/data; \
    chown -R dirigent:dirigent /run /srv;

COPY --from=composer_build /usr/bin/composer /usr/bin/composer

COPY docker/init.sh /
COPY docker/Caddyfile /etc/caddy/
COPY docker/php.ini /etc/php82/conf.d/
COPY docker/php-fpm.conf /etc/php82/
COPY docker/supervisord.conf /etc/
COPY docker/process /srv/process/

USER dirigent

ENV APP_ENV="prod"
ENV DATABASE_URL="postgresql://dirigent@127.0.0.1:5432/dirigent?serverVersion=16&charset=utf8"
ENV DIRIGENT_IMAGE=1

WORKDIR /srv/app

COPY --chown=dirigent:dirigent --from=composer_build /srv/app ./
COPY --chown=dirigent:dirigent --from=node_build /srv/app/public/build public/build/
COPY --chown=dirigent:dirigent readme.md license.md ./
COPY --chown=dirigent:dirigent .env.dirigent ./
COPY --chown=dirigent:dirigent bin bin/
COPY --chown=dirigent:dirigent config config/
COPY --chown=dirigent:dirigent migrations migrations/
COPY --chown=dirigent:dirigent public public/
COPY --chown=dirigent:dirigent src src/
COPY --chown=dirigent:dirigent translations translations/
COPY --chown=dirigent:dirigent templates templates/

RUN set -e; \
    chmod +x bin/console; \
    chmod +x bin/dirigent; \
    composer dump-autoload --classmap-authoritative --no-ansi --no-interaction

VOLUME /srv/data

EXPOSE 7015

CMD ["sh", "/init.sh"]
