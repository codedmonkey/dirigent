#!/usr/bin/env sh

set -e

exec caddy run --config /etc/caddy/Caddyfile
