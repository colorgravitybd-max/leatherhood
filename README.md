# LeatherHood — Custom PHP 8 E-Commerce Engine

Production-ready, no-Composer custom commerce platform for **leatherhoodbd.com**, designed for Hostinger Shared Hosting (LiteSpeed). Replaces the WordPress + WooCommerce stack with a fully owned, OOP-PHP / PDO / vanilla-JS codebase.

## What's inside

| Module | Files |
|---|---|
| Master schema (15 tables) | `database/schema.sql` |
| BD Geo seed (8 / 64 / 100+ thanas) | `database/seeds/02_geo_bd.sql` |
| Settings + demo catalog seeds | `database/seeds/01_settings.sql`, `03_demo_catalog.sql` |
| Core (PDO, Auth, Router, Helpers, Bootstrap) | `app/Core/` |
| Services (Checkout w/ row-locking, OTP, SMS, Mailer, Meta CAPI, Cloudflare, Couriers, bKash, ImageOptimizer, JWT, PDF) | `app/Services/` |
| Storefront (Home / Shop / Product / Cart / Checkout / Pages / Thankyou / 404) | `public/views/` |
| Storefront router + JSON APIs | `public/index.php` |
| JWT REST API for mobile APK | `public/api/v1/index.php` |
| Glassmorphism Light Admin | `admin/` |
| Cron jobs | `crons/` |

## Highlights

- **Row-locking checkout** (`app/Services/CheckoutService.php`) — `SELECT ... FOR UPDATE` per SKU inside a single PDO transaction, with deterministic ordering to avoid deadlocks. Two simultaneous buyers for the last unit ⇒ one succeeds, one gets a clean "Out of Stock" error. Cancellation auto-restocks via inventory ledger.
- **OTP fake-order killer** — generated on order placement, sent via SMS gateway, hashed in `otp_codes`, with attempts counter, TTL, and a manual admin verify override.
- **Auto-WebP optimizer** — Imagick-preferred / GD fallback. Scales, compresses, converts, deletes the raw upload, records `compression_ratio` and shows "Saved 4.8 MB" toast in admin.
- **Cascading geo dropdowns** — Vanilla JS hits `/api/geo/divisions|districts/{id}|police-stations/{id}`, then `/api/checkout/quote` to recalc subtotal+shipping+grand live.
- **Cloudflare** — Frosted-glass "Purge Cloudflare Cache" button in the admin topbar, plus auto-purge of the specific product URL on every product save.
- **Meta CAPI** — Server-side `Purchase` event fired after OTP verification, hashed PII, dedup `event_id` matching the Pixel.
- **Couriers** — One-click push for SteadFast and Pathao; consignment ID + tracking URL written back to the order.
- **Bulk invoicing** — Select N orders → `/admin/invoices/bulk?ids=…` opens a single self-print HTML/PDF with packing slips + barcodes.
- **JWT REST API v1** — `/api/v1/auth/login`, `/orders`, `/orders/{id}/status`, `/inventory/scan` for the warehouse APK to deduct/add stock by barcode.
- **Crons (Hostinger Cron Jobs)**:
  - `crons/transactional_emails.php` — runs every 5–15 min, sends confirmation, shipped, delivered emails.
  - `crons/cart_abandonment.php` — runs every 30–60 min, nudges pending-OTP carts via SMS, marks 24h-old as abandoned (with restock), recovers `abandoned_carts` rows.
- **RBAC** — `super_admin`, `admin`, `moderator`, `support` with wildcard permission matrix (`Auth::can('orders.update')`).
- **Marketing scripts hub** — admin table to inject GTM / Meta Pixel / MS Clarity / TikTok / custom tags into head/body/footer without redeploying.

## Install on Hostinger

1. Upload the repo into `~/domains/leatherhoodbd.com/`. Set the **Document Root** to `~/domains/leatherhoodbd.com/leatherhood/public`.
2. In hPanel → MySQL Databases, create a database + user. Note the host / db / user / pass.
3. Open the file manager: copy `.env.example` to a real `.env` next to it; fill credentials. (Or set the matching values via hPanel → Advanced → Environment Variables.)
4. In phpMyAdmin, import in this order:
   - `database/schema.sql`
   - `database/seeds/01_settings.sql`
   - `database/seeds/02_geo_bd.sql`
   - `database/seeds/03_demo_catalog.sql` (optional demo products & pages)
5. Visit `https://leatherhoodbd.com/admin/login` and sign in:
   - Email: `admin@leatherhoodbd.com`
   - Password: `ChangeMe!2026` *(replace immediately from Team & RBAC)*
6. **Cron jobs** (hPanel → Advanced → Cron Jobs):
   ```
   */15 * * * *  php ~/domains/leatherhoodbd.com/leatherhood/crons/transactional_emails.php > /dev/null 2>&1
   */45 * * * *  php ~/domains/leatherhoodbd.com/leatherhood/crons/cart_abandonment.php   > /dev/null 2>&1
   ```
7. Optional: connect Cloudflare (zone ID + API token in `.env`), turn on `CF_ENABLED=true`.

## Default admin login

> **WARNING:** the default admin password is a placeholder. Change it on first login from **Team & RBAC**, or run a fresh `password_hash()` for `users.password`.

## Folder map

```
leatherhood/
├── public/                ← document root
│   ├── index.php          ← storefront router + JSON APIs
│   ├── .htaccess
│   ├── api/v1/            ← JWT REST API for the APK
│   ├── assets/{css,js,images,uploads}
│   └── views/             ← home, shop, product, cart, checkout, page, thankyou, 404
├── admin/                 ← Glassmorphism Light Theme console
│   ├── index.php
│   ├── views/             ← dashboard, orders, products, customers, pages, reviews, geo, users, marketing, settings
│   └── actions/           ← cloudflare, courier, order_*, media_upload, invoices_bulk, geo_update
├── app/
│   ├── Core/              ← Bootstrap, Database, Auth, Router, Helpers
│   └── Services/          ← Checkout, Otp, Sms, Mailer, MetaCAPI, Cloudflare, Courier, Bkash, ImageOptimizer, Jwt, PdfInvoice, Cart, Shipping, Http
├── config/config.php
├── crons/                 ← transactional_emails.php, cart_abandonment.php
├── database/              ← schema.sql + seeds
└── storage/               ← logs, sessions, cache, pdf  (write-able)
```

## Currency

Every monetary value is rendered with `Helpers::bdt(...)` → `৳1,490`. The schema stores `currency = 'BDT'`.

## Tech stack

- PHP **8.2+** OOP, `declare(strict_types=1)`, namespaced PSR-4-style autoloader.
- **PDO MySQL** with prepared statements only; nested transactions via savepoints.
- **MySQL 8 / MariaDB 10.4+** with `InnoDB`, `utf8mb4_unicode_ci`.
- **Vanilla JS / CSS** — no jQuery, no React.
- **Composer optional** — Imagick, GD, cURL only need to be enabled in PHP (Hostinger has them by default).

---

© LeatherHood. All rights reserved.
