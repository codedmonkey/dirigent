#!/usr/bin/env sh

set -e

# If the first argument is `-init`, run the application. This is
# also the default command.
if [ "$1" = "-init" ]; then
  set -- /srv/init.sh
else
  # If the first argument is `--`, execute the remaining arguments as a
  # new command, otherwise pass the arguments to the Dirigent binary.
  if [ "$1" = "--" ]; then
    set -- ${@:2}
  else
    set -- bin/dirigent "$@"
  fi
fi

exec "$@"
