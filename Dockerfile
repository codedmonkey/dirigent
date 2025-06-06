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

FROM node:23 AS node_build

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

COPY docker/entrypoint.sh docker/init.sh /srv/

RUN set -e; \
    addgroup -g $GID -S dirigent; \
    adduser -u $UID -S -G dirigent dirigent; \
    apk upgrade --no-cache; \
    apk add --no-cache --upgrade \
        caddy \
        curl \
        git \
        openssl \
        php83 \
        php83-ctype \
        php83-curl \
        php83-dom \
        php83-fileinfo \
        php83-fpm \
        php83-iconv \
        php83-intl \
        php83-mbstring \
        php83-openssl \
        php83-pdo \
        php83-pdo_pgsql \
        php83-phar \
        php83-session \
        php83-simplexml \
        php83-sodium \
        php83-tokenizer \
        php83-xml \
        postgresql \
        supervisor; \
    ln -s /usr/bin/php83 /usr/bin/php; \
    ln -s /usr/sbin/php-fpm83 /usr/sbin/php-fpm; \
    mkdir -p /run/postgresql /srv/config /srv/data; \
    chown -R dirigent:dirigent /run /srv; \
    chmod +x /srv/entrypoint.sh /srv/init.sh;

COPY --from=composer_build /usr/bin/composer /usr/bin/composer

COPY docker/Caddyfile /etc/caddy/
COPY docker/php.ini /etc/php83/conf.d/
COPY docker/php-fpm.conf /etc/php83/
COPY docker/supervisord.conf /etc/
COPY docker/process /srv/process/
COPY docker/scripts /srv/scripts/

USER dirigent

WORKDIR /srv/app

COPY --chown=$UID:$GID --from=composer_build /srv/app ./
COPY --chown=$UID:$GID --from=node_build /srv/app/public/build public/build/
COPY --chown=$UID:$GID README.md LICENSE.md CHANGELOG.md ./
COPY --chown=$UID:$GID bin/console bin/dirigent bin/
COPY --chown=$UID:$GID docker/config.yaml config/dirigent.yaml
COPY --chown=$UID:$GID docker/env.php ./.env.dirigent.local.php
COPY --chown=$UID:$GID config config/
COPY --chown=$UID:$GID docs docs/
COPY --chown=$UID:$GID migrations migrations/
COPY --chown=$UID:$GID public public/
COPY --chown=$UID:$GID src src/
COPY --chown=$UID:$GID translations translations/
COPY --chown=$UID:$GID templates templates/

RUN set -e; \
    chmod +x bin/console; \
    chmod +x bin/dirigent; \
    composer dump-autoload --classmap-authoritative --no-ansi --no-interaction;

VOLUME /srv/config
VOLUME /srv/data

EXPOSE 7015

ENTRYPOINT ["/srv/entrypoint.sh"]
CMD ["-init"]
