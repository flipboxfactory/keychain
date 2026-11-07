# keychain

Craft CMS plugin that stores X.509 key pairs for other plugins (`flipboxfactory/saml-core`, `saml-sp`, `saml-idp`). Those consume `KeyChainService`, `KeyChainRecord::getDecryptedKey()` / `getDecryptedCertificate()` and the `keychain` table, so treat them as API: breaking them needs a major version. Private keys are encrypted at rest with Craft's security key; never log or expose decrypted keys.

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
- SemVer. Keep `composer.json` `version` and `extra.version` in sync; git tags are unprefixed (`5.0.1`) and match them.
- Add a `CHANGELOG.md` entry (newest first, `## X.Y.Z - YYYY-MM-DD` with `### Fixed|Added|Changed|Removed`) for any user-facing change.
- Docs go in `README.md`. Keep them concise.
