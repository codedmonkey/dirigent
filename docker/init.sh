#!/bin/sh

set -e

# Run init scripts
for file in $(find "/srv/scripts/init" -type f | sort -t '-' -k1,1n)
do
    echo "Execute init script: $file"

    sh "$file"
done

echo "EOS"
exit 0

# Start Supervisor
exec supervisord
