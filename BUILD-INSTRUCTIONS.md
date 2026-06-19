# Build Instructions — recreate this e-commerce platform

Audience: an AI agent or developer tasked with **building a catalogue / e-commerce website like this one
from scratch**. This is a blueprint of *what to build and why*, not usage docs. For how each finished
screen behaves, see the per-section guides in [`docs/`](docs/).

---

## 1. What you are building

A small-business **product catalogue + e-commerce** site that:

- Runs **enquiry-first**: prices hidden, products show **"Enquire for price"**, cart becomes a request
  list, checkout emails an enquiry. (No account required — guest by default.)
- Can be **switched to selling**: a toggle + Stripe keys turns on real prices and online checkout.
- Has a full **admin panel**: dashboard, hero banner, products (+variants), nested categories, media
  library, blog, enquiries, orders, email templates, event logs, settings (branding, colours, store mode,
  featured row, SMTP, Stripe, catalogue PDF).

## 2. Hard constraints (these decide the whole architecture)

> The most important section. Most design choices below exist **because of these constraints.**

- **Hosting = shared cPanel (e.g. GoDaddy).** No Node runtime, no Composer, no build step on the server,
  no root, no long-running processes. Deploy is **FTP file upload**.
- **Server PHP is old (assume 7.4).** Write modern-ish PHP but **avoid 8.0+ syntax at runtime**:
  - No `match()` → use `switch`.
  - Polyfill `str_starts_with` / `str_contains`.
  - Be careful with named args, enums, constructor promotion, nullsafe in hot paths.
- **MySQL via PDO** (the only datastore). No ORM.
- **No client build pipeline.** Ship plain CSS + vanilla JS. Any npm-built asset (e.g. the rich-text
  editor) must be **pre-bundled locally and committed as a static file** — never resolved from a CDN at
  runtime (see §13).

**Therefore:** vanilla PHP, no framework, a single front controller, server-rendered HTML strings,
progressive-enhancement JS, settings in a DB table, secrets encrypted in that table.

## 3. Stack

- PHP 8-style code that runs on PHP 7.4, PDO/MySQL.
- Server-rendered HTML (PHP functions returning strings / small view files). No template engine.
- Vanilla JS (one `app.js` + a couple of feature scripts). LocalStorage cart.
- One pre-built ESM bundle for the WYSIWYG editor (TipTap), self-hosted.
- PHPMailer (vendored, 3 files) for SMTP; Stripe via raw cURL (no SDK).

## 4. Directory layout

```
public/                 # web root
  index.php             # front controller
  .htaccess             # rewrite all → index.php
  assets/css/style.css
  assets/js/app.js richtext.js media.js tiptap-bundle.js
  uploads/              # user uploads (also the media library source)
app/                    # NOT web-served (deny via .htaccess) — or place outside web root
  config.php config.local.php   # constants; local overrides win
  bootstrap.php         # polyfills, session, require chain
  db.php                # PDO singleton + q_one/q_all/q_val/q_exec helpers
  helpers.php           # e(), url(), redirect(), csrf, respond(), setting(), enc()/dec(), save_upload()…
  auth.php              # is_admin(), require_admin(), login/logout
  repo.php              # queries: products, categories (subtree), featured, with_variants
  pages.php             # public pages (home, products, product, category, blog, about, contact)
  partials.php          # product_card, variant_table, cart_control, pagination…
  admin.php             # entire admin (dispatch + every section)
  forms.php             # enquiry/contact handlers + send_mail()
  stripe.php            # orders table, checkout session, webhook
  emails.php            # email templates + render
  lib/PHPMailer/*       # vendored mailer
  views/layout.php views/admin/layout.php
sql/install.sql         # schema + seed
```

`index.php` should **auto-detect** whether `app/` is a sibling (`../app`) or inside web root (`./app`),
so the same code works in both deploy layouts.

## 5. Routing (front controller)

- `.htaccess`: rewrite everything to `public/index.php` (except real files).
- `dispatch($path, $seg)`:
  - `seg[0] === 'admin'` → load `admin.php`, call `admin_dispatch(slice)`.
  - POST endpoints (`/stripe/checkout`, `/stripe/webhook`, enquiry, contact) → load + handle.
  - else map to a public page function.
- Wrap dispatch in try/catch → friendly maintenance page on fatal.
- `admin_dispatch` handles `login`/`logout`, then `require_admin()`, then a `switch` over the section.

## 6. Data model (MySQL)

- **products**: id, category_id (FK, nullable), sku (unique-ish), title, slug (unique), description TEXT
  (HTML), price DECIMAL nullable (NULL = "Enquire"), images JSON, status enum(draft,published),
  featured tinyint, created_at.
- **product_variants**: id, product_id FK, sku, label, price DECIMAL nullable, image VARCHAR nullable,
  position. (Add `image` via lazy migration if missing.)
- **categories**: id, parent_id (self-FK, nullable → nesting), name, slug, position, description, image.
- **blog_posts**: id, title, slug, excerpt, content TEXT (HTML), cover_image, category, author, status,
  published_at.
- **enquiries**: id, name, email, phone, items JSON, message, status enum(new,responded,closed),
  created_at.
- **orders**: id, email, name, phone, items JSON, total DECIMAL, status enum(pending,paid,failed,
  cancelled), stripe_session, created_at.
- **settings**: `key` PK, `value` JSON (the whole config system).
- **logs**: id, level enum(info,warn,error), category, message, meta JSON, created_at.

## 7. Settings system (central to everything)

- One `settings` table; `setting($key, $default)` reads + json-decodes, `set_setting($key, $value)`
  json-encodes + upserts.
- Group keys: `general` (branding, logo size, catalogue PDF), `theme` (brand colours), `toggles`
  (sellingEnabled / enquiryEnabled / guestCheckout), `featured`, `smtp`, `stripe`, `emails`,
  `media_meta`, `hero`.
- Typed accessors with defaults: `branding()`, `theme()`, `toggles()`, `featured_cfg()`, `hero()`,
  `smtp_settings()`, `stripe_settings()`, `payments_enabled()`.
- **Secrets encrypted at rest**: `enc()/dec()` AES-256-GCM with a key constant. Encrypt SMTP password,
  Stripe secret key, webhook secret. Never echo secrets back to the browser; blank field = keep existing.

## 8. Core helpers to build first

`e()` (htmlspecialchars), `url()` (base-path aware), `redirect()`, `csrf_token/csrf_field/csrf_check`
(field name `_csrf`), `respond()` / `respond_admin()` (wrap content in the public/admin layout),
`setting/set_setting`, `enc/dec/is_encrypted`, `save_upload()` (image validation + move to uploads),
`save_pdf()`, `log_event()`, `slugify()`, `money()`, `json_arr()`.

## 9. Public site

- **Home**: hero (see §12 hero), category directory, **featured products** (carousel when count >
  cards-per-row), how-it-works, about, CTA, blog teasers. Built with a heredoc — **define every
  interpolated `{$var}` before the heredoc** (heredoc interpolates, so undefined tokens render blank).
- **Products**: filter/search/sort + pagination; `product_card()`.
- **Product detail**: gallery (product + variant images, thumbnails, click-to-swap), variant table
  (size/part-no/price/add; row click swaps gallery image), price-or-enquire, rich-text description.
- **Category**: resolve nested slug path → breadcrumbs + sub-categories + products (filter by full
  sub-tree via `subtree_ids()`).
- **Blog** list + post. **About**, **Contact** (form → email).
- **Cart**: localStorage, side drawer, quantity steppers, badge — all client JS.

## 10. Admin panel

Auth (single admin via credentials; session). Then one function per section in `admin.php`
(see the `docs/` guides for exact fields & behaviour):
dashboard, hero, products (+variant editor), categories (tree), media library, blog, enquiries, orders,
emails, event logs, settings. Each editor form posts to itself; settings cards each carry a `_section`
and save independently.

## 11. Media pipeline

- Upload validates type/size, writes to `/uploads`, returns a public URL. The media library just lists
  that folder.
- **Client-side compression before upload**: canvas downscale to ≤1000px + re-encode to WebP ~0.85.
- **Reusable picker modal** (`media.js`): grid + search + drag/drop upload + details; returns a URL into
  any hidden image field. Used for product main image and each variant image.
- **Attachment details**: title/alt (stored in `media_meta` setting), dimensions, "used by", and server
  **GD** edit (rotate/flip/scale/crop) that rewrites the file in place so URLs stay valid. Guard for
  missing GD.

## 12. Hero

Two modes saved under `hero`: **slideshow** (slides with image/eyebrow/heading/accent/sub/2 buttons/align
+ transition/speed/autoplay/padding/type sizes) and **single image** (one banner, no overlay). Admin has
a radio to switch; render branches on mode. Slides cross-fade/slide with dots+arrows; sizes via CSS vars.

## 13. Rich text editor (important pitfall)

- Use TipTap, but **do not import from a CDN like esm.sh at runtime** — it resolves a large dependency
  graph on the fly = slow first load.
- Instead: `npm i @tiptap/core @tiptap/starter-kit @tiptap/extension-link @tiptap/pm` (pin all to the
  **same** 2.x version to avoid export mismatches), write a tiny `entry.js` that re-exports
  `Editor, StarterKit, Link`, and `esbuild --bundle --format=esm --minify` into a single
  `tiptap-bundle.js`. Commit that file and import it locally from `richtext.js`. One request, one
  ProseMirror instance, fast.

## 14. Payments (optional)

- Stripe keys in settings (encrypted). `payments_enabled()` = selling on + stripe enabled + secret set.
- Checkout: create a `pending` order with line items **re-priced from the DB**, create a Stripe Checkout
  Session via cURL, redirect. Webhook verifies the signature, marks the order `paid`, sends order emails.

## 15. Emails

- Templates defined in code with defaults; admin overrides merged from `emails` setting; each has an
  enabled flag. Placeholders (`{name}`,`{items}`,`{total}`,…) replaced at send; `{items}` builds a table.
- Wrap in a branded HTML shell (logo/colour from settings). Send via PHPMailer SMTP when configured, else
  `mail()`. Disabled template = send nothing.

## 16. Frontend assets

- `style.css`: design tokens as CSS custom properties (brand colours injected from settings via an inline
  `:root` style block so the admin can recolour the site). Components: header/nav, cards, variant table,
  cart drawer, carousel, media modal, admin tables/forms.
- `app.js`: cart store + drawer, hero slideshow, featured carousel, card swatches, scroll reveal.

## 17. Deployment workflow

- **FTP upload** files to the web root. Two layouts both supported via the `index.php` auto-detect.
- `UPLOAD_DIR = $_SERVER['DOCUMENT_ROOT'] . '/uploads'` so it matches the public `/uploads` URL.
- Protect `app/` with `.htaccess` deny (or keep it outside the web root).
- Before every deploy: `php -l` each changed file. Test locally with `php -S`.
- First-time DB: run `sql/install.sql` (via a one-shot installer that connects over localhost on the
  server, then delete it) — remote MySQL is usually blocked on shared hosts.

## 18. Security checklist

- CSRF token on every state-changing POST.
- Escape all output with `e()`.
- Re-price carts server-side; never trust client prices.
- Verify Stripe webhook signatures.
- Encrypt secrets at rest; never return them to the browser.
- Deny direct web access to `app/`. Keep credentials out of the repo.

## 19. Suggested build order

1. Front controller + routing + layout + config/db/helpers.
2. Schema + seed; repo queries.
3. Public catalogue (home, products, product, category) in enquiry mode.
4. Cart (JS) + checkout → enquiry email.
5. Admin auth + dashboard + product/category/blog CRUD.
6. Settings system (branding, colours, toggles) + media library + picker.
7. Emails (templates + SMTP) ; hero ; featured carousel.
8. Payments (Stripe) behind the selling toggle.
9. Polish: SEO/meta, logs, deploy.

## 20. Lessons / gotchas (save yourself the pain)

- PHP 7.4: no `match`; polyfill `str_*`; test on the real PHP version.
- Heredoc interpolates `{$var}` — define all tokens first or you get blank output.
- Upload path must equal the served path (`DOCUMENT_ROOT/uploads`), or images 404.
- Don't runtime-load big JS from a CDN module resolver — pre-bundle and self-host.
- FTP scripts: list files explicitly (shells like zsh don't word-split unquoted vars), use relative
  paths for RNFR/RNTO.
- Fine-grained GitHub tokens need **Contents: Read and write** *and* the repo in their access list.

---

See [`CONTRIBUTING.md`](CONTRIBUTING.md) for how to keep this blueprint and the guides up to date.
