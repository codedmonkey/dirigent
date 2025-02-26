#!/usr/bin/env sh

set -e

bin/console doctrine:migrations:sync-metadata-storage --no-ansi --no-interaction
bin/console doctrine:migrations:migrate --allow-no-migration --no-ansi --no-interaction
