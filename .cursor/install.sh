#!/usr/bin/env bash
# Idempotent bootstrap for the exchange-platform-hub Cloud Agent environment.
# Prepares env files, builds the Docker images, installs Composer dependencies
# and runs database migrations for both Laravel apps (admin-panel + api-service).
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

# --- Docker daemon -----------------------------------------------------------
bash "$REPO_ROOT/.cursor/ensure-docker.sh"
DC="sudo docker compose"

# --- Compose variable substitution ------------------------------------------
# Build args for the PHP/Nginx/Composer images. UID/GID 1000 match the "ubuntu"
# host user so bind-mounted writes keep working.
if [ ! -f "$REPO_ROOT/.env" ]; then
  cat > "$REPO_ROOT/.env" <<'EOF'
UID=1000
GID=1000
USER=laraveluser
EOF
fi

# --- Application env files ---------------------------------------------------
# These live under env/ and are git-ignored; generate them from the checked-in
# *.example templates and rewrite the connection settings for the Docker network
# (service DNS names + the MySQL root password from env/mysql.env).
prepare_env() {
  local example="$1" target="$2" db_name="$3"
  if [ ! -f "$target" ]; then
    cp "$example" "$target"
    sed -i \
      -e "s|^DB_HOST=.*|DB_HOST=database|" \
      -e "s|^DB_PORT=.*|DB_PORT=3306|" \
      -e "s|^DB_DATABASE=.*|DB_DATABASE=${db_name}|" \
      -e "s|^DB_USERNAME=.*|DB_USERNAME=root|" \
      -e "s|^DB_PASSWORD=.*|DB_PASSWORD=123456|" \
      -e "s|^REDIS_HOST=.*|REDIS_HOST=redis|" \
      -e "s|^REDIS_PORT=.*|REDIS_PORT=6379|" \
      "$target"
    # Ensure the shared api_system database name is set even if absent in the template.
    if grep -q "^API_SYSTEM_DB_DATABASE=" "$target"; then
      sed -i "s|^API_SYSTEM_DB_DATABASE=.*|API_SYSTEM_DB_DATABASE=api_system|" "$target"
    else
      echo "API_SYSTEM_DB_DATABASE=api_system" >> "$target"
    fi
  fi
}

[ -f env/mysql.env ] || cp env/mysql.env.example env/mysql.env
prepare_env env/admin_panel.env.example env/admin_panel.env laravel
prepare_env env/api_service.env.example env/api_service.env api_backend

# The api-service ships no migrations, so its default database-backed session,
# cache and queue stores have no tables to use. Point them at file/sync drivers
# so the service boots and serves requests in the dev environment.
if [ -f env/api_service.env ] && ! grep -q "^SESSION_DRIVER=file" env/api_service.env; then
  sed -i \
    -e "s|^SESSION_DRIVER=.*|SESSION_DRIVER=file|" \
    -e "s|^CACHE_STORE=.*|CACHE_STORE=file|" \
    -e "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=sync|" \
    env/api_service.env
fi

# --- Build images ------------------------------------------------------------
$DC build php-admin nginx-admin nginx-api composer-admin composer-api

# --- Composer dependencies ---------------------------------------------------
# --no-security-blocking: the apps pin exact framework versions (no composer.lock
# is committed), some of which now carry advisories that Composer blocks by
# default. We must not bump the apps' dependencies here, so we disable advisory
# blocking to install the versions the project actually targets.
COMPOSER_INSTALL="install --ignore-platform-reqs --no-interaction --prefer-dist --no-security-blocking"
$DC run --rm --entrypoint composer composer-admin $COMPOSER_INSTALL
$DC run --rm --entrypoint composer composer-api  $COMPOSER_INSTALL

# --- Application keys (only when missing, to stay idempotent) ----------------
generate_key() {
  local env_file="$1" artisan_service="$2"
  if ! grep -qE "^APP_KEY=base64:" "$env_file"; then
    $DC run --rm "$artisan_service" key:generate --force
  fi
}
generate_key env/admin_panel.env artisan-admin
generate_key env/api_service.env  artisan-api

# --- Database ----------------------------------------------------------------
# Bring up MySQL and wait until it accepts connections before migrating.
$DC up -d database redis
echo "Waiting for MySQL to accept connections..."
for _ in $(seq 1 60); do
  if $DC exec -T database mysqladmin ping -h 127.0.0.1 -uroot -p123456 --silent >/dev/null 2>&1; then
    echo "MySQL is ready."
    break
  fi
  sleep 2
done

# admin-panel owns all migrations (including the api_system_db connection ones).
$DC run --rm artisan-admin migrate --force

# Seed baseline data (roles, admins, currencies, markets, ...) only once. The
# seeders are not idempotent (they insert fixed primary keys), so guard on an
# empty admins table.
admin_count="$($DC exec -T database mysql -uroot -p123456 laravel -N -e \
  "SELECT COUNT(*) FROM admins;" 2>/dev/null || echo 0)"
if [ "${admin_count:-0}" = "0" ]; then
  $DC run --rm artisan-admin db:seed --force
else
  echo "Database already seeded (admins=${admin_count}); skipping db:seed."
fi

# --- Frontend assets ---------------------------------------------------------
# The admin-panel UI is rendered with Vite (Vue/Vuetify); build its assets so
# pages resolve @vite() references. PUPPETEER_SKIP_DOWNLOAD avoids fetching a
# Chromium binary that the build does not need.
$DC run --rm -e PUPPETEER_SKIP_DOWNLOAD=true --entrypoint npm npm install
$DC run --rm -e PUPPETEER_SKIP_DOWNLOAD=true --entrypoint npm npm run build

echo "Install complete."
