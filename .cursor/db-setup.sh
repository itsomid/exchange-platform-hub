#!/usr/bin/env bash
# Bring up MySQL and make sure it is healthy, migrated and seeded.
#
# Why the reinit dance: the dbdata volume is captured in the environment build
# snapshot. When a fresh agent boots, those InnoDB files live on the snapshot's
# overlay lower layer, and mysqld cannot reopen them there (InnoDB file ops fail
# with "OS error 22 / 122" on overlayfs). Fresh initialization in the writable
# layer works fine, so when MySQL fails to come up we recreate its data
# directory from scratch and re-apply migrations + seeders. Within a running
# agent (volume already in the writable layer) MySQL stays healthy and data is
# preserved across restarts.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

DC="sudo docker compose"
PROJECT="$(basename "$REPO_ROOT")"
DB_VOLUME="${PROJECT}_dbdata"

wait_for_mysql() {
  local timeout="$1"
  for _ in $(seq 1 "$timeout"); do
    if $DC exec -T database mysqladmin ping -h 127.0.0.1 -uroot -p123456 --silent >/dev/null 2>&1; then
      return 0
    fi
    sleep 1
  done
  return 1
}

$DC up -d database

echo "Waiting for MySQL to accept connections..."
if ! wait_for_mysql 60; then
  echo "MySQL did not become healthy; reinitializing its data directory (stale overlay snapshot)..."
  $DC rm -fsv database >/dev/null 2>&1 || true
  sudo docker volume rm "$DB_VOLUME" >/dev/null 2>&1 || true
  $DC up -d database
  if ! wait_for_mysql 120; then
    echo "ERROR: MySQL still not healthy after reinit. Recent logs:" >&2
    $DC logs --tail 40 database >&2 || true
    exit 1
  fi
fi
echo "MySQL is ready."

# Apply migrations (idempotent) and seed once (guarded on an empty admins table).
$DC run --rm artisan-admin migrate --force

admin_count="$($DC exec -T database mysql -uroot -p123456 laravel -N -e \
  "SELECT COUNT(*) FROM admins;" 2>/dev/null || echo 0)"
if [ "${admin_count:-0}" = "0" ]; then
  $DC run --rm artisan-admin db:seed --force
else
  echo "Database already seeded (admins=${admin_count}); skipping db:seed."
fi
