# Admin → Enquiries

Path: **`/admin/enquiries`**

Customer requests sent in **enquiry mode** (the default, no-payment flow). Distinct from **Orders**, which
are Stripe payments.

## Enquiry list

Columns: **From** (name), **Email**, **Items** (line count · total units), **Status** badge, **Received**
date, **View**.

## Enquiry detail

Two panels:

**Contact** — name, email (mailto link), phone, received date, and a **Status** dropdown
(`New` / `Responded` / `Closed`) that **auto-saves on change**. The customer's **message** is shown if
provided.

**Requested items** — a table of SKU / item / quantity from the cart at submission time.

## How an enquiry is created

1. A visitor builds a cart and submits the checkout form (name / email / phone / message).
2. An enquiry row is stored and **two emails** are sent: the admin notification and the customer
   confirmation (see [Emails](admin-emails.md)).
3. It lands here as **New**; update the status as you work it.

## Status meanings

| Status | Use |
|--------|-----|
| **New** | Just received, not actioned. |
| **Responded** | You've replied with pricing/availability. |
| **Closed** | Done / no longer active. |

## Notes

- The Dashboard "New enquiries" count reflects rows still in `New`.
- Enquiries are independent of Orders — if you switch the store to purchasing mode, paid orders appear
  under Orders instead.
