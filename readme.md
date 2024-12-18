# Dirigent

A free and open package registry for [Composer][composer], a package manager primarily used for managing and installing
dependencies in [PHP][php].

Take back control of your dependencies:
- Publish private packages
- Mirror packages from external registries, including [Packagist][packagist]

Please note that Dirigent only supports Composer 2. There are no plans to support Composer 1 at this time.

## Currently under development

Please note that Dirigent is still under heavy development, and it's currently **not safe to use in production**.
Proceed with caution.

## Installation

The easiest way to install Dirigent is through our Docker image.

```shell
docker run -p "7015:7015" ghcr.io/codedmonkey/dirigent:0.3
```

Learn more about installing and configuring Dirigent in our [installation guide][docs-install].

## Sponsor

Help Dirigent by [sponsoring][codedmonkey-sponsor] its development!

## Documentation

The documentation is available [in our Git repository][docs].

## License

Dirigent is licensed under the _Functional Source License, Version 1.1, MIT Future License_. It's free to use for
internal and non-commercial purposes, but it's not allowed to use a release for commercial purposes (competing use)
until it's second anniversary. See our [full license][license] for more details.

## Contributing

Feel free to report issues and make suggestions on [GitHub][github-issues].

## About Me

Dirigent is developed by [Coded Monkey][codedmonkey].

[codedmonkey]: https://www.codedmonkey.com
[codedmonkey-sponsor]: https://www.codedmonkey.com/sponsor?project=dirigent
[composer]: https://getcomposer.org
[docs]: docs/readme.md
[docs-install]: docs/install.md
[github-issues]: https://github.com/codedmonkey/dirigent/issues
[license]: license.md
[packagist]: https://packagist.org
[php]: https://php.net
