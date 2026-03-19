# Contributing to Dirigent

Dirigent is an open-source project, contributions of all kind are welcome, including
[financial contributions][codedmonkey-sponsor].

This guide contains technical information and instructions about the development process of the project that you should
follow when contributing code.

## Project information

### Technology stack

- **Languages & frameworks**:
    - PHP 8.3+
    - Symfony 7.3
    - PostgreSQL 16.x (via Doctrine ORM 3.x)
    - TypeScript
- **Development requirements**:
    - Docker
    - Symfony CLI
- **Package managers**:
    - **PHP**: Composer
    - **TypeScript**: NPM
- **Frontend**: Twig, EasyAdmin 4.x
- **Frontend (JavaScript)**: Webpack Encore, Stimulus
- **Linting**: Rector, PHP-CS-Fixer, PHPStan
- **Testing**: PHPUnit 12.x, Testcontainers

### Coding style

#### PHP

Dirigent follows the [PER coding style][per-coding-style] and the [Symfony coding standards][symfony-coding-standards].

## Running Dirigent locally

### Installation

To run Dirigent locally, follow the [Running from source code][docs-install-source] guide in the documentation,
up to the *Configure services* section.

Additional requirements:

- Symfony binary
- Docker


```shell
# Optionally, copy the example Docker Compose configuration override file
cp compose.override.example.yaml compose.override.yaml

# Install dependencies
composer install
npm install

# Build frontend assets
npm run build # or watch for changes with: npm run watch

# Run services through Docker Compose
docker compose up -d

# Run the Symfony development server
symfony server:start -d

# Create & fill the development database
symfony console doctrine:database:create --if-not-exists
symfony console doctrine:schema:update --force
symfony console doctrine:fixtures:load --no-interaction
```

## Lint & validate the code

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

### Running tests

```shell
# Prepare the Symfony test environment (if the database schema changed)
symfony composer tests:setup
```

```shell
# Run all tests
symfony composer tests

# Run only PHP tests
symfony composer tests:php
symfony composer tests:php:unit
symfony composer tests:php:functional

# Run tests for Docker images
symfony composer tests:docker
symfony composer tests:docker:standalone
```

[codedmonkey-sponsor]: https://github.com/sponsors/codedmonkey
[docs-install-source]: ./docs/installation/source.md
[per-coding-style]: https://www.php-fig.org/per/coding-style/
[symfony-coding-standards]: https://symfony.com/doc/current/contributing/code/standards.html
