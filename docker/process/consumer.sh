#!/bin/sh

set -e

while [ ! "$(netstat -an | grep :9000)" ]; do
  echo "Waiting for application before starting working"

  sleep 5
done

function shutdown() {
    bin/console messenger:stop-workers
}

trap shutdown HUP INT QUIT ABRT KILL ALRM TERM TSTP

exec bin/console messenger:consume async scheduler_packages --sleep 10
