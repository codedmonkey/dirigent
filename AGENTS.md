# Agent guidelines for Dirigent development

Dirigent is a free and open package registry for Composer, the PHP package manager. It allows users to publish private packages and mirror packages from external registries like Packagist.

### Project structure

```
assets/                         # Frontend assets
config/                         # Symfony configuration
migrations/                     # Doctrine migrations (PostgreSQL)
src/
├── Attribute/                  # PHP attributes
├── Command/                    # Symfony console commands
├── Composer/                   # Composer integration logic
├── Controller/                 # HTTP controllers
│   └── Dashboard/              # EasyAdmin dashboard controllers
├── Doctrine/
│   ├── Entity/                 # Doctrine ORM entities
│   ├── Repository/             # Doctrine repositories
│   ├── Type/                   # Custom Doctrine types
│   └── DataFixtures/           # Database fixtures
├── Encryption/                 # Encryption utilities
├── Entity/                     # Enums (UserRole, PackageUpdateSource)
├── EventListener/              # Symfony event listeners
├── Form/                       # Symfony form types
├── Message/                    # Symfony messenger messages and handlers (async jobs)
├── Package/                    # Package management services
├── Routing/                    # Symfony routing logic
├── Twig/                       # Twig extensions
└── Validator/                  # Symfony validators
templates/                      # Twig templates
tests/
├── UnitTests/                  # Unit tests
├── FunctionalTests/            # Functional/Integration tests
└── Docker/                     # Docker image tests
```

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
symfony composer lint

# Individual linters
symfony composer lint:refactor        # Rector (automatically applies changes)
symfony composer lint:coding-style    # PHP-CS-Fixer (automatically applies changes)
symfony composer lint:static-analysis # PHPStan level 5
symfony composer lint:container       # Symfony container validation
symfony composer lint:templates       # Twig template validation
```

### Testing

```shell
# Prepare the Symfony test environment for tests (if the database schema changed)
symfony composer tests:setup

# Run all tests
symfony composer tests

# Run only PHP tests
symfony composer tests:php
symfony composer tests:php:unit
symfony composer tests:php:functional

# Run tests for Docker images
symfony composer tests:docker
```
