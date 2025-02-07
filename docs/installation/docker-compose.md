---
sidebar_label: Docker Compose
sidebar_position: 2
---

# Install Dirigent using Docker Compose

This page is a stub.

It's currently not possible to run Dirigent through multiple services.

## Example configuration file

```yaml
services:
  database:
    image: postgres:${POSTGRES_VERSION:-16}-alpine
    environment:
      POSTGRES_DB: ${POSTGRES_DB:-dirigent}
      # You should definitely change the password
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD:-!ChangeMe!}
      POSTGRES_USER: ${POSTGRES_USER:-dirigent}
    healthcheck:
      test: ["CMD", "pg_isready"]
      timeout: 5s
      retries: 5
      start_period: 60s
    volumes:
      - ./postgres-data:/var/lib/postgresql/data:rw
```
