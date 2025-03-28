---
sidebar_position: 90
---

# Security

## Kernel secret

To learn more about how and why the kernel secret is used, check out the [Symfony documentation](https://symfony.com/doc/7.2/reference/configuration/framework.html#secret).

:::note

When using the standalone image, the kernel secret is generated automatically. See the [Image secrets](#image-secrets)
section to learn more.

:::

To configure the kernel secret through a custom environment variable, use the following configuration:

```yaml
framework:
  secret: '%env(KERNEL_SECRET)%'
```

## Encryption

In some cases, Dirigent needs to store sensitive information in the database, like GitHub access tokens or SSH keys
that are used for authenticating to private repositories. As a safety precaution, this data is encrypted during
runtime through an encryption key before being stored securely in the database. The encryption key has to be created
before running the application.

### Generate encryption key pair

To generate an encryption key pair, run the following command:

```shell
bin/dirigent encryption:generate-keys
```

:::note

When using the standalone image, this is done automatically when starting the container. See the [Image secrets](#image-secrets)
section to learn more.

:::

This generates both a (private) decryption key and a (public) encryption key, both need to exist for Dirigent to
function. The location of the keys can be changed in the configuration. For example, to use environment variables
to configure the encryption keys, use the following configuration:

```yaml
dirigent:
  encryption:
    private_key: '%env(DECRYPTION_KEY)%'
    private_key_path: '%env(DECRYPTION_KEY_FILE)%'
    public_key: '%env(ENCRYPTION_KEY)%'
    public_key_path: '%env(ENCRYPTION_KEY_FILE)%'
```

### Rotate encryption keys

```yaml
dirigent:
  encryption:
    rotated_keys:
     - '%env(OLD_DECRYPTION_KEY)%'
    rotated_key_paths:
     - '%env(OLD_DECRYPTION_KEY_FILE)%'
```

## Image secrets

When using the standalone image, secrets are stored in the `/srv/config/secrets` directory by default.

- `decryption_key`  
  Unless configured through `DECRYPTION_KEY` or `DECRYPTION_KEY_FILE` environment variables.
- `encryption_key`  
  Unless configured through `ENCRYPTION_KEY` or `ENCRYPTION_KEY_FILE` environment variables.
- `kernel_secret`  
  Unless configured through `KERNEL_SECRET` or `KERNEL_SECRET_FILE` environment variables.
