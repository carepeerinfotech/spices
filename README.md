# Elephant Shop

Laravel 13 ecommerce storefront + admin with variants, verified checkout, Paytm, Shiprocket, and manageable homepage.

## Requirements

- PHP 8.3+
- Composer
- MySQL
- No npm build required (Tailwind CDN + vanilla JS)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate

# Create MySQL database elephant_ecom, then:
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8015
```

## Default accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@elephant.com | password |
| Customer (verified) | customer@elephant.com | password |

- Store: http://127.0.0.1:8015/
- Admin: http://127.0.0.1:8015/admin/login

## Features

- Product variants, gallery uploads, shipping dimensions, COD/online flags
- Category picture + banner
- Customer register/login, email verification, addresses, password change
- Session cart for guests, DB cart merge on login
- Verified checkout with shipping/billing addresses
- Pincode shipping quotes (Shiprocket fake/live)
- Paytm fake/live payment flow
- Email templates + SMTP settings
- Shiprocket manual fulfillment (create order, AWB, pickup, track, cancel)
- Global settings (payment modes, notifications, shipping charges)
- Homepage CMS sections

## Integrations

Configure under **Admin → Settings**:

- Paytm: set driver `fake` (default) or `live`, merchant id/key, staging/production
- Shiprocket: enable, driver `fake`/`live`, API email/password, pickup location
- Email: SMTP host/port/user/password/from
- Webhooks/callbacks:
  - Paytm callback: `POST /payments/paytm/callback`

## Testing

```bash
php artisan test
```

Uses SQLite in-memory (`phpunit.xml`). Fake Paytm/Shiprocket drivers keep tests offline.

## Notes

- Checkout requires authenticated + email-verified customers
- Shipments are created only when an admin clicks **Send to Shiprocket**
- Secrets in settings are encrypted at rest and masked in the UI
