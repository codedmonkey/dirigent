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
    chown -R dirigent:dirigent /run /srv; \
    chmod +x /srv/entrypoint.sh /srv/init.sh;

COPY --from=composer_build /usr/bin/composer /usr/bin/composer

COPY docker/Caddyfile /etc/caddy/
COPY docker/php.ini /etc/php82/conf.d/
COPY docker/php-fpm.conf /etc/php82/
COPY docker/supervisord.conf /etc/
COPY docker/process /srv/process/
COPY docker/scripts /srv/scripts/

USER dirigent

ENV APP_ENV="prod"
ENV DATABASE_URL="postgresql://dirigent@127.0.0.1:5432/dirigent?serverVersion=16&charset=utf8"
ENV DIRIGENT_IMAGE=1

WORKDIR /srv/app

COPY --chown=$UID:$GID --from=composer_build /srv/app ./
COPY --chown=$UID:$GID --from=node_build /srv/app/public/build public/build/
COPY --chown=$UID:$GID readme.md license.md ./
COPY --chown=$UID:$GID .env.dirigent ./
COPY --chown=$UID:$GID bin/console bin/dirigent bin/
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

VOLUME /srv/data

EXPOSE 7015

ENTRYPOINT ["/srv/entrypoint.sh"]
CMD ["-init"]
