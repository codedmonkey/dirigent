---
sidebar_position: 99
---

# Dirigent Configuration Reference

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

## dist_mirroring

### enabled

Type: `boolean` | Default: `false`

Whether to enable or disable distribution mirroring

### preferred

### dev_packages

## Example configuration

```yaml
dirigent:
    title: My Dirigent
    slug: my-dirigent
    security:
        public: false
        registration: false
    storage:
        path: '%kernel.project_dir%/storage'
    packages:
        dynamic_updates: true
        dynamic_update_delay: true
        periodic_updates: true
        periodic_update_interval: true
    dist_mirroring:
        enabled: true
        preferred: true
        dev_packages: false
```

[iso-8601-durations]: https://en.wikipedia.org/wiki/ISO_8601#Durations
