# Demo — the live reference implementation

This is the **actual working code** for the platform (vanilla PHP + PDO, no build step). It's here so the
file structure is concrete and you can **edit it and re-publish** to the site. Nothing is stripped.

> ⚠️ This repo is **private** and `app/config.php` contains the real `APP_SECRET` (it decrypts stored
> SMTP/Stripe secrets). Keep the repo private. If it ever becomes public, **rotate `APP_SECRET`** and
> re-enter the SMTP/Stripe secrets in admin. Real server DB credentials live in `app/config.local.php`,
> which is **not** committed.

## File structure

```
demo/
├─ public/                        # WEB ROOT (point the domain / php -S here)
│  ├─ index.php                   # front controller (routes everything)
│  ├─ router.php                  # router for `php -S` local dev
│  ├─ install.php                 # one-shot DB installer — DELETE after running
│  ├─ .htaccess                   # rewrite all → index.php
│  └─ assets/
│     ├─ css/style.css            # all styles + design tokens (:root)
│     ├─ js/app.js                # cart, hero slideshow, featured carousel, card swatches
│     ├─ js/media.js              # media library + picker modal + attachment editor
│     ├─ js/richtext.js           # WYSIWYG init (imports tiptap-bundle.js)
│     ├─ js/tiptap-bundle.js      # self-hosted TipTap (no CDN)
│     ├─ logo.png  favicon.ico  certified.png
│     └─ uploads/                 # user uploads + media library source (gitignored)
├─ app/                           # APPLICATION (kept out of web root; denied via .htaccess)
│  ├─ config.php                  # constants + defaults (APP_SECRET, brand, DB dev defaults)
│  ├─ config.local.example.php    # copy → config.local.php on each server (DB creds, SITE_URL)
│  ├─ bootstrap.php               # polyfills, session, require chain
│  ├─ db.php                      # PDO singleton + q_one/q_all/q_val/q_exec
│  ├─ helpers.php                 # e(), url(), csrf, respond(), setting(), enc()/dec(), uploads…
│  ├─ auth.php                    # is_admin(), require_admin(), login/logout
│  ├─ repo.php                    # product/category/featured queries (incl. subtree_ids)
│  ├─ pages.php                   # public pages (home, products, product, category, blog, about, contact)
│  ├─ partials.php                # product_card, variant_table, cart_control, pagination…
│  ├─ admin.php                   # entire admin panel (dispatch + every section)
│  ├─ forms.php                   # enquiry/contact handlers + send_mail()
│  ├─ stripe.php                  # orders table, checkout session, webhook
│  ├─ emails.php                  # email templates + render
│  ├─ views/layout.php            # public layout (header/nav/footer)
│  ├─ views/admin/layout.php      # admin shell (sidebar)
│  └─ lib/PHPMailer/              # vendored mailer (3 files)
├─ sql/
│  ├─ schema.sql                  # tables
│  ├─ install.sql                 # schema + seed
│  └─ migrate_from_prisma.sql     # one-off data migration (historical)
└─ DEPLOY.md                      # deploy notes
```

## Run locally

1. Create a MySQL database; import `sql/install.sql` (or `schema.sql`).
2. `cp app/config.local.example.php app/config.local.php` and set `DB_*` (and `SITE_URL` if needed).
3. Serve: `php -S localhost:8000 -t public public/router.php`
4. Open `http://localhost:8000/` ; admin at `/admin/login`.

> Requires only PHP (7.4+) with PDO + GD. No Composer, no npm to run it.

## Re-publish to the live site

- FTP the contents: `public/*` → web root; `app/` and `sql/` alongside (protected by their `.htaccess`).
- On the server, create `app/config.local.php` with the **server** DB credentials + `SITE_URL`.
- `UPLOAD_DIR` auto-resolves to `DOCUMENT_ROOT/uploads`.
- **Delete `public/install.php`** after the first install.
- Keep `APP_SECRET` **identical** across deploys, or previously-saved SMTP/Stripe secrets won't decrypt.
- Before uploading changed PHP: `php -l <file>`.

## Rebuilding the TipTap bundle (only if you change editor deps)

```
npm i @tiptap/core@^2 @tiptap/starter-kit@^2 @tiptap/extension-link@^2 @tiptap/pm@^2 esbuild
# entry.js re-exports Editor, StarterKit, Link
npx esbuild entry.js --bundle --format=esm --minify --outfile=public/assets/js/tiptap-bundle.js
```

See the per-section build specs in [`../docs/`](../docs/) for how each screen works.
