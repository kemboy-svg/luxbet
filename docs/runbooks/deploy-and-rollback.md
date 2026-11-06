# Deploy and rollback

## Normal deploy

Merging to `main` builds `ghcr.io/kemboy-svg/luxbet-app` and `luxbet-web`
tagged with the commit SHA, copies `compose.yml` and the scripts to the
Droplet, and runs `scripts/deploy.sh <sha>`, which:

1. pulls the new images;
2. takes an encrypted database backup;
3. runs migrations;
4. runs `luxbet:preflight`. **If it fails, the new release is not started**
   and the old containers keep serving;
5. starts the new containers and restarts queue workers;
6. checks `luxbet:launch-gate` reports DISABLED, and stops the web
   container if it does not;
7. records the SHA in `/opt/luxbet/.deploy-history`.

Watch it in **Actions → staging**. Then check the site loads and the demo
banner shows.

## Manual deploy

```bash
ssh deploy@DROPLET_IP
cd /opt/luxbet
scripts/deploy.sh <commit-sha>
```

## Rollback

Application-only change (no new migrations): redeploy the previous SHA.

```bash
tail -n 5 .deploy-history
scripts/deploy.sh <previous-sha>
```

The release included migrations:

1. Prefer a **forward fix** (a new commit) if the data is fine.
2. Otherwise restore the backup taken just before the deploy (step 2 above;
   newest file in `/var/backups/luxbet`) with
   `scripts/restore-db.sh --replace <file>`, then deploy the previous SHA.
   Data written since that backup is lost; note it in the incident log.

## Maintenance mode

```bash
docker compose exec app php artisan down --retry=60
docker compose exec app php artisan up
```

## Never

- Never edit code inside a running container. Build a new image.
- Never put payment credentials in `.env`. Preflight blocks the release if
  it finds any.
- Never change `config/luxbet.php` `real_money.enabled`. The real-money gate
  is a separate, approved project (see `docs/regulatory-launch-gate.md`).
