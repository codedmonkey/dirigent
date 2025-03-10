---
sidebar_label: Introduction
sidebar_position: 2
---

# What is Dirigent?

Dirigent is an open-source package registry for [Composer][composer]. Programmers using [PHP][php] to develop software
can use Dirigent to distribute private packages or mirror packages from external registries, like [Packagist][packagist].

## Why should I use Dirigent?

A package registry that can be hosted anywhere by anyone can be useful for a number of reasons.

### Sharing packages

Dirigent allows sharing packages across teams, across machines (including DevOps and production servers) and most
importantly, across projects, so even a solo developer can use Dirigent to improve their workflow.

### Availability

Most people take the internet for granted, but there are a lot of cases where this simply isn't the case. Think about
living in a remote location or a closed facility, where internet is either limited, very expensive or in some cases
non-existent.

Luckily, Composer already does a good job of caching previously downloaded packages on your system to mitigate some
of these scenarios, but it doesn't solve all of them. Dirigent can serve as a proxy for external registries to save
bandwidth when using Composer across multiple workflows or systems. Dirigent can also be installed and configured in
a location where internet is generally available before being moved elsewhere.

## Are there limitations to using Dirigent?

### Performance

When using Dirigent to mirror packages from external registries, it does not serve files directly from a web server so
it's not necessarily faster than the source registry. Especially when dynamic updates and dist mirroring are enabled,
Dirigent might be slower, but gives you full control over the dependencies in return.

If you're looking to speed up your Composer installations, try [Velocita][velocita].

## Can I use Dirigent at my for-profit business?

Dirigent is released under the [FSL-1.1-MIT License][github-license], which gives anybody the permission to use
Dirigent for non-commercial purposes, including for-profit businesses. The only thing that's not allowed is creating
a business model specifically around Dirigent or forks thereof. This limitation expires after 2 years, see our
[license][github-license] for the exact details.

We're always looking into moving parts of the code into separate packages with a more permissive grant, feel free
to open an issue on GitHub with suggestions.

[composer]: https://getcomposer.org
[github-license]: https://github.com/codedmonkey/dirigent/blob/main/license.md
[packagist]: https://packagist.org
[php]: https://www.php.net
[velocita]: https://github.com/gmta/velocita-proxy
