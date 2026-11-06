# Backups and restore

## What is backed up

- PostgreSQL (all application data, including the demo ledger and audit
  log): `scripts/backup-db.sh` writes an encrypted `pg_dump` (AES-256,
  passphrase in `backup.env`) to `/var/backups/luxbet` and, when Spaces is
  configured, to `s3://<bucket>/luxbet/`.
- Redis holds sessions, cache and queues only. It is not backed up; losing
  it signs users out and drops queued jobs.
- Code and images are rebuilt from git; `.env` and `backup.env` live in your
  password manager.

## Schedule

On the Droplet, as the deploy user (`crontab -e`):

```cron
# Nightly encrypted backup at 02:15 UTC
15 2 * * * /opt/luxbet/scripts/backup-db.sh >> /var/backups/luxbet/backup.log 2>&1
# Monthly restore drill on the 1st at 03:30 UTC (newest backup)
30 3 1 * * /opt/luxbet/scripts/restore-db.sh --verify "$(ls -t /var/backups/luxbet/*.dump.enc | head -1)" >> /var/backups/luxbet/restore-drill.log 2>&1
```

Every deploy also takes a backup before running migrations.

Retention: 14 days locally (`BACKUP_RETENTION_DAYS`). For Spaces, add a
lifecycle rule in the DigitalOcean control panel (e.g. delete after 35 days).

## Restore drill (safe; proves backups work)

```bash
cd /opt/luxbet
scripts/restore-db.sh --verify /var/backups/luxbet/luxbet-<timestamp>.dump.enc
```

It restores into a scratch database, checks:

- the checksum and decryption;
- row counts for users, wallets, ledger and audit log;
- every wallet balance equals its latest ledger entry;
- both append-only triggers are present;

then drops the scratch database. `RESTORE DRILL PASSED` is the acceptance
evidence for Milestone 7. Paste the report into the log below.

To drill from Spaces, download first:

```bash
docker run --rm -v /var/backups/luxbet:/backups --env-file backup.env \
  -e AWS_ACCESS_KEY_ID -e AWS_SECRET_ACCESS_KEY amazon/aws-cli:2.22.0 \
  s3 cp s3://BUCKET/luxbet/FILE /backups/ --endpoint-url https://fra1.digitaloceanspaces.com
```

(set `AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY` to the Spaces key pair).

## Real restore (replaces live data)

```bash
scripts/restore-db.sh --replace /var/backups/luxbet/luxbet-<timestamp>.dump.enc
```

It asks you to type the database name, takes a safety backup, stops the app,
restores, restarts and runs preflight. Record it as an incident.

## Drill log

| Date | Backup file | Duration | Result | By  |
| ---- | ----------- | -------- | ------ | --- |
|      |             |          |        |     |
