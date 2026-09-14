# Market Eye Backend

Laravel 12 API + Blade admin + public landing for **Market Eye** — crowd-verified Nigerian market prices.

## Highlights

- Token authentication & mail setup (see `.env.example`)
- Airtime wallet / claim flow
- Public API + OpenAPI / Swagger / Postman (`/developers`, `/developers/swagger`)
- Server-side price alerts via **FCM HTTP v1** (service account) + Expo fallback
- Admin analytics (`/admin/analytics`)
- Batch offline submissions (`POST /api/v1/submissions/batch`)

### Push notifications

1. Put Firebase service account at `storage/app/firebase/service-account.json`
2. Set `FIREBASE_PROJECT_ID=marketeye-f8498` in `.env`
3. Run `php artisan migrate` (adds `users.fcm_device_token`)
4. Keep `php artisan queue:work` running
5. After a user signs in on a **dev build**, test with:

```bash
php artisan push:test user@example.com
```

## Quick start

```bash
composer install
cp .env.example .env   # if needed
php artisan key:generate
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```

- Landing: `/`
- Developer docs: `/developers`
- Admin: `/admin`
