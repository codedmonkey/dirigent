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
  dist_mirroring:
    enabled: false
    preferred: true
    dev_packages: false
  metadata:
    default_fetch_strategy: 'mirror'
    retain_pruned_versions:
      enabled: true
      tagged_versions: true
      dev_versions: false
    retain_stale_revisions:
      enabled: true
      tagged_versions: true
      dev_versions: false
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

## dist_mirroring

### enabled

Type: `boolean` | Default: `false`

Whether to enable or disable distribution mirroring

### preferred

### dev_packages

## metadata

### default_fetch_strategy

Type: `string` | Default: `mirror`

Configure the default fetch strategy for new packages:

**mirror** (Fetch from mirror)  
Always try to mirror package metadata from mirror registries when possible. Only metadata from the project's
`composer.json` is available. If mirroring is not possible, it defaults to `source` instead.  
**source** (Fetch from source)  
Fetch the package metadata directly from the source, but it doesn't have to include VCS data. For example, when the
package is hosted on GitHub the API is used instead, which saves on storage and bandwidth but limits the amount of
metadata that's available.  
**vcs** (Fetch from VCS)  
Fetch the package metadata directly from the source through VCS. Package metadata is created directly from the
source code.

### retain_pruned_versions

#### enabled

Type: `boolean` | Default: `true`

Whether to enable or disable retaining pruned versions of packages.

#### tagged_versions

Type: `boolean` | Default: `true`

Retain pruned tagged package versions.

#### dev_versions

Type: `boolean` | Default: `false`

Retain pruned development package versions.

### retain_stale_revisions

#### enabled

Type: `boolean` | Default: `true`

Whether to enable or disable retaining stale revisions of packages.

#### tagged_versions

Type: `boolean` | Default: `true`

Retain stale revisions of tagged package versions.

#### dev_versions

Type: `boolean` | Default: `false`

Retain stale revisions of development package versions.

[iso-8601-durations]: https://en.wikipedia.org/wiki/ISO_8601#Durations
[symfony]: https://symfony.com
[symfony-docs-config]: https://symfony.com/doc/current/configuration.html
