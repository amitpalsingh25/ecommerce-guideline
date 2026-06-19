# Admin → Emails

Path: **`/admin/emails`**

Manage every notification the site sends. Templates have sensible defaults in code; any edits are saved
as overrides and merged on top.

## The list

Each row: **Email** (label), **Recipient** (the admin address or "Customer"), **Status** (On/Off), and
**Manage**.

The notifications:

| Template | Sent to | Fires when |
|----------|---------|-----------|
| **New enquiry — to admin** | Admin | A visitor submits an enquiry/cart request |
| **Enquiry received — to customer** | Customer | (Confirmation of the above) |
| **Contact form — to admin** | Admin | The contact form is submitted |
| **New order — to admin** | Admin | A Stripe order is paid |
| **Order confirmation — to customer** | Customer | (Confirmation of the paid order) |

## Manage a template

| Field | Notes |
|-------|-------|
| **Enable this email** | Off = this notification is never sent. |
| **Recipient** | Read-only (admin address or "the customer"). |
| **Subject** | Email subject line. |
| **Heading** | Big heading inside the email. |
| **Body** | Rich-text (WYSIWYG) editor; stored as HTML. |

**Placeholders** (type them anywhere; they're filled at send time):

```
{name} {email} {phone} {message} {items} {order} {total} {site} {phone_co} {email_co}
```

`{items}` inserts the products table (SKU / item / qty). Others are simple text substitutions.

### Live preview

Below the form is a **full-width live preview** rendered with sample data that **updates as you type**
(heading + body). It shows the real branded email shell — logo header, accent bar, content, footer — so
you see exactly what recipients get. There's also a full-page preview link.

## Delivery & wrapper

- Every email is wrapped in a **branded HTML shell** (header with logo, coloured accent bar, footer with
  contact details).
- Sending uses the **SMTP** account from Settings → Email when configured, otherwise the server's
  `mail()` as a fallback. Set SMTP up for reliable delivery.
- A **disabled** template is skipped entirely (its event sends nothing).

## Notes

- Defaults live in code; saved edits are stored under the `emails` setting and merged over the defaults,
  so you can always clear a field to fall back to the default text.
- The same brand colours/logo from Settings flow into the email shell automatically.
