#!/usr/bin/env sh

set -e

if [ ! -z "${KERNEL_SECRET}" ] || [ ! -z "${KERNEL_SECRET_FILE}" ]; then
  echo "Kernel secret is defined as an environment variable"

  exit 0
fi

if [ -f "/srv/config/secrets/kernel_secret" ]; then
  echo "Kernel secret exists"

  exit 0
fi

# Make sure secrets directory exists
mkdir -p /srv/config/secrets

# Generate a kernel secret and save the value
secret=$(openssl rand -base64 12)
echo $secret > /srv/config/secrets/kernel_secret

echo "Generated a new kernel secret"
