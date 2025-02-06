#!/bin/sh

set -e

while [ ! $(pg_isready) ]; do
  echo "Waiting for database connection to start application"

  sleep 3
done

exec php-fpm
