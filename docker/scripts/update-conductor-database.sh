#!/bin/sh

# TODO remove in 0.4 release

set -e

echo "Updating database from Conductor structure to Dirigent structure..."

# Start PostgreSQL server
pg_ctl start -D /srv/data/postgresql

# Wait for PostgreSQL to be ready
until pg_isready; do
  echo "Waiting for PostgreSQL to be ready..."
  sleep 3
done

# Check and rename database if it exists
if psql -lqt | cut -d \| -f 1 | grep -qw conductor; then
  echo "Renaming PostgreSQL database..."

  psql -c "ALTER DATABASE conductor RENAME TO dirigent;"
fi

# Check and rename user if it exists
if psql -t -c "SELECT 1 FROM pg_roles WHERE rolname='conductor';" | grep -q 1; then
  echo "Renaming PostgreSQL user..."

  psql -c "ALTER USER conductor RENAME TO dirigent;"
fi

# Finish migration
pg_ctl stop -D /srv/data/postgresql

echo "Updating database from Conductor structure to Dirigent structure done"
