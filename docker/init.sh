#!/usr/bin/env sh

set -e

# Run init scripts
for file in $(find "/srv/scripts/init" -type f | sort -t '-' -k1,1n)
do
  echo "Execute init script: $file"

  sh "$file"
done

# Start Supervisor
exec supervisord
