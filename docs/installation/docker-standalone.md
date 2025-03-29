---
sidebar_label: Standalone Docker image
sidebar_position: 1
---

# Install Dirigent using the standalone Docker image

The easiest way to install Dirigent is by using our standalone Docker images, which bundle all services required for
Dirigent into one, including a web server, database and a background worker. The standalone Docker images are available
[on GitHub][github-docker-images].

## Examples

### Docker command

```bash
docker volume create dirigent-data

docker container run -d \
  --name dirigent \
  -p "7015:7015" \
  -v /path/to/dirigent/config:/srv/config \
  -v dirigent-data:/srv/data \
  ghcr.io/codedmonkey/dirigent:0.4
```

### Docker Compose configuration

```yaml
services:
  dirigent:
    image: ghcr.io/codedmonkey/dirigent:0.4
    ports:
      - "7015:7015"
    volumes:
      - ./config:/srv/config
      - data:/srv/data

volumes:
  data:
```

## Volumes

Both the `/srv/config` and `/srv/data` directories are configured as volumes, both need to be retained as the config
directory contains encryption keys for sensitive data so make sure to mount it. It's recommend to store the
configuration and data in separate locations, see our [security guide](../security.md) for more information.

## Configuring Dirigent in the image

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

### Environment variables

- `DECRYPTION_KEY` / `DECRYPTION_KEY_FILE`
- `ENCRYPTION_KEY` / `ENCRYPTION_KEY_FILE`
- `GITHUB_TOKEN`
- `KERNEL_SECRET` / `KERNEL_SECRET_FILE`
- `MAILER_DSN`
- `SENTRY_DSN`
- `TRUSTED_PROXIES`

## Running the image

After following the steps above you're ready to boot the image, so run the Docker command to start your
container.

By default, Dirigent is exposed on port `7015` so go to `http://localhost:7015` in your browser to access your
Dirigent installation.

_Note that Composer requires registries to use https by default._

Now that you've installed Dirigent on your system, it's time to [get started][docs-getting-started]!

## Building the standalone Docker image

To build the standalone Docker image, clone [the Dirigent repository][github] and checkout the version or
commit you want to build. Simply run the `docker build` command inside the repository to build the image.

```shell
git clone https://github.com/codedmonkey/dirigent.git
cd dirigent
git checkout v0.4.0
docker build -t dirigent-standalone .
```

### Change UID and GID

The standalone Docker image runs as user `1000:1000`. To run the image with a different UID or GID, you can pass both
a `UID` and `GID` build argument to the `docker build` command.

```shell
docker build -t dirigent-standalone --build-arg UID=1011 --build-arg GID=1110 .
```

[docs-configuration-reference]: ../configuration-reference.md
[docs-getting-started]: ../getting-started.md
[github]: https://github.com/codedmonkey/dirigent
[github-docker-images]: https://github.com/codedmonkey/dirigent/pkgs/container/dirigent
