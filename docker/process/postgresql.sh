#/bin/sh

set -e

function shutdown()
{
    pkill postgres
}

if [ ! -d "/srv/data/postgresql" ]; then
  mkdir -p /srv/data/postgresql
  initdb /srv/data/postgresql
fi

trap shutdown HUP INT QUIT ABRT KILL ALRM TERM TSTP

exec postgres -D /srv/data/postgresql
