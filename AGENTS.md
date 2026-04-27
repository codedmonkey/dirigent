# Agent guidelines for Dirigent development

Dirigent is a free and open package registry for Composer, the PHP package manager. It allows users to publish private packages and mirror packages from external registries like Packagist.

## Architecture

@ARCHITECTURE.md

## Coding style

### Project

- Environment variables are stored in `.env.dirigent` and `.env.dirigent.*` (not `.env`).

### PHP

- Follow the PER coding style and the Symfony coding standards.
- Organize services into domain-specific namespaces.
- Always use strict comparisons (`===`, `!==`).
- Enforce the use of DateTimeImmutable over DateTime.
- Always use spaces in concatenation (`$a . $b`).
- Always use imports. Use aliases when collisions occur or the imported name is unclear. Do not import classes from the root namespace (e.g. `\RuntimeException`, `\Stringable`); use the fully-qualified backslash prefix inline instead.
- Don't use blank lines between import groups.

## Commands

### Linting & code quality

```shell
# Run all linting jobs
symfony composer run lint

# Individual linters
symfony composer run lint:refactor        # Rector (automatically applies changes)
symfony composer run lint:coding-style    # PHP-CS-Fixer (automatically applies changes)
symfony composer run lint:static-analysis # PHPStan level 5
symfony composer run lint:container       # Symfony container validation
symfony composer run lint:templates       # Twig template validation
```

### Testing

```shell
# Prepare the Symfony test environment for tests (if the database schema changed)
symfony composer run tests:setup

# Run all tests
symfony composer run tests

# Run only PHP tests
symfony composer run tests:php
symfony composer run tests:php:unit
symfony composer run tests:php:functional

# Run tests for Docker images
symfony composer run tests:docker
```
