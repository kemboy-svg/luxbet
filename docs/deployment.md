# Deployment

## Environments

| Environment | `APP_ENV` | `LUXBET_ENVIRONMENT_MODE` | Notes |
| --- | --- | --- | --- |
| Local | `local` | `demo` | Docker Compose stack |
| CI | `testing` | `demo` | GitHub Actions service containers |
| Staging | `staging` | `staging` | Sandbox provider credentials only |
| Production | `production` | `demo` until legally approved | Still demo-only |

Each environment has its own credentials. Never use production credentials in
local, CI, demo or staging environments, and never commit secrets.

## Local

See the README. `docker-compose.yml` provides PostgreSQL 16, Redis 7,
Mailpit, PHP-FPM 8.3 and Nginx, with ports bound to `127.0.0.1`.

## Staging and production (Milestone 7)

To be completed with the staging deployment:

- HTTPS only (TLS terminated at Nginx or a load balancer), HSTS.
- PostgreSQL and Redis on a private network, never public ports.
- `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`.
- Encrypted, regular backups with documented and tested restores.
- Container images pinned to reviewed versions; non-root containers.
- Centralised error monitoring and uptime checks.
- `php artisan luxbet:launch-gate` must report `DISABLED` after every deploy.
