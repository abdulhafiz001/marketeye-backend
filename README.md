# Market Eye Backend

Laravel 12 API + Blade admin + public landing for **Market Eye** — crowd-verified Nigerian market prices.

See **[PROJECT.md](./PROJECT.md)** for:

- Google OAuth & mail setup (keys you must create)
- Airtime wallet / claim flow
- Public API & rate limits
- Academic / final-year project highlights

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
