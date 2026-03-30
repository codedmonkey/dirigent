# Changelog

* 0.6.1 (2026-03-30)
  * Fixed loading of encryption module in dashboard pages which prevented changes to existing credentials
  * Disabled autocomplete on credentials information

* 0.6.0 (2026-03-23)
  * Added configuration option to fetch mirrored packages from their VCS repositories by default when possible 
  * Improved the `packages:update` command with clearer arguments
  * Improved user roles by only allowing a single role per user
  * Improved sorting of package links by adding indices to the stored metadata
  * Improved code quality with:
    * Added scripts for development as Composer scripts
    * Integrated Rector into development workflow
    * Added development guidelines for AI agents
    * Added Claude commands for code reviewing
  * Updated various Composer dependencies

* 0.5.0 (2025-10-17)
  * **Breaking changes**
    * Changed unique field for users from their email address to their username
  * Added *optional* multi-factor authentication [#6](https://github.com/codedmonkey/dirigent/pull/6)
  * Added source and distribution info to package pages
  * Added package links for dependents, suggesters and providers
  * Improved package and package list pages
  * Implemented pretty URLs through EasyAdmin
  * Disabled online check for compromised password
  * Improved code quality with:
    * Run tests in database transactions with `dama/doctrine-test-bundle`
    * Various code style improvements
  * Updated Symfony dependencies to version 7.3

* 0.4.0 (2025-04-06)
  * **Breaking changes**
    * An encryption key is now required for Dirigent to function. When using the standalone image, make sure to
      mount the contents of `/srv/config`, since sensitive information in the database will automatically be
      encrypted with a key stored in `/srv/config/secrets`. Losing the encryption key will make stored credentials
      unreadable.
    * The default kernel secret (`APP_SECRET`) has been refactored to only apply to `dev` environments.
  * Added encrypted database values for Credentials entities [#4](https://github.com/codedmonkey/dirigent/pull/4)
  * Added `bin/dirigent` binary with only app-related commands
  * Added automated random kernel secret in standalone image, stored in `/srv/config/secrets/kernel_secret`
  * Improved initialization process of standalone image [#3](https://github.com/codedmonkey/dirigent/pull/3)
  * Improved code quality with:
    * Testing of images with [Testcontainers](https://github.com/testcontainers/testcontainers-php/)
    * Static analysis with [PHPStan](https://phpstan.org)
    * Various improvements to GitHub Actions workflows
  * Bumped minimum PHP version to 8.3
  * Updated Symfony dependencies to version 7.2

* 0.3.1 (2025-01-15)
  * Fixed error on dashboard root when not logged in
  * Fixed permission error on `/srv/data` in Docker image
  * Fixed PostgreSQL sequences in database migrations

* 0.3.0 (2025-01-14)
  * Renamed project to Dirigent
  * Added custom-built assets with Webpack
  * Added Edit Package page
  * Added Package Statistics page
  * Added package fetch strategy field
  * Added GitHub access tokens as a credential type
  * Added GITHUB_TOKEN env var support

* 0.2.3 (2024-12-15) 
  * Fixed access token form events triggering in all forms

* 0.2.2 (2024-12-10)
  * Changed internal names of downloads to installations
  * Fixed database migrations

* 0.2.1 (2024-11-23)
  * Access tokens are now encrypted before being stored in the database
  * Added expiry dates for access tokens
  * Fixed removing existing packages and versions
  * Fixed downloads tracking without public access enabled

* 0.2.0 (2024-10-23)
  * Added custom theme
  * Added database migrations
  * Added resolving of README files from VCS repositories
  * Added package download tracking
  * Improved validation of VCS repositories
  * Fixed an issue where the API returned the output from external registries instead of Dirigent

* 0.1.0 (2024-08-03)
  * Initial release
