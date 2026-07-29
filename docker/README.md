# Docker / Coolify

Production image: nginx + php-fpm + queue worker + `schedule:work` via supervisord.

## Local test

```bash
docker compose up --build
curl -i http://localhost:8080/up
```

## Coolify

1. New resource → Dockerfile (context = this backend repo)
2. Port **80**
3. Attach managed MySQL (or external) and set env vars (see main README summary)
4. Mount Firebase JSON to `storage/app/firebase/service-account.json` (or set `FIREBASE_CREDENTIALS`)
5. Generate a real `APP_KEY` (`php artisan key:generate --show`) — do not reuse the compose placeholder

## First admin

Do **not** rely on a password seeder. After deploy, open:

`https://your-domain.com/setup`

Create the first admin (page disables itself once an admin exists). Then use `/admin/login`.

Optional catalog seed only (markets/products — not the admin account):

```bash
php artisan db:seed --class=Database\\Seeders\\MarketEyeSeeder --force
```

