# keychain

Craft CMS plugin that stores X.509 key pairs for other plugins (`flipboxfactory/saml-core`, `saml-sp`, `saml-idp`). Those consume `KeyChainService`, `KeyChainRecord::getDecryptedKey()` / `getDecryptedCertificate()` and the `keychain` table, so treat them as API: breaking them needs a major version.

## Security first

KeyChain holds the private keys behind commercial SSO/identity plugins that customers buy to protect their Craft sites. A leaked or mishandled key compromises a site's authentication. Security and code quality take priority over speed, convenience and scope.

- **Private keys are encrypted at rest** with Craft's security key. Never store, return, log or render a decrypted key unless a caller explicitly asks for it. That includes exception messages, debug output, CP templates and project config.
- **Fail closed.** If decryption, key/certificate pairing or validation fails, raise an error. Never fall back to plaintext or to an unvalidated key.
- **Don't weaken key generation or validation defaults**, such as key size, digest or the key/cert pair check, to make something work. If one gets in the way, stop and raise it.
- **Control panel actions** that create, view, download or delete keys must require admin permissions and CSRF protection.
- **Dependencies**: run `composer audit` and check GitHub advisories before adding or bumping one, and justify any new dependency.
- **Security fixes** ship as a patch release with a regression test (one that fails without the fix) and a CHANGELOG `### Security` entry naming the advisory (GHSA/CVE).
- When unsure how OpenSSL or Craft's security service behaves, verify it against the source or docs rather than guessing, and say what you couldn't verify.

## Before every commit

```bash
composer check   # ECS + PHPStan + Codeception; must pass
composer fix-cs  # auto-fix style, then re-run check
```

Tests need MySQL and `tests/.env` (see README "Development").

## Rules

- Add or update tests in `tests/unit/` for every behaviour change or bug fix. Bug fixes get a test that fails without the fix.
- Don't lower the PHPStan level or add a baseline. Use an inline `@phpstan-ignore <identifier>` with a reason only for genuine false positives.
- Schema changes need a migration in `src/migrations/` and a `schemaVersion` bump in `composer.json` `extra`.
- Keep style-only changes (`fix-cs`) in their own commit, separate from logic changes.
- Don't commit generated or local files: `tests/.env`, `tests/_output`, `tests/_support/_generated`, `vendor/`, `composer.lock`.

## Workflow

- Default branch is `master`. Work on a branch and open a PR; CI (`.github/workflows/ci.yml`) must be green.
- SemVer, decided by the PR title (conventional commits; PRs are squash-merged, so the title becomes the commit): `fix:`/`perf:` → patch, `feat:` → minor, `<type>!:` or a `BREAKING CHANGE:` line in the PR body → major. `docs`, `test`, `ci`, `chore`, `build`, `refactor`, `style` and `revert` don't release. A releasing PR adds its notes at the top of `CHANGELOG.md` under `## Unreleased` (with `### Security|Fixed|Added|Changed|Removed`; `## Unreleased [CRITICAL]` for a critical release). Don't touch `composer.json` `version`/`extra.version` or write version headings. The "Release check" CI job enforces this.
- Merging to `master` releases once checks and tests pass: CI renames `Unreleased` to `## X.Y.Z - YYYY-MM-DD`, sets the `composer.json` version, pushes a `chore(release): X.Y.Z` commit and the unprefixed tag (`5.0.1`) as the release GitHub App, and creates the GitHub release from that section (`.github/scripts/version.sh`). Never bump, tag or release manually.
- Docs go in `README.md`. Keep them concise.
