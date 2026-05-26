# ELHOE — Product Authenticity Verification (`/checker`)

Standalone, WordPress-independent verification portal for ELHOE luxury skincare.

- **Live URL:** `https://elhoe.com/checker`
- **Backend:** PHP 8.x (strict), PDO with prepared statements, MySQL 5.7+ / MariaDB 10.4+
- **Frontend:** HTML5 + Tailwind (CDN) + Vanilla JS, mobile-first glassmorphism UI
- **Scanner:** [`html5-qrcode`](https://github.com/mebjas/html5-qrcode) loaded from CDN
- **Bot mitigation:** Cloudflare Turnstile + IP-based rate limiter
- **Geo logging:** ip-api.com (free tier) via cURL

This app is a **drop-in folder** that lives next to your existing WordPress install.
WordPress keeps owning everything else on the domain; the portal runs entirely from
inside `public_html/checker/` with its own `.htaccess`.

---

## 1. Folder layout (after upload)

```
public_html/                     <-- your existing WordPress root
├── wp-admin/                    (WordPress)
├── wp-content/                  (WordPress)
├── ...                          (WordPress)
├── index.php                    (WordPress)
└── checker/                     <-- THIS APP
    ├── .htaccess                (security + headers)
    ├── .env                     (your DB + Turnstile secrets, NOT committed)
    ├── index.php                (the customer verification UI)
    ├── verify_action.php        (JSON endpoint that the form posts to)
    ├── assets/
    │   ├── css/site.css
    │   ├── js/site.js
    │   └── uploads/             (product images, must be writable)
    ├── admin/                   (admin dashboard, served at /checker/admin)
    │   ├── index.php
    │   ├── .htaccess
    │   ├── actions/
    │   ├── views/
    │   └── assets/
    ├── app/                     (PHP classes - protected by .htaccess)
    ├── config/                  (config.php - protected by .htaccess)
    └── database/                (schema + seed - protected by .htaccess)
```

---

## 2. Step-by-step install on Hostinger / cPanel

### 2a. Upload the files

1. In **File Manager**, open `public_html/`.
2. Create a new folder named exactly `checker` (lowercase, no trailing space).
3. Upload the contents of this `checker/` folder into `public_html/checker/`.
   Make sure hidden files (`.htaccess`, `.env.example`) are uploaded too.
4. Rename `.env.example` → `.env` (or copy):
   ```
   cp .env.example .env
   ```
   The `.env` file already contains your DB credentials. **Do not** commit `.env` back to git.
5. Set permissions:
   - Folders: `755`
   - Files: `644`
   - Ensure `assets/uploads/` is writable: `775` (or owned by the web user).

### 2b. Create the database

Create a MySQL database in **hPanel → Databases → MySQL Databases**:

| Field | Value |
|-------|-------|
| Database name | `u991123247_elhoechecker` |
| Username      | `u991123247_checker` |
| Password      | `Vent@2322` |

Then in **phpMyAdmin** (or via SSH), import the two SQL files:

```sql
-- 1. Schema
SOURCE /home/u991123247/domains/elhoe.com/public_html/checker/database/schema.sql;

-- 2. Seed (admin user + sample product)
SOURCE /home/u991123247/domains/elhoe.com/public_html/checker/database/seed.sql;
```

…or upload both files in the phpMyAdmin import tab.

### 2c. Verify it loads

- Customer portal: <https://elhoe.com/checker/>
- Admin login:    <https://elhoe.com/checker/admin/>

Default admin credentials (rotate after first login!):

```
email:    admin@elhoe.com
password: ChangeMe!2026
```

### 2d. Test code (already seeded)

Type `ELHOE-DEMO-UNIVERSAL` into the verifier — you should see the green
"Authentic ELHOE Product" card with the sample serum.

---

## 3. Security checklist before going live

- [ ] **Rotate the admin password** the very first time you log in (the seed password
      is in this README, so it is *not* a secret).
- [ ] **Rotate the database password** in hPanel and update `.env` accordingly. The
      password in this repo (`Vent@2322`) was provided during setup and should be
      changed before the URL is shared with customers.
- [ ] **Set up Cloudflare Turnstile**:
   1. Cloudflare dashboard → **Turnstile** → **Add site**.
   2. Domain: `elhoe.com`. Mode: *Managed*.
   3. Copy the **Site key** into `.env` as `TURNSTILE_SITE_KEY`.
   4. Copy the **Secret key** as `TURNSTILE_SECRET_KEY`.
   5. Reload the verifier — the Turnstile widget will appear above the button.
- [ ] **Delete the demo product** in `/checker/admin/?route=products` once you have
      created your real catalogue.
- [ ] Confirm `https://elhoe.com/checker/.env`, `/checker/app/Core/Database.php` and
      `/checker/config/config.php` all return **403 Forbidden** (proof the .htaccess
      rules are active).

---

## 4. URL contract for the WordPress storefront

When a code is valid, the API returns the product's `wordpress_url` enriched with
UTM tags so you can attribute repurchase revenue:

```
?utm_source=verify_portal&utm_medium=referral&utm_campaign=repurchase&utm_content=<product_id>
```

The "Buy Again / Restock Now" CTA and the product image/title links all use this URL.

---

## 5. Day-to-day workflow

| Task                              | Where                                                       |
|-----------------------------------|-------------------------------------------------------------|
| Add a new product                 | `/checker/admin/?route=products/edit`                       |
| Generate 1,000 unique scratch codes | `/checker/admin/?route=codes/generate`                    |
| Export a batch as CSV for printing | Codes page → filter by batch → "Export this batch (CSV) ↓"|
| Import an external batch CSV      | `/checker/admin/?route=codes/generate` → bottom of page     |
| See top counterfeit hotspots      | `/checker/admin/?route=insights/radar`                      |
| See scans by country / district   | `/checker/admin/?route=insights/geo`                        |
| Review batches expiring soon      | `/checker/admin/?route=insights/expiry`                     |
| Audit raw scan log                | `/checker/admin/?route=logs`                                |

---

## 6. Changing the URL prefix later

Everything is keyed off a single constant — `BASE_PATH` in `config/config.php`,
which reads `APP_BASE_PATH` from `.env`. If you ever want to move the app to,
say, `elhoe.com/verify` or to a subdomain, change one line in `.env`:

```
APP_BASE_PATH=/verify       # or "" for a domain root install
```

…and every link, redirect, asset path, and AJAX URL adjusts automatically.
