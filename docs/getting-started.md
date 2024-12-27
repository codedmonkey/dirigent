---
sidebar_position: 3
---

# Getting Started with Dirigent

Make sure you've followed the [installation][docs-install] guide before continuing.

## Create an Owner account

When accessing the login page of Dirigent for the first time, you'll be redirected to the registration page (even if
registration is disabled). The first account created automatically gets an Owner role in the application.

## Mirror public packages

After installing Dirigent, the [Packagist registry][packagist] is added as an external registry which makes it possible
to mirror public packages. Dirigent is initially configured to only mirror packages explicitly added by an
administrator.

To enable mirroring of public packages on request, (as an administrator) open the "Registries" page, locate the
Packagist registry, click the three dots and click "Edit". Find the "Package Mirroring" option and select "Automatically
mirror packages on request". Save the registry for the changes to take effect.

## Add packages

[docs-install]: install.md
[packagist]: https://packagist.org
