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

```shell
# Run all linters
symfony composer lint
```

### Running PHPUnit tests

```shell
# Before running the tests, make sure the testing database is ready
symfony composer tests:setup

# Run PHP tests
symfony composer tests:php

# Run tests on Docker images
symfony composer tests:docker 
```

[codedmonkey-sponsor]: https://www.codedmonkey.com/sponsor?project=dirigent
[dirigent-docs-install-source]: https://dirigent.dev/docs/installation/source
