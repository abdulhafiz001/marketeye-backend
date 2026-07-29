#!/usr/bin/env bash
set -euo pipefail

echo "[entrypoint] Market Eye backend starting…"

# ---------------------------------------------------------------------------
# 1) Wait until MySQL accepts connections (poll, not fixed sleep)
# ---------------------------------------------------------------------------
php /usr/local/bin/wait-for-db.php

# ---------------------------------------------------------------------------
# Ensure runtime directories are writable (volume mounts may reset ownership)
# ---------------------------------------------------------------------------
mkdir -p \
  storage/app/public \
  storage/app/firebase \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwx storage bootstrap/cache || true

# ---------------------------------------------------------------------------
# 2) Migrate (non-interactive)
# ---------------------------------------------------------------------------
echo "[entrypoint] Running migrations…"
php artisan migrate --force --no-interaction

# ---------------------------------------------------------------------------
# 3) storage:link (idempotent)
# ---------------------------------------------------------------------------
if [ ! -L public/storage ] && [ ! -e public/storage ]; then
  echo "[entrypoint] Creating storage symlink…"
  php artisan storage:link --no-interaction || true
else
  echo "[entrypoint] storage link already present — skipping"
fi

# ---------------------------------------------------------------------------
# 4) Optimize caches
# ---------------------------------------------------------------------------
echo "[entrypoint] Caching config / routes / views…"
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

# ---------------------------------------------------------------------------
# 5) Seeders — NOT auto-run
#    MarketEyeSeeder is mostly firstOrCreate/updateOrCreate, but it upserts
#    admin@marketeye.ng with a known default password (admin123). That is NOT
#    safe to run automatically in production. Run manually if you need catalog data:
#      php artisan db:seed --class=Database\\Seeders\\MarketEyeSeeder --force
# ---------------------------------------------------------------------------
echo "[entrypoint] Skipping db:seed (MarketEyeSeeder not auto-run — see comment above)"

# Optional Firebase credentials hint
if [ ! -f storage/app/firebase/service-account.json ] && [ -z "${FIREBASE_CREDENTIALS:-}" ]; then
  echo "[entrypoint] WARN: Firebase service-account JSON not found — FCM pushes will fail until mounted"
fi

echo "[entrypoint] Handing off to supervisord…"
exec "$@"
