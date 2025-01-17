#!/bin/sh

set -e

if [ ! -d "/srv/data/postgresql" ]; then
  mkdir -p /srv/data/postgresql
  initdb /srv/data/postgresql
fi

function shutdown()
{
    pkill postgres
}

trap shutdown HUP INT QUIT ABRT KILL ALRM TERM TSTP

exec postgres -D /srv/data/postgresql
