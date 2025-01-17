#!/bin/sh

set -e

# Start server
pg_ctl start -D /srv/data/postgresql

# Run migrations
bin/console doctrine:migrations:migrate --allow-no-migration --no-ansi --no-interaction

# Stop server
pg_ctl stop -D /srv/data/postgresql

echo "Created PostgreSQL database"
