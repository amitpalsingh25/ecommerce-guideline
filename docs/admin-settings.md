# Admin Settings page

Path: **`/admin/settings`**

The Settings page is split into independent cards. **Each card is its own form and saves on its own**
(it posts a hidden `_section` field, and only that section's values are written). Saving one card never
overwrites another.

All values are stored as JSON rows in the `settings` table via `set_setting(key, value)` and read back
with `setting(key, default)`. Secret fields are **encrypted at rest** (AES-256-GCM) and are never shown
back to the browser — leave a secret field blank to keep the saved value.

---

## 1. Branding

| Field | Notes |
|-------|-------|
| **Logo** | Header logo. Upload an image; shown in the site header. |
| **Favicon** | Browser-tab icon. |
| **Logo width (px, 0 = auto)** | Fixed width, or `0` to size by height (keeps aspect ratio). |
| **Logo height (px)** | Used when width is `0`. |
| **Catalogue PDF** | Upload a PDF (max 30 MB). Wires up the header **"Catalogue"** button. While no PDF is set, that button falls back to the contact page. A "remove current PDF" checkbox appears once one is uploaded. |

Stored under the `general` setting key.

## 2. Brand colours

Four colour pickers — **Primary**, **Primary dark**, **Accent**, **Ink/dark** — that override the site's
CSS custom properties globally (buttons, links, accents, dark sections). Values are validated as `#RRGGBB`.
Stored under `theme`.

## 3. Store mode

Three toggles that decide how the store behaves (see
[Payments & store modes](payments-and-modes.md) for the full behaviour matrix):

- **Enable selling** — turn on online purchasing (needs valid Stripe keys).
- **Enable enquiry mode** — the "Enquire for price" / cart-to-enquiry flow.
- **Allow guest checkout** — let visitors order/enquire without an account.

Stored under `toggles`.

## 4. Featured products (home)

Controls the homepage "Featured products" row.

| Field | Effect |
|-------|--------|
| **How many to show** | Number of featured products fetched (1–40). |
| **Cards per row** | Visible cards per row (1–6). |
| **Autoplay (seconds, 0 = off)** | Auto-advance interval for the carousel. |
| **Enable carousel** | When featured count > cards-per-row, show a swipeable carousel with arrows instead of wrapping. |

A product appears here when its **"Feature on homepage"** box is ticked in the product editor.
Stored under `featured`.

## 5. Email (SMTP)

When configured, enquiry/contact/order emails send via this SMTP account; otherwise the server's
`mail()` is used as a fallback.

| Field | Notes |
|-------|-------|
| **Host / Port** | e.g. `smtp.gmail.com` / `587`. |
| **Username** | The mailbox address. |
| **App password** | Stored **encrypted**. For Gmail use a 16-char App Password, not the account password. Blank = keep existing. |
| **From name / From email** | Sender identity on outgoing mail. |

Stored under `smtp`. See [Emails](payments-and-modes.md) note: templates are managed separately under `/admin/emails`.

## 6. Payments (Stripe)

| Field | Notes |
|-------|-------|
| **Enable Stripe checkout** | Master switch for online payment. |
| **Publishable key** | `pk_…` (not secret). |
| **Secret key** | `sk_…` — stored **encrypted**; blank = keep existing. |
| **Webhook signing secret** | `whsec_…` — stored **encrypted**; blank = keep existing. |
| **Webhook URL** | Shown read-only; register it in the Stripe dashboard. |

Stored under `stripe`. Full flow in [Payments & store modes](payments-and-modes.md).

---

### Notes for maintainers

- Adding a new section = add a `<form>` card with a unique `_section`, handle it in the settings POST
  branch, and read its values into the render. Keep secrets encrypted and never echo them back.
- Because each card saves independently, validation failures in one form don't affect the others.
