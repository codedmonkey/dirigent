#!/usr/bin/env sh

set -e

if [ -d "/srv/data/postgresql" ]; then
  echo "Database directory found"

  # Start Postgres server
  pg_ctl start -D /srv/data/postgresql

  exit 0
fi

echo "Creating PostgreSQL database..."

# Create database directory
mkdir -p /srv/data/postgresql

# Initialize database storage
initdb /srv/data/postgresql

# Start Postgres server
pg_ctl start -D /srv/data/postgresql

# Create database
createdb dirigent

echo "Created PostgreSQL database"
