# Deployment

## Environments

| Environment | `APP_ENV`    | `LUXBET_ENVIRONMENT_MODE`     | Notes                             |
| ----------- | ------------ | ----------------------------- | --------------------------------- |
| Local       | `local`      | `demo`                        | Docker Compose stack              |
| CI          | `testing`    | `demo`                        | GitHub Actions service containers |
| Staging     | `staging`    | `staging`                     | Sandbox provider credentials only |
| Production  | `production` | `demo` until legally approved | Still demo-only                   |

Each environment has its own credentials. Never use production credentials in
local, CI, demo or staging environments, and never commit secrets.

## Local

See the README. `docker-compose.yml` provides PostgreSQL 16, Redis 7,
Mailpit, PHP-FPM 8.3 and Nginx, with ports bound to `127.0.0.1`.

## Staging (DigitalOcean)

One Ubuntu 24.04 Droplet running Docker Compose. Full setup steps are in
[runbooks/staging-setup.md](runbooks/staging-setup.md).

| Piece            | Where                                                                                                                                                 |
| ---------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| Images           | `deploy/docker/Dockerfile`: `app` (PHP-FPM 8.4, also queue and scheduler) and `web` (Caddy with automatic HTTPS), pushed to GHCR tagged by commit SHA |
| Server layout    | `deploy/compose.staging.yml` → `/opt/luxbet/compose.yml`; secrets in `/opt/luxbet/.env` and `backup.env` (mode 600)                                   |
| Pipeline         | `.github/workflows/staging.yml`: PRs build images; `main` builds, pushes and deploys when `STAGING_DEPLOY_ENABLED` is `true`                          |
| Deploy           | `deploy/scripts/deploy.sh`: backup → migrate → `luxbet:preflight` → swap → launch-gate check                                                          |
| Backups          | `deploy/scripts/backup-db.sh` (encrypted, optional Spaces copy) and `restore-db.sh --verify` drills                                                   |
| Server hardening | `deploy/scripts/bootstrap-droplet.sh`: ufw (22/80/443), fail2ban, unattended upgrades, key-only SSH, non-root deploy user                             |

Controls in place:

- HTTPS only via Caddy (Let's Encrypt), HSTS from the app.
- PostgreSQL and Redis only on the private Docker network; Redis needs a
  password.
- `APP_DEBUG=false`, secure session cookies, encrypted sessions.
- Non-root containers with `no-new-privileges`.
- `luxbet:preflight` blocks any release with an open launch gate, payment
  credentials, live secrets outside production mode, debug mode, a
  plain-HTTP URL, insecure cookies or pending migrations.
- `luxbet:launch-gate` must report DISABLED after every deploy, or the web
  container is stopped.

Still to add (not blocking staging): uptime monitoring (DigitalOcean
Monitoring alerts or an external checker on `/up`) and error tracking.

## Production

Same layout on a separate Droplet with separate credentials, plus DigitalOcean
Droplet backups and a managed PostgreSQL cluster if the data grows. Production
stays in demo mode until the regulatory review (Milestone 8) and a separately
approved project are complete.

## Runbooks

See [runbooks/](runbooks/README.md) for deploys and rollbacks, backups and
restore drills, incidents, provider outages and account actions.
