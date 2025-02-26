#!/usr/bin/env sh

set -e

# If the first argument is `-init`, execute supervisor to run the
# application, which is the default command, or pass the argument
# to the Dirigent binary
if [ "$1" = "-init" ]; then
  set -- /srv/init.sh
else
  set -- bin/dirigent "$@"
fi

exec "$@"
