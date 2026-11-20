# keychain

Craft CMS plugin that stores X.509 key pairs for other plugins (`flipboxfactory/saml-core`, `saml-sp`, `saml-idp`). Those consume `KeyChainService`, `KeyChainRecord::getDecryptedKey()` / `getDecryptedCertificate()` and the `keychain` table, so treat them as API: breaking them needs a major version.

## Security first

KeyChain holds the private keys behind commercial SSO/identity plugins that customers buy to protect their Craft sites. A leaked or mishandled key compromises a site's authentication. Security and code quality take priority over speed, convenience and scope.

- **Private keys are encrypted at rest** with Laravel's `Crypt` (`APP_KEY`); 5.x used Craft 5's security key, which only `database/migrations/2026_11_20_000000_reencrypt_key_pairs_for_craft_6.php` (via `src/security/LegacyEncryption.php`) still reads. Never store, return, log or render a decrypted key unless a caller explicitly asks for it. That includes exception messages, debug output, CP templates and project config.
- **Fail closed.** If decryption, key/certificate pairing or validation fails, raise an error. Never fall back to plaintext or to an unvalidated key.
- **Don't weaken key generation or validation defaults**, such as key size, digest or the key/cert pair check, to make something work. If one gets in the way, stop and raise it.
- **Control panel screens and actions** that create, view, download or delete keys must require an admin (`RequireAdmin` in `routes/cp.php` and `routes/actions.php`) and CSRF protection (POST/DELETE only). Craft registers action routes on the site's action trigger too, without authentication. Never flash a submitted private key to the session (`flashExcept(['key'])`, not `asFailure()`).
- **Dependencies**: run `composer audit` and check GitHub advisories before adding or bumping one, and justify any new dependency.
- **Security fixes** ship as a patch release with a regression test (one that fails without the fix) and a CHANGELOG `### Security` entry naming the advisory (GHSA/CVE).
- When unsure how OpenSSL or Craft's security service behaves, verify it against the source or docs rather than guessing, and say what you couldn't verify.

## Before every commit

```bash
composer check   # Pint + Larastan + Pest; must pass
composer fix-cs  # auto-fix style, then re-run check
```

This branch needs PHP 8.5 (Craft 6). Without it locally, run commands through `../../.dev/php85/run` from the worktree, e.g. `../../.dev/php85/run composer check`. Tests run on in-memory SQLite; set `DB_CONNECTION`/`DB_HOST`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` for MySQL or PostgreSQL (CI runs all three).

## Rules

- Add or update Pest tests in `tests/Feature/` (with Craft booted) or `tests/Unit/` for every behaviour change or bug fix. Bug fixes get a test that fails without the fix.
- Don't lower the PHPStan level or add a baseline. Use an inline `@phpstan-ignore <identifier>` with a reason only for genuine false positives.
- Schema changes need a dated Laravel migration in `database/migrations/` (and the same change in `Install.php`), plus a `schemaVersion` bump in `composer.json` `extra` and `KeyChain::$schemaVersion`.
- Keep style-only changes (`fix-cs`) in their own commit, separate from logic changes.
- Don't commit generated or local files: `tests/.env`, `tests/_output`, `vendor/`, `composer.lock`.

## Workflow

- Default branch is `master` (Craft 5 until Craft 6 GA). Craft 6 work targets `craft/6.0`. Work on a branch and open a PR; CI (`.github/workflows/ci.yml`) must be green.
- **PR titles and commit messages use [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/)**: `<type>(<scope>): <description>`, e.g. `fix(sso): ...` (see `CONTRIBUTING.md`). Always title PRs this way: the title is the squash-merge commit and decides the release. Pick the type by what users get: `fix:` for a bug or security fix, `feat:` for new behaviour, `!` for a breaking change to public API or config; otherwise a non-releasing type. If unsure whether a change should release, ask.
- SemVer from the PR title: `fix:`/`perf:` → patch, `feat:` → minor, `<type>!:` or a `BREAKING CHANGE:` line in the PR body → major. `docs`, `test`, `ci`, `chore`, `build`, `refactor`, `style` and `revert` don't release. A releasing PR adds its notes at the top of `CHANGELOG.md` under `## Unreleased` (with `### Security|Fixed|Added|Changed|Removed`; `## Unreleased [CRITICAL]` for a critical release). Don't touch `composer.json` `version`/`extra.version` or write version headings. The "Release check" CI job enforces this.
- Merging to `master` releases once checks and tests pass: CI renames `Unreleased` to `## X.Y.Z - YYYY-MM-DD`, sets the `composer.json` version, pushes a `chore(release): X.Y.Z` commit and the unprefixed tag (`5.0.1`) as the release GitHub App, and creates the GitHub release from that section (`.github/scripts/version.sh`). Never bump, tag or release manually.
- Maintenance branches `craft/X.Y` (e.g. `craft/5.0` for Craft 5 LTS) release the same way, continuing from their own newest tag; target fixes there and port them across. While `.github/prerelease` exists (`alpha`, `beta` or `rc`), merges release prereleases (`6.0.0-beta.1`, `6.0.0-beta.2`, …); a releasing PR that deletes it publishes the final version. See the header of `.github/scripts/version.sh`.
- Security advisory fixes merge from the advisory's private fork as one "Merge commit from fork" squash with no PR here. Its release comes from the fork's commit subjects (listed in the squash body), so give every commit on the fork branch a conventional subject (`fix(security): …`) and put the notes under `Unreleased`. The fork has no Actions, so run `composer check` locally before merging.
- Docs go in `README.md`. Keep them concise.
