#/bin/sh

set -e

function shutdown()
{
    pkill pg_ctl
}

if [ ! -d "/srv/data/postgresql" ]; then
  mkdir -p /srv/data/postgresql
  initdb /srv/data/postgresql
fi

trap shutdown HUP INT QUIT ABRT KILL ALRM TERM TSTP

exec pg_ctl start -D /srv/data/postgresql
