#!/usr/bin/env bash
# Deploy a LuxBet image tag on the Droplet.
#
#   /opt/luxbet/scripts/deploy.sh <git-sha>
#
# Steps: pull images -> back up the database -> run migrations -> preflight
# (aborts on failure, old containers keep serving) -> swap containers ->
# confirm the launch gate is DISABLED. Previous tags stay in .deploy-history
# for rollback: scripts/deploy.sh <previous-sha>.
set -euo pipefail

ROOT="${LUXBET_ROOT:-/opt/luxbet}"
cd "$ROOT"

tag="${1:?usage: deploy.sh <image tag (git sha)>}"
if [[ ! "$tag" =~ ^[0-9a-f]{7,40}$ ]]; then
    echo "Image tag must be a git commit SHA." >&2
    exit 2
fi
export LUXBET_IMAGE_TAG="$tag"

echo "==> Pulling images for $tag"
docker compose pull app web

echo "==> Backing up the database"
"$ROOT/scripts/backup-db.sh"

run() { docker compose run --rm --no-deps -T app "$@"; }

echo "==> Running migrations"
run php artisan migrate --force --no-interaction

echo "==> Preflight checks"
if ! run php artisan luxbet:preflight; then
    echo "Preflight failed: the new release was NOT started. Fix the issue or roll back." >&2
    exit 1
fi

echo "==> Starting the new release"
sed -i '/^LUXBET_IMAGE_TAG=/d' .env
echo "LUXBET_IMAGE_TAG=$tag" >> .env
docker compose up -d --remove-orphans
docker compose exec -T app php artisan queue:restart

echo "==> Launch gate status"
docker compose exec -T app php artisan luxbet:launch-gate | tee /tmp/luxbet-gate.txt
if ! grep -q "DISABLED" /tmp/luxbet-gate.txt; then
    echo "LAUNCH GATE IS NOT DISABLED. Stopping the web container." >&2
    docker compose stop web
    exit 1
fi

echo "$(date -u +%FT%TZ) $tag" >> .deploy-history
docker image prune -f >/dev/null
echo "==> Deployed $tag"
