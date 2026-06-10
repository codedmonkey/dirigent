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

FROM alpine:3.23

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
        php85 \
        php85-ctype \
        php85-curl \
        php85-dom \
        php85-fileinfo \
        php85-fpm \
        php85-gd \
        php85-iconv \
        php85-intl \
        php85-mbstring \
        php85-openssl \
        php85-pdo \
        php85-pdo_pgsql \
        php85-phar \
        php85-session \
        php85-simplexml \
        php85-sodium \
        php85-tokenizer \
        php85-xml \
        postgresql16 \
        supervisor; \
    ln -s /usr/bin/php85 /usr/bin/php; \
    ln -s /usr/sbin/php-fpm85 /usr/sbin/php-fpm; \
    mkdir -p /run/postgresql /srv/config /srv/data; \
    chown -R dirigent:dirigent /run /srv; \
    chmod +x /srv/entrypoint.sh /srv/init.sh;

COPY --from=composer_build /usr/bin/composer /usr/bin/composer

COPY docker/Caddyfile /etc/caddy/
COPY docker/php.ini /etc/php85/conf.d/
COPY docker/php-fpm.conf /etc/php85/
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
