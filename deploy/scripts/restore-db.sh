#!/usr/bin/env bash
# Restore a LuxBet backup.
#
#   scripts/restore-db.sh --verify  <file.dump.enc>   restore drill (safe)
#   scripts/restore-db.sh --replace <file.dump.enc>   replace the live database
#
# --verify restores into a scratch database (luxbet_restore_check), runs
# integrity checks on the demo ledger and audit log, prints a report and drops
# the scratch database. It never touches the live database. Run it at least
# monthly and after any backup change; record the result in the runbook log.
#
# --replace stops the app, restores over the live database and restarts.
# It asks for confirmation and takes a fresh backup first.
set -euo pipefail

ROOT="${LUXBET_ROOT:-/opt/luxbet}"
cd "$ROOT"
set -a; . ./.env; . ./backup.env; set +a

mode="${1:-}"; file="${2:-}"
if [[ "$mode" != "--verify" && "$mode" != "--replace" ]] || [[ ! -f "$file" ]]; then
    echo "Usage: $0 --verify|--replace <backup.dump.enc>" >&2
    exit 2
fi

if [ -f "$file.sha256" ]; then
    (cd "$(dirname "$file")" && sha256sum --check --quiet "$(basename "$file").sha256")
    echo "Checksum OK."
fi

decrypt() {
    openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass env:BACKUP_PASSPHRASE -in "$file"
}
psql_in() {
    docker compose exec -T pgsql psql -v ON_ERROR_STOP=1 -U "$DB_USERNAME" -d "$1" -At -c "$2"
}

if [ "$mode" = "--verify" ]; then
    scratch=luxbet_restore_check
    started=$(date +%s)
    psql_in postgres "DROP DATABASE IF EXISTS $scratch;"
    psql_in postgres "CREATE DATABASE $scratch;"
    trap 'psql_in postgres "DROP DATABASE IF EXISTS $scratch;" >/dev/null' EXIT

    decrypt | docker compose exec -T pgsql pg_restore -U "$DB_USERNAME" -d "$scratch" --no-owner --exit-on-error

    users=$(psql_in "$scratch" "SELECT count(*) FROM users;")
    wallets=$(psql_in "$scratch" "SELECT count(*) FROM demo_wallets;")
    ledger=$(psql_in "$scratch" "SELECT count(*) FROM demo_wallet_transactions;")
    audit=$(psql_in "$scratch" "SELECT count(*) FROM audit_logs;")
    # Every wallet balance must equal the balance after its latest ledger row.
    mismatched=$(psql_in "$scratch" "
        SELECT count(*) FROM demo_wallets w
        JOIN LATERAL (
            SELECT balance_after_minor FROM demo_wallet_transactions t
            WHERE t.wallet_id = w.id ORDER BY t.created_at DESC, t.reference DESC LIMIT 1
        ) last ON true
        WHERE last.balance_after_minor <> w.balance_minor;")
    gate=$(psql_in "$scratch" "SELECT count(*) FROM launch_control;")
    triggers=$(psql_in "$scratch" "SELECT count(*) FROM pg_trigger WHERE tgname IN ('audit_logs_append_only', 'demo_wallet_transactions_append_only');")

    echo
    echo "Restore drill report ($(date -u +%FT%TZ))"
    echo "  backup:                 $(basename "$file")"
    echo "  users:                  $users"
    echo "  demo wallets:           $wallets"
    echo "  ledger rows:            $ledger"
    echo "  audit entries:          $audit"
    echo "  launch_control rows:    $gate"
    echo "  append-only triggers:   $triggers (expected 2)"
    echo "  wallet/ledger mismatch: $mismatched (expected 0)"
    echo "  duration:               $(( $(date +%s) - started ))s"

    if [ "$mismatched" != "0" ] || [ "$triggers" != "2" ]; then
        echo "RESTORE DRILL FAILED" >&2
        exit 1
    fi
    echo "RESTORE DRILL PASSED"
    exit 0
fi

read -r -p "Replace the LIVE database '$DB_DATABASE' with $(basename "$file")? Type the database name to confirm: " answer
if [ "$answer" != "$DB_DATABASE" ]; then
    echo "Aborted."; exit 1
fi

echo "Taking a safety backup of the current database first..."
"$ROOT/scripts/backup-db.sh"

docker compose stop web app queue scheduler
psql_in postgres "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '$DB_DATABASE' AND pid <> pg_backend_pid();" >/dev/null
psql_in postgres "DROP DATABASE $DB_DATABASE;"
psql_in postgres "CREATE DATABASE $DB_DATABASE OWNER $DB_USERNAME;"
decrypt | docker compose exec -T pgsql pg_restore -U "$DB_USERNAME" -d "$DB_DATABASE" --no-owner --exit-on-error
docker compose up -d
docker compose exec -T app php artisan luxbet:preflight
echo "Live database restored."
