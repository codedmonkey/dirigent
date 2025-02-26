#!/usr/bin/env sh

set -e

while [ ! $(pg_isready) ]; do
  echo "Application is waiting for the database"

  sleep 3
done

exec php-fpm
