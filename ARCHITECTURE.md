# Dirigent architecture

## Technology stack

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

## Directory structure

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

### PHP

Dirigent follows the [PER coding style][per-coding-style] and the [Symfony coding standards][symfony-coding-standards].
