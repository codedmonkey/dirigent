#/bin/sh

set -e

composer run-script --no-ansi --no-interaction auto-scripts

# todo temporary timeout for database connection
while ! nc -z localhost 5432; do
  echo "Waiting for database connection";
  sleep 3;
done;

bin/console doctrine:database:create --if-not-exists --no-ansi --no-interaction
bin/console doctrine:schema:update --complete --force --no-ansi --no-interaction
bin/console messenger:setup-transports --no-ansi --no-interaction

exec php-fpm
