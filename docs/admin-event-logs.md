# Admin → Event Logs

Path: **`/admin/logs`**

A chronological record of system events — useful for troubleshooting (did an email send? did a webhook
arrive? did a save fail?).

## The list

Columns: **Level** badge, **Category**, **Message** (with any structured metadata underneath), **Time**.
Newest first, capped at 300 rows.

## Filters

- **Level** — `info`, `warn`, `error`.
- **Category** — e.g. `enquiry`, `email`, `order`, `stripe`, `contact`, `system`.

## What gets logged

| Category | Examples |
|----------|----------|
| `enquiry` | Enquiry received / DB save result |
| `email` | Whether enquiry/contact/order emails sent |
| `order` | Order created (pending), order paid |
| `stripe` | Checkout session created, webhook signature failures, API errors |
| `contact` | Contact form submissions |
| `system` | Settings saved, product/hero saves, media edits, lazy migrations |

Levels: **info** (normal), **warn** (something skipped/non-fatal, e.g. email not sent), **error**
(failures, e.g. bad webhook signature, DB error).

## Clearing logs

A **Clear logs** action (POST) empties the table. Use sparingly — it's your audit trail.

## Troubleshooting tips

- Emails not arriving? Filter **category = email** / **level = warn** to see send results, then check
  Settings → Email (SMTP).
- Payment not marked paid? Filter **category = stripe / order** to see webhook activity and signature
  checks.
