# Installing Conductor

## Docker image

Our Docker images are available [on GitHub][github-docker-images].

### Example Docker Compose configuration

```yaml
services:
  conductor:
    image: ghcr.io/codedmonkey/conductor:0.1
    ports:
      - "7015:7015"
    volumes:
      - ./config:/srv/config
      - data:/srv/data

volumes:
  data:
```

### Configuring the Docker image

When booting from the Docker image, Conductor will look for custom configuration in the `/srv/config` directory. Make
sure to mount it in the container as a volume.

Create a file in this directory called `conductor.yaml` and add the following contents:

```yaml
conductor:
    title: My Conductor
    slug: my-conductor
    security:
        public: false
        registration: false
```

For a complete list of configuration options, see the [Configuration Reference][docs-configuration-reference].

[docs-configuration-reference]: configuration-reference.md
[github-docker-images]: https://github.com/codedmonkey/conductor/pkgs/container/conductor
