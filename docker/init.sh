#!/bin/sh

set -e

sh /srv/scripts/update-conductor-database.sh

exec supervisord
