#/bin/sh

composer dump-autoload \
    --no-interaction \
    --no-ansi \
    --classmap-authoritative
composer run-script auto-scripts

caddy run --config /etc/caddy/Caddyfile &
php-fpm &

wait -n
exit $?
