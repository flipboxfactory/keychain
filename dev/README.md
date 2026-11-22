# KeyChain sandbox

A throwaway Craft 6 site running KeyChain from this working copy. It uses Testbench's workbench (`workbench/`, `testbench.yaml`) with SQLite and runs in Docker, so you only need Docker. Each `dev/up` starts from scratch; `dev/down` deletes everything.

```bash
dev/up      # fresh site on http://localhost:8090/admin (admin / password)
dev/down    # stop the server and delete the database, .env and storage
```

`dev/up`:

1. Builds a PHP 8.5 image from `dev/Dockerfile` (first run only takes a few minutes).
2. Runs `composer install` and `composer workbench:setup`. That installs Craft and KeyChain into `workbench/database/database.sqlite` and seeds two key pairs: one generated and encrypted, one bring-your-own and unencrypted.
3. Serves the site with `testbench serve` in the `keychain-sandbox` container.

Code edits apply on the next request. Seeder changes (`workbench/database/seeders/`) need another `dev/up`.

- Another port: `SANDBOX_PORT=8091 dev/up`. 8090 is the default so it doesn't clash with the saml-sp sandbox on 8080.
- Logs: `docker logs -f keychain-sandbox`.
- With PHP 8.5 installed locally, you can skip Docker: `composer workbench:setup && composer serve`.

The admin password is `password`. The sandbox is for local use only.
