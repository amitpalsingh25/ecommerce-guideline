# Build spec — Emails

**Goal:** editable notification templates with a branded HTML wrapper, live preview, and SMTP delivery.

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

## Acceptance
- Editing subject/heading/body + toggling enabled persists; live preview reflects edits before save;
  disabling a template stops that email.
