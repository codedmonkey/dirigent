---
sidebar_position: 2
---

# Installing Dirigent

## Docker image

The easiest way to install Dirigent is by using one of our Docker images, which are available [on GitHub][github-docker-images].

### Examples

#### Docker command

```bash
docker container run -p "7015:7015" -v /path/to/config:/srv/config ghcr.io/codedmonkey/dirigent:0.3
```

#### Docker Compose configuration

```yaml
services:
  dirigent:
    image: ghcr.io/codedmonkey/dirigent:0.3
    ports:
      - "7015:7015"
    volumes:
      - ./config:/srv/config
      - data:/srv/data

volumes:
  data:
```

### Configuring Dirigent in the image

When booting from the Docker image, Dirigent will look for custom configuration in the `/srv/config` directory. Make
sure to mount it in the container as a volume, in the example Docker Compose configuration it's mounted from the
`config/` directory located in your Docker Compose project.

Create a file in the config directory called `dirigent.yaml` and add the following contents:

```yaml
dirigent:
    title: My Dirigent
    slug: my-dirigent
    security:
        public: false # Only enable public access if your instance is located behind a firewall
        registration: false # Only enable registration if your instance is located behind a firewall
```

For a complete list of configuration options, see the [Configuration Reference][docs-configuration-reference].

### Running the image

After following the steps above you're ready to boot the image, so run the Docker command to start your
container.

By default, Dirigent is exposed on port `7015` so go to `http://localhost:7015` in your browser to access your
Dirigent installation.

_Note that Composer requires registries to use https by default._

## Getting Started

Now that you've installed Dirigent on your system, it's time to [get started][docs-getting-started]!

[docs-configuration-reference]: configuration-reference.md
[docs-getting-started]: getting-started.md
[github-docker-images]: https://github.com/codedmonkey/dirigent/pkgs/container/dirigent
