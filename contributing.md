# Contributing to Dirigent

Dirigent is an open-source project, contributions of all kind are welcome, including
[financial contributions][codedmonkey-sponsor].

## Running Dirigent locally

To run Dirigent locally, follow the [Running from source code][dirigent-docs-install-source] guide in the documentation,
up to the *Configure services* section.

Additional requirements:

- Symfony binary
- Docker

```shell
# Optionally, copy the example Docker Compose configuration override file
cp compose.override.example.yaml composer.override.yaml

# Start Docker services
docker compose up -d

# Start Symfony local server
symfony server:start -d
```

## Lint & test your code

### Running PHPUnit tests

Before running the tests, make sure the testing database is ready:

```shell
symfony console --env=test doctrine:database:create --if-not-exists
symfony console --env=test doctrine:schema:update --force
symfony console --env=test doctrine:fixtures:load --no-interaction
```

Run the PHPUnit tests:

```shell
symfony run bin/phpunit
```

[codedmonkey-sponsor]: https://www.codedmonkey.com/sponsor?project=dirigent
[dirigent-docs-install-source]: https://dirigent.dev/docs/installation/source
