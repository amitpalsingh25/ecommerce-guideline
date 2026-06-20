# Deploy guide — shared cPanel hosting

Vanilla PHP + PDO/MySQL. No Composer, no build step. Runs on typical shared cPanel hosting.

## Local dev
```
# start a local MySQL/MariaDB, create a database, import sql/install.sql
php -S 127.0.0.1:8090 -t public public/router.php
# http://127.0.0.1:8090   →   admin at /admin/login
```
Local DB defaults live in `app/config.php`; override them in `app/config.local.php` (gitignored).

## What to upload (FTP)

### Layout A — secure (preferred, if you can write above the web root)
- Upload the **contents of `public/`** → the web root.
- Upload **`app/`** and **`sql/`** → ONE directory ABOVE the web root.
- `index.php` auto-detects `../app`.

### Layout B — simple (everything in the web root)
- Upload `public/` contents → web root, AND `app/` + `sql/` INTO the web root too.
- `index.php` auto-detects `./app`. `app/.htaccess` + `sql/.htaccess` block direct web access.

Either way: keep `uploads/` writable (chmod 755/775).

## Database (one-time)
1. cPanel → **phpMyAdmin** → select your database.
2. **Import** → `sql/install.sql` (schema + seed data + settings).
3. Create the admin user (the seed file ships a placeholder hash):
   `php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT);"` → set it on the `users` row.

## Config (one-time, per server)
- Copy `app/config.local.example.php` → `app/config.local.php` and set:
  `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS`, `SITE_URL`, and a 64-hex **`APP_SECRET`**
  (`php -r "echo bin2hex(random_bytes(32));"`).
- Keep `APP_SECRET` **stable** across deploys, or previously-saved SMTP/Stripe secrets won't decrypt.
- On shared hosts the DB host is usually `localhost` from the server (no remote-MySQL whitelist needed).

## Go live
- Visit the domain → public site. `/admin` → log in. Change the admin password.
- **Delete `public/install.php`** after the first install.
- Enquiry/contact emails use PHP `mail()` by default; set SMTP in Admin → Settings for reliable delivery.

## Notes
- Default mode is enquiry ("Enquire for price"); enable Stripe selling later in Settings.
- Pretty URLs need `mod_rewrite` (usually on) via `public/.htaccess`.
