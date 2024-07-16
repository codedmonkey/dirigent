#/bin/sh

set -e

if [ ! -d "/srv/data/postgresql" ]; then
  mkdir -p /srv/data/postgresql
  initdb /srv/data/postgresql
fi

exec pg_ctl start -D /srv/data/postgresql
