#!/bin/sh

set -e

while [ -z "$(netstat -an | grep :9000)" ]; do
  echo "Waiting for app";
  sleep 5;
done;

exec /srv/app/bin/console messenger:consume async scheduler_packages --sleep 10
