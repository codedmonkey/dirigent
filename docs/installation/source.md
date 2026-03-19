---
sidebar_label: Source code
sidebar_position: 3
---

# Running Dirigent from the source code

:::note

This page is a stub.

:::

Running the project from the source code is not guaranteed to work on every system.

## Requirements

To install Dirigent on your system from source you need to have the following packages installed on your system:

- Git
- PHP 8.3 or higher
- Composer 2
- Web server (like Nginx or Caddy)
- PHP-FPM
- PostgreSQL 16
- Node 23

## Download source code

The recommended way to download Dirigent is through Git:

```shell
git clone https://github.com/codedmonkey/dirigent.git
cd dirigent
```

You can also download the source code directly from the [Releases][github-releases] page on GitHub.

## Install build tools

```shell
composer check-platform-reqs
composer install
```

```shell
npm install
npm run production
```

:::note

Stop here if you're following the "Contributing to Dirigent" guide.

:::

## Configure services

[github-releases]: https://github.com/codedmonkey/dirigent/releases
