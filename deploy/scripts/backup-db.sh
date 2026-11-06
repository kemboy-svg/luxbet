#!/usr/bin/env bash
# Encrypted PostgreSQL backup for LuxBet staging/production.
#
#   /opt/luxbet/scripts/backup-db.sh
#
# Writes BACKUP_DIR/luxbet-<UTC timestamp>.dump.enc (pg_dump custom format,
# AES-256 via openssl), uploads it to DigitalOcean Spaces when configured,
# and deletes local copies older than BACKUP_RETENTION_DAYS.
# Schedule it with cron (see docs/runbooks/backups-and-restore.md).
set -euo pipefail

ROOT="${LUXBET_ROOT:-/opt/luxbet}"
cd "$ROOT"
set -a; . ./.env; . ./backup.env; set +a

: "${BACKUP_PASSPHRASE:?BACKUP_PASSPHRASE is not set in backup.env}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/luxbet}"
RETENTION="${BACKUP_RETENTION_DAYS:-14}"

umask 077
mkdir -p "$BACKUP_DIR"
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
file="$BACKUP_DIR/luxbet-$stamp.dump.enc"

echo "Backing up database $DB_DATABASE to $file"
docker compose exec -T pgsql pg_dump -U "$DB_USERNAME" -d "$DB_DATABASE" --format=custom --no-owner \
    | openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass env:BACKUP_PASSPHRASE -out "$file"

# A dump that decrypts and lists cleanly is at least structurally valid.
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass env:BACKUP_PASSPHRASE -in "$file" \
    | docker compose exec -T pgsql pg_restore --list >/dev/null
sha256sum "$file" > "$file.sha256"
echo "Backup written and verified ($(du -h "$file" | cut -f1))."

if [ -n "${SPACES_BUCKET:-}" ]; then
    echo "Uploading to Spaces bucket $SPACES_BUCKET"
    docker run --rm -v "$BACKUP_DIR:/backups:ro" \
        -e AWS_ACCESS_KEY_ID="$SPACES_KEY" -e AWS_SECRET_ACCESS_KEY="$SPACES_SECRET" \
        amazon/aws-cli:2.22.0 s3 cp "/backups/$(basename "$file")" "s3://$SPACES_BUCKET/luxbet/$(basename "$file")" \
        --endpoint-url "https://$SPACES_REGION.digitaloceanspaces.com" --only-show-errors
    docker run --rm -v "$BACKUP_DIR:/backups:ro" \
        -e AWS_ACCESS_KEY_ID="$SPACES_KEY" -e AWS_SECRET_ACCESS_KEY="$SPACES_SECRET" \
        amazon/aws-cli:2.22.0 s3 cp "/backups/$(basename "$file").sha256" "s3://$SPACES_BUCKET/luxbet/$(basename "$file").sha256" \
        --endpoint-url "https://$SPACES_REGION.digitaloceanspaces.com" --only-show-errors
fi

find "$BACKUP_DIR" -name 'luxbet-*.dump.enc*' -mtime +"$RETENTION" -delete
echo "Done."
