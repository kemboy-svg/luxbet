# Testing

Automated tests are a release requirement.

## Running

```bash
docker compose up -d pgsql redis   # PostgreSQL creates luxbet_testing automatically
composer test                      # Pint --test, Larastan, PHPUnit
npm run check && npm run types:check
```

`composer ci:check` runs everything CI runs. Tests use PostgreSQL (not
SQLite) because the schema relies on PostgreSQL check constraints. The test
database settings are in `phpunit.xml` and can be overridden by environment
variables.

If you use your own PostgreSQL instead of Docker:

```sql
CREATE ROLE luxbet LOGIN PASSWORD 'luxbet_local_only';
CREATE DATABASE luxbet OWNER luxbet;
CREATE DATABASE luxbet_testing OWNER luxbet;
```

## Layout

| Folder              | Contents                                        |
| ------------------- | ----------------------------------------------- |
| `tests/Unit`        | Pure rules, e.g. launch-gate evaluation         |
| `tests/Feature`     | HTTP, database and console behaviour            |
| `tests/Integration` | Provider adapters with fakes (from Milestone 5) |

## Rules

- Every business rule ships with tests.
- Never weaken a security control to make a test pass.
- Use fictional data only. Test fixtures must never persist an enabled
  real-money state; the launch-gate unit tests use unsaved in-memory records.
- Use fake provider adapters by default; real sandbox credentials only in
  protected CI secrets.

## CI

`.github/workflows/tests.yml` runs on pushes to `main` and on pull requests,
with PostgreSQL 16 and Redis 7 service containers, plus a dependency-audit
job (`composer audit`, `npm audit --audit-level=high`).
