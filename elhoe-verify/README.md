# ELHOE — Product Authenticity Verification

Standalone, WordPress-independent verification portal for the ELHOE luxury skincare brand.

- **Backend:** PHP 8.x (strict), PDO with prepared statements, MySQL 5.7+ / MariaDB 10.4+
- **Frontend:** HTML5 + Tailwind (CDN) + Vanilla JS, mobile-first glassmorphism UI
- **Scanner:** [`html5-qrcode`](https://github.com/mebjas/html5-qrcode) loaded from CDN
- **Bot mitigation:** Cloudflare Turnstile + IP-based rate limiter
- **Geo logging:** ip-api.com (free tier) via cURL

Designed to live at `verify.elhoe.com` (subdomain) or `elhoe.com/verify` (subfolder), fully isolated from the WordPress storefront.

---

## 1. Directory layout

```
elhoe-verify/
├── config/config.php          # Environment, DB, Turnstile keys
├── database/{schema,seed}.sql # MySQL schema + default admin user
├── app/
│   ├── Core/                  # Database, Auth, Helpers, RateLimiter, GeoIP
│   └── Services/              # Turnstile, CodeGenerator
├── public/                    # Document root for verify subdomain
│   ├── index.php              # Verification UI
│   ├── verify_action.php      # JSON endpoint
│   └── assets/                # css, js, uploads
└── admin/                     # Admin dashboard (protected by .htaccess + Auth)
    ├── index.php              # Front controller / router
    ├── actions/               # Mutating endpoints (POST handlers)
    └── views/                 # Server-rendered pages
```

---

## 2. Install

1. Create a MySQL database, e.g. `elhoe_verify`.
2. Import the schema and seed:
   ```bash
   mysql -u USER -p elhoe_verify < database/schema.sql
   mysql -u USER -p elhoe_verify < database/seed.sql
   ```
3. Copy `.env.example` to `.env` and fill in:
   - DB credentials
   - `APP_BASE_URL` (e.g. `https://verify.elhoe.com`)
   - Cloudflare Turnstile site key + secret
   - SMTP / mail (optional)
4. Point your web server (Apache/Nginx) document root at `public/`. The `admin/` folder is intentionally **outside** the public root in shared-hosting layouts; on a standard cPanel install put `public/*` at the domain root and keep `admin/` accessible via `/admin` alias or subdomain.
5. Make `public/assets/uploads/` writable (`chmod 775`).

### Default admin login (CHANGE IMMEDIATELY)

```
email:    admin@elhoe.com
password: ChangeMe!2026
```

After first login open `/admin/?route=settings` to rotate the password.

---

## 3. Cloudflare Turnstile setup

1. In the Cloudflare dashboard go to **Turnstile** → **Add site**.
2. Domain: `verify.elhoe.com` (or your hostname). Mode: *Managed*.
3. Copy the **Site key** into `.env` as `TURNSTILE_SITE_KEY`.
4. Copy the **Secret key** as `TURNSTILE_SECRET_KEY`.
5. The frontend already injects `<div class="cf-turnstile" data-sitekey="...">`. The backend (`verify_action.php`) calls `siteverify` before any DB lookup. If the secret is empty Turnstile is bypassed (useful for local dev only).

---

## 4. Rate limiter

Configured via `RATE_LIMIT_MAX` and `RATE_LIMIT_WINDOW_SEC` in `.env`. Default: **5 invalid codes per 600 seconds per IP** → HTTP `429`. Counts only invalid attempts so legitimate repeat-buyers are never blocked.

---

## 5. URL contract for the WordPress storefront

When a code is valid, the API returns the product's `wordpress_url` already enriched with UTM tags:

```
?utm_source=verify_portal&utm_medium=referral&utm_campaign=repurchase&utm_content=<product_id>
```

The "Buy Again / Restock Now" CTA and the product image/title links all use this URL.

---

## 6. Cron (optional)

A simple LAMP cron to purge >180-day raw scan logs while keeping aggregated stats:

```
0 3 * * *  /usr/bin/php /var/www/elhoe-verify/crons/prune_logs.php
```

(Provided as a stub — not required for MVP.)
