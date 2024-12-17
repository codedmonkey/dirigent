# Changelog

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
