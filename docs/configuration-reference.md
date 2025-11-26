---
sidebar_label: Configuration reference
sidebar_position: 99
---

# Dirigent configuration reference

Dirigent uses the [Symfony][symfony] framework under the hood. Refer to the [Symfony documentation][symfony-docs-config]
to learn how the configuration system works.

When using the standalone image, any JSON or YAML file located directly in the `/srv/config` directory will be loaded
as a Symfony configuration file.

## Example configuration

```yaml
# dirigent.yaml
dirigent:
  title: 'My Dirigent'
  slug: null
  security:
    public: false
    registration: false
  # not supported by the standalone image
  #storage:
  #  path: '%kernel.project_dir%/storage'
  packages:
    dynamic_updates: true
    dynamic_update_delay: 'PT4H'
    periodic_updates: true
    periodic_update_interval: 'P1W'
  distributions:
    enabled: false
    build: true
    mirror: false
    async_api_requests: false
    dev_packages: false
    preferred_mirror: true
  metadata:
    default_fetch_strategy: 'mirror'
```

## dirigent (root)

### title

Type: `string` | Default: `'My Dirigent'`

The application name as shown in views and emails.

### slug

Type: `string` | Default: `null`

A simplified (lowercase and alphanumeric) version of the application name used as identifier for the registry. Defaults
to a simplified version of the application title.

## security

### public

Type: `boolean` | Default: `false`

Enable public access to the registry. If disabled, only registered users will be able to view and download packages. 

**Note that enabling public access can lead to abuse of your resources**

### registration

Type: `boolean` | Default: `false`

Enable public registration. If disabled, new users can only be created by 

## storage

### path

Type `string` | Default: `'%kernel.project_dir%/storage'`

The path where the application stores Composer and package data.

When using the official Docker image, this is automatically set to `/srv/data` and changing the storage path
is not possible.

## packages

### dynamic_updates

Type: `boolean` | Default: `true`

Whether to enable or disable dynamic updates.

### dynamic_update_delay

Type: `string` | Default: `PT4H`

The time between dynamic updates being triggered, defaults to 4 hours.

The time must be defined in the [ISO 8601 durations][iso-8601-durations] format.

### periodic_updates

Type: `boolean` | Default: `true`

Whether to enable or disable periodic updates.

### periodic_update_interval

Type: `string` | Default: `P1W`

The time between periodic updates being scheduled, defaults to once a week.

The time must be defined in the [ISO 8601 durations][iso-8601-durations] format.

## distributions

### enabled

Type: `boolean` | Default: `false`

Enable hosting of package distributions.

### build

Type: `boolean` | Default: `true`

Enable building distribution from the source.

### mirror

### async_api_requests

### dev_packages

### preferred_mirror

## metadata

### default_fetch_strategy

Type: `string` | Default: `mirror`

todo

Fetch mirrored packages from their VCS repositories by default when possible.

Sets the fetch strategy of new mirrored packages to **Fetch from VCS**.

[iso-8601-durations]: https://en.wikipedia.org/wiki/ISO_8601#Durations
[symfony]: https://symfony.com
[symfony-docs-config]: https://symfony.com/doc/current/configuration.html
