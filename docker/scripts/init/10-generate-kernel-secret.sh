#!/bin/sh

set -e

# Make sure secrets directory exists
mkdir -p /srv/config/secrets

# Generate a kernel secret and save the value
secret=$(openssl rand -base64 12)
echo $secret > /srv/config/secrets/kernel_secret
