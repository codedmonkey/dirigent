#!/usr/bin/env sh

set -e

# Stop Postgres server
pg_ctl stop -D /srv/data/postgresql
