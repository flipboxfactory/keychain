Changelog
=========

## Unreleased

### Security
- Key pairs created with "bring your own key" from the control panel stored their private key and certificate unencrypted in the `settings` column, even with "Encrypt at Rest" on. They're no longer copied there, and a migration removes them from existing key pairs. If you used encryption at rest for a BYOK key pair, consider rotating it, because database backups made before this update may contain the plaintext key.
- The key pair edit page no longer renders the decrypted private key. Admins can reveal it on demand with **Reveal current key**, which requires an elevated session (re-entering their password) and isn't cached.

### Fixed
- Saving an encrypted key pair with a new private key validated the old key against the certificate, so a key that didn't match could be saved
- A key pair could be saved without a private key or certificate

## 5.0.2 - 2026-11-07

### Changed
- Control panel pages use Craft's own markup and classes: a plain data table with an empty state for the key pair list, standard sidebar fields and metadata on the edit pages, and consistent "Encrypt at Rest" labels

### Fixed
- Description validation errors weren't shown on the edit page (looked up under `keypair` instead of `description`)

## 5.0.1 - 2026-11-07

### Fixed
- Generating an OpenSSL key pair no longer fails when a certificate field (e.g. country) is left blank

### Added
- Codeception test suite, craftcms/ecs and craftcms/phpstan checks, and a GitHub Actions workflow
- Merging a version bump to master now tags and publishes the GitHub release automatically

## 5.0.0 - 2024-04-02

### Feature
- Craft CMS 5.0 support

## 4.0.0 - 2022-06-13
### Fixed
- upgraded for Craft 4.0

## 1.0.3 - 2020-01-07
### Fixed
- Fixing issue with requiring admin when project config when `allowAdminChanges` general config is set.

## 1.0.2 - 2019-10-07
### Removed
- flipboxfactory/craft-ember package to easy updates with dependancies.

## 1.0.1 - 2019-09-01
### Fixed
- Fixed issue with isEncrypted preventing saves.

## 1.0.0-RC1
### Added
- Auto generate method for OpenSSL

## Unreleased
Initial release.
