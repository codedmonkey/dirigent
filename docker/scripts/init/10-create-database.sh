#!/bin/sh

set -e

if [ -d "/srv/data/postgresql" ]; then
  echo "Database directory found"

  exit 0
fi

echo "Creating PostgreSQL database..."

# Create database directory
mkdir -p /srv/data/postgresql

# Initialize database storage
initdb /srv/data/postgresql

# Start server
pg_ctl start -D /srv/data/postgresql

# Create database
createdb dirigent

# Stop server
pg_ctl stop -D /srv/data/postgresql

echo "Created PostgreSQL database"
