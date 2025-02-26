#!/usr/bin/env sh

set -e

function shutdown() {
  pkill postgres
}

trap shutdown HUP INT QUIT ABRT KILL ALRM TERM TSTP

exec postgres -D /srv/data/postgresql
