#/bin/sh

set -eux

composer run-script --no-ansi --no-interaction auto-scripts

pg_ctl start -D /var/lib/postgresql/data &

# Temporary wait for database todo
while ! nc -z localhost 5432; do sleep 1; done;

bin/console doctrine:database:create --if-not-exists --no-ansi --no-interaction
bin/console doctrine:schema:update --complete --force --no-ansi --no-interaction
bin/console messenger:setup-transports --no-ansi --no-interaction

caddy run --config /etc/caddy/Caddyfile &
php-fpm &

wait -n
exit $?
