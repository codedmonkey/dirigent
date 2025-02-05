#!/usr/bin/env sh

set -e

if [ -f "/srv/config/secrets/kernel_secret" ]; then
  echo "Kernel secret exists"
fi

# Make sure secrets directory exists
mkdir -p /srv/config/secrets

# Generate a kernel secret and save the value
secret=$(openssl rand -base64 12)
echo $secret > /srv/config/secrets/kernel_secret

echo "Generated a new kernel secret"
