# Build spec — Emails

> Tokens, layout & structure reference: [design-system.md](design-system.md).
> Picker internals: [media-gallery.md](media-gallery.md).

**Goal:** editable notification templates with a branded HTML wrapper, live preview, and SMTP delivery.

> **Changed in 2026-09.** Templates gained a second authoring mode: a **drag-and-drop designer** that
> produces a whole email document, alongside the original heading+body form. The classic mode below is
> unchanged and stays the default — read *Template kinds* onward for the new path.

## Routes
- `GET /admin/emails` — list. `GET/POST /admin/emails/{id}` — edit/save.
- `POST /admin/emails/{id}/preview` (and/or GET) — render preview HTML (works even if disabled, with
  sample data; accepts unsaved heading/body for live preview).

## Data
- Defaults defined in code (`email_defaults()`), keyed by id, each: label, to(admin|customer), subject,
  heading, body, with placeholder tokens.
- Overrides + enabled flag stored in `setting('emails')`, merged over defaults by `email_template($id)`.
- Templates to ship: `enquiry_admin`, `enquiry_customer`, `contact_admin`, `order_admin`, `order_customer`.

## Build — list
Table: label, recipient (admin address or "Customer"), Status On/Off, Manage.

## Build — edit
Fields: Enable checkbox, Recipient (read-only), Subject, Heading, **Body** `<textarea class="richtext">`.
Show placeholder list. **Live preview**: full-width iframe; JS posts current heading+body to the preview
route (debounced/polled) → `srcdoc`, so it updates as you type. Save persists override under `emails`.

## Build — rendering
- `email_replace($tpl,$vars)`: replace `{name}{email}{phone}{message}{order}{total}{site}{phone_co}
  {email_co}` (escaped, nl2br); `{items}` → an HTML products table.
- `email_wrap(heading,bodyHtml)`: branded shell (logo + brand colour from settings, accent bar, footer).
- `render_email($id,$vars,$force=false)`: return `{subject,html}` or null if disabled (unless `$force`
  for preview). Inject site/contact vars.

## Build — sending & wiring
- `send_mail()`: PHPMailer SMTP when `smtp_settings()` present, else `mail()` fallback.
- Wire events: enquiry submit → `enquiry_admin` + `enquiry_customer`; contact form → `contact_admin`;
  order paid (webhook) → `order_admin` + `order_customer`. Disabled template = send nothing.

---

# Visual template builder

**Goal:** let an admin design a notification email by dragging rows, columns, images and buttons, pick
pictures from the existing media library, and have the result sent by the same `render_email()` /
`send_mail()` path the classic templates use.

## Template kinds

One discriminator decides how a template is authored **and** how it renders. Store it per template as
`editor`:

| `editor` | Authoring | Stored | Rendering |
|----------|-----------|--------|-----------|
| `classic` *(default)* | Heading + rich-text body | `heading`, `body` fragments | `email_wrap()` adds the branded shell |
| `design` | Drag-and-drop designer | `design_json` (source of truth) + `body_html` (derived) | `body_html` sent **as-is** — no wrapper |
| `html` | Paste existing markup | `body_html` only | `body_html` sent **as-is** — no wrapper |

The rule that keeps this honest: **a designed template already contains its own `<html>`, header and
footer, so it must not be wrapped again.** `email_wrap()` applies to `classic` only. Getting this wrong
produces a doubled header and a nested `<html>` that Outlook renders as plain text.

`body_html` for a `design` template is a **derived cache** — regenerate it from `design_json` on every
save so the two cannot drift. Never hand-edit it.

## Data — designs do not fit in `setting('emails')`

`settings.value` is `TEXT` (64 KB) and `setting('emails')` holds **every** template's override in one
JSON blob. A single exported design runs 50–200 KB. In MySQL's default non-strict mode an oversized
value is **silently truncated**, which corrupts every template at once, not just the one being saved.

Give designed templates their own table:

```sql
CREATE TABLE IF NOT EXISTS email_templates (
  id          VARCHAR(64) PRIMARY KEY,   -- matches the email_defaults() key
  editor      VARCHAR(20)  NOT NULL DEFAULT 'classic',
  subject     VARCHAR(255) NULL,
  design_json LONGTEXT     NULL,         -- source of truth for editor='design'
  body_html   LONGTEXT     NULL,         -- derived for 'design', authored for 'html'
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Keep `enabled`, and the classic `heading`/`body`, where they already live in `setting('emails')`.
`email_template($id)` merges three layers, last wins: code defaults → `setting('emails')` →
`email_templates` row. Absent row = classic template, so **no migration and no backfill** — existing
installs keep working untouched.

Validate `editor` against an explicit whitelist on save (`classic|design|html`, anything else →
`classic`). A `VARCHAR` column plus non-strict MySQL will otherwise accept a typo and store it, and the
renderer then silently falls through to the wrong branch. Do not use an `ENUM` — adding a fourth mode
later means an `ALTER`, and an out-of-range `ENUM` write stores `''` without erroring.

## Build — the designer

Use [Unlayer](https://github.com/unlayer/react-email-editor) (`embed.js`). No build step, no npm — it
drops into an admin page as a script tag, which suits this stack.

**Know what you are adopting:** only the wrapper is MIT. The editor UI itself is fetched from
`editor.unlayer.com` at runtime, so authoring needs a live connection to a third party and a CSP that
allows it. Sending is unaffected — the exported HTML is yours and lives in your database.

```html
<div id="email-designer" style="min-height:78vh"></div>
<script src="https://editor.unlayer.com/embed.js"></script>
<script>
unlayer.init({
  id: 'email-designer',
  displayMode: 'email',
  appearance: { theme: 'modern_dark' },
  // The platform's own {token} syntax, so email_replace() needs no changes.
  mergeTags: {
    name:     { name: 'Customer name', value: '{name}' },
    email:    { name: 'Customer email', value: '{email}' },
    order:    { name: 'Order number',  value: '{order}' },
    total:    { name: 'Order total',   value: '{total}' },
    site:     { name: 'Site name',     value: '{site}' },
    email_co: { name: 'Company email', value: '{email_co}' },
    phone_co: { name: 'Company phone', value: '{phone_co}' }
  },
  tools: { button: { properties: { backgroundColor: { value: BRAND_COLOUR } } } }
});

if (SAVED_DESIGN) unlayer.loadDesign(SAVED_DESIGN);

// Uploads go to the platform's own endpoint, so images never land on a third-party CDN.
unlayer.registerCallback('image', function (file, done) {
  var fd = new FormData();
  fd.append('file', file.attachments[0]);
  fd.append('_csrf', CSRF);
  fetch(URL_ADMIN_UPLOAD, { method: 'POST', body: fd })
    .then(function (r) { return r.json(); })
    .then(function (j) { if (j.url) done({ progress: 100, url: j.url }); });
});
</script>
```

Save posts both halves — `unlayer.exportHtml(function (d) { /* d.design, d.html */ })` — writing
`design_json = d.design` and `body_html = d.html`.

`{items}` (the products table) has no visual equivalent. Expose it as a **merge tag placed in its own
text block**; `email_replace()` swaps it for the table at send time. Say so in the placeholder list, or
an admin will try to build the table by hand.

## Build — attaching the media gallery

**`selectImage` does not work.** Unlayer's documented hook for replacing its image picker
(`registerCallback('selectImage', …)`) is gated behind a paid entitlement — their own type definitions
list it under `entitlements`, beside `userUploads` and `stockImages`. On an unregistered project the
callback is accepted, forwarded to the iframe, and then silently never fires. The upstream bug report
([react-email-editor#435](https://github.com/unlayer/react-email-editor/issues/435)) is open, and the
docs now mark the approach "Not Recommended".

Drive the design instead. `saveDesign` / `loadDesign` are ungated, so a button *outside* the editor can
put an image in without any entitlement:

1. Add an **Insert image** button in a strip above the designer.
2. It opens the existing picker — `window.FSAMedia.pick(cb)`, see [media-gallery.md](media-gallery.md).
3. Track the selected block: `unlayer.addEventListener('item:selected', fn)` gives `{item, active}`.
   Keep `item.id` when `item.type === 'image'`, else null.
4. On pick: read the design out, edit it, load it back.

```js
unlayer.saveDesign(function (design) {
  // Replace the selected image block, else append to the last column.
  var rows = design.body && design.body.rows;
  if (!rows || !rows.length) return;
  var target = selectedImageId && findContent(rows, selectedImageId);
  if (target) {
    target.values.src = { url: url, autoWidth: true, maxWidth: '100%' };
  } else {
    var col = rows[rows.length - 1].columns.slice(-1)[0];
    if (!col) return;
    (col.contents = col.contents || []).push({
      type: 'image',
      values: { src: { url: url, autoWidth: true, maxWidth: '100%' }, textAlign: 'center', altText: '' }
    });
  }
  unlayer.loadDesign(design);
});
```

Measure the file first (`new Image()` → `naturalWidth`/`naturalHeight`) and put the dimensions in `src`,
or the block inserts at the wrong scale.

Guard every branch: an empty body, a row with no columns, a stale id whose block was deleted, and a
selected block that is **not** an image (a text block selected when the button is clicked must append a
new image, never have its `values.src` overwritten).

Cost of this approach: `loadDesign` resets the editor's undo history. Unlayer's own upload button
doesn't. Worth stating in the UI copy.

## Build — rendering & sending

`render_email()` branches on `editor` and nothing else:

```php
$t = email_template($id);
if (!$t || (!$t['enabled'] && !$force)) return null;
$vars += ['site' => SITE_NAME, 'phone_co' => CO_PHONE, 'email_co' => CO_EMAIL, 'address' => CO_ADDRESS];

// A designed or pasted template is already a complete document - wrapping it again
// nests <html> inside <html>, which several clients render as plain text.
$html = $t['editor'] === 'classic'
    ? email_wrap(email_replace($t['heading'], $vars), email_replace($t['body'], $vars))
    : email_replace($t['body_html'], $vars);

return ['subject' => email_replace($t['subject'], $vars), 'html' => $html];
```

`email_replace()` is unchanged and runs over the whole document — it only rewrites `{token}` runs.

**Set `$m->Encoding = 'base64'` in `send_mail()`.** SMTP caps a line at 1000 octets including CRLF
(RFC 5321 §4.5.3.1). Exported designs contain single lines far longer than that; with PHPMailer's
default `8bit` the server drops the connection and the send fails with an empty error. Hand-written
`classic` bodies rarely hit the limit, which is why this only surfaces once someone uses the designer.
The `mail()` fallback needs the matching `Content-Transfer-Encoding: base64` header and a
`chunk_split(base64_encode($html), 76, "\r\n")` body.

Preview is the existing iframe, with two changes: for a designed template feed it `body_html` directly,
and add `sandbox=""` to the iframe. `srcdoc` without `sandbox` inherits the admin origin, and a pasted
`html` template is arbitrary third-party markup — a `<script>` in it would run with the admin session.

## Edge cases
- **CSP.** `script-src` and `frame-src` must both allow `https://editor.unlayer.com`. With a restrictive
  policy the editor renders as an empty box and logs nothing useful.
- **Switching modes** on an existing template must not silently discard the other mode's content. Keep
  `design_json` when moving `design` → `html`, and warn before overwriting.
- Uploads still go through the library's client-side compression — see [media-gallery.md](media-gallery.md).
- Merge tags must round-trip: check `{token}` survives export un-escaped (`{` and `}` are not entities).
- A template with no design yet must open blank, not error — `loadDesign(null)` throws.

## Acceptance
- Editing subject/heading/body + toggling enabled persists; live preview reflects edits before save;
  disabling a template stops that email.
- A designed template round-trips: save, reload the page, the canvas comes back identical.
- A 150 KB design saves and reads back byte-identical (proves the column is `LONGTEXT`, not `TEXT`).
- Insert image places a picture from the existing library, replacing the selected block when one is
  selected and appending otherwise; no image reaches a third-party CDN.
- A designed template arrives in Gmail and Outlook with one header, correct tokens, and no truncation.
- An existing install with no `email_templates` rows behaves exactly as before the change.
