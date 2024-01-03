#/bin/sh

set -eux

composer run-script --no-ansi --no-interaction auto-scripts

bin/console doctrine:database:create --no-ansi --no-interaction
bin/console doctrine:schema:update --complete --force --no-ansi --no-interaction
bin/console messenger:setup-transports --no-ansi --no-interaction

caddy run --config /etc/caddy/Caddyfile &
php-fpm &

wait -n
exit $?
