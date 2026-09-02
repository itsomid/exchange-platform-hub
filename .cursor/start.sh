#!/usr/bin/env bash
# Per-boot startup for the exchange-platform-hub Cloud Agent environment.
# Ensures the Docker daemon is up, then starts the serving stack:
#   - admin-panel  -> http://localhost:8000
#   - api-service  -> http://localhost:8001
#   - MySQL, Redis, and the queue workers for both apps.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

bash "$REPO_ROOT/.cursor/ensure-docker.sh"

# Ensure MySQL is healthy, migrated and seeded (reinitializes the data dir when
# it was restored from an overlay snapshot and cannot be reopened).
sudo docker compose up -d redis
bash "$REPO_ROOT/.cursor/db-setup.sh"

sudo docker compose up -d \
  database \
  redis \
  php-admin \
  nginx-admin \
  php-api \
  nginx-api \
  admin-queue \
  api-queue

echo "Serving stack is up:"
echo "  admin-panel -> http://localhost:8000"
echo "  api-service -> http://localhost:8001"
