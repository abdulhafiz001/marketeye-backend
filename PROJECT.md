# Market Eye — Setup & Academic Notes

Crowd-verified Nigerian market prices for budgeting before you visit the market — plus a public developer API.

## Stack

- **Backend:** Laravel 12, Sanctum, Socialite (Google), Blade admin + public landing
- **Mobile:** Expo / React Native (`marketeye-frontend`)

## Features delivered

- Live market prices + crowdsourced submissions with admin verification
- **Airtime wallet:** ₦1 per verified submission; claim from ₦200; admin marks paid
- Google sign-in (Socialite redirect + mobile ID token)
- Welcome email on signup
- Forgot password via **6-digit email OTP**
- Public landing with market price carousels
- Rate-limited public API + `/developers` docs
- Community points / leaderboard kept separate from naira wallet

## Environment — backend (`marketeye-backend/.env`)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=your@gmail.com
MAIL_FROM_NAME="Market Eye"

GOOGLE_CLIENT_ID=your-web-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-secret
GOOGLE_REDIRECT_URI="${APP_URL}/api/v1/auth/google/callback"
# Optional native clients (must match token `aud`):
GOOGLE_ANDROID_CLIENT_ID=
GOOGLE_IOS_CLIENT_ID=
```

Run migrations after pull:

```bash
cd marketeye-backend
composer install
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```

Queue worker recommended for mail in production (`php artisan queue:work`). Local demo can use `MAIL_MAILER=log` or sync SMTP.

## Google OAuth — what you need to create

1. Open [Google Cloud Console](https://console.cloud.google.com/) → create project **MarketEye**
2. **APIs & Services → OAuth consent screen** → External → add app name + your email
3. **Credentials → Create OAuth client ID**
   - Type **Web application**
   - Authorized redirect URI: `https://YOUR_DOMAIN/api/v1/auth/google/callback` (local: `http://127.0.0.1:8000/api/v1/auth/google/callback`)
   - For native Android builds, also create an **Android** client with package name: `com.marketeye.app` (from `app.json`)
   - For iOS builds, use bundle ID: `com.marketeye.app`
4. Copy Client ID + Secret into backend `.env` as above
5. For Expo mobile, also set the **same Web client ID** (and Android/iOS clients if you build native binaries) in the frontend:

```env
EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID=....apps.googleusercontent.com
EXPO_PUBLIC_GOOGLE_ANDROID_CLIENT_ID=
EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID=
```

Mobile flow: Expo AuthSession returns a Google `id_token` → `POST /api/v1/auth/google` → Sanctum token.

Until client IDs exist, the Google button stays disabled and shows a configure hint.

## Environment — frontend

```env
EXPO_PUBLIC_API_URL=http://YOUR_PC_LAN_IP:8000
EXPO_PUBLIC_ADMIN_PANEL_URL=http://YOUR_PC_LAN_IP:8000/admin
EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID=
```

## Accounts (kept separate on purpose)

| Who | Table | Portal |
|---|---|---|
| Mobile app users | `users` | Expo app |
| Admins | `admins` | `/admin` (secret — not linked on landing) |
| Developers | `developers` | `/developer` register/login |

Same email can exist as an app user **and** an admin — they do not share the `users` table.

## Public API

- Docs: `/developers`
- Developers self-serve keys at `/developer` (max **3** active keys, default **200**/day, self-serve max **500**/day)
- Admin oversight at `/admin/api-keys` (raise/revoke limits up to 50k/day)
- Endpoints under `/api/v1/public/*` require `X-API-Key: me_…`

## Admin airtime claims

1. Approve price submissions → user wallet += ₦1
2. User claims at ≥ ₦200 (full balance held as pending claim)
3. Admin gets email → sends airtime manually → **Mark paid**
4. Reject refunds the wallet

## Academic highlights (final-year project)

| Theme | Implementation |
|---|---|
| Crowdsourcing + incentives | Verified submissions → naira wallet → airtime claim |
| Data quality | Pending → approve → snapshot; confidence / stale flags |
| Moderation & audit | Admin activity log; claim paid_by / paid_at |
| Open platform | Documented, rate-limited public API |
| Local domain fit | Nigerian units (mudu, bag, paint, kg, etc.) |
| Complete auth lifecycle | Register + welcome mail, Google OAuth, OTP reset |

World Bank / WFP catalog URL seeders were removed; prices come from community submissions and admin manual entry.
