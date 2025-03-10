---
sidebar_label: Source code
sidebar_position: 3
---

# Running Dirigent from the source code

:::note

This page is a stub.

:::

Running the project from source code is not guaranteed to work on every system.

## Requirements

To install Dirigent on your system from source you need to following packages:

- Git
- PHP 8.2 or higher
- Composer 2
- Web server (like Nginx or Caddy)
- PHP-FPM
- PostgreSQL
- Node

## Download source code

```shell
git clone https://github.com/codedmonkey/dirigent.git
cd dirigent
```

## Install build tools

```shell
composer check-platform-reqs
composer install
```

```shell
npm install
npm run production
```

## Configure services
