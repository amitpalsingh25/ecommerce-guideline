# Admin → Orders

Path: **`/admin/orders`**

A read-only list of paid/attempted orders. Orders are **created by the checkout flow**, not entered by
hand (see [Payments & store modes](payments-and-modes.md)).

## The list

Columns: **Order #**, **Customer** (name + email), **Items** (line count), **Total**, **Status** badge,
**Date**. Capped at 500 rows, newest first.

When **online payments are off**, a notice explains that orders only appear once **Stripe is enabled**
(Settings → Payments) **and selling is on**. Until then the store runs in **enquiry mode**, so customer
requests show under **Enquiries**, not here.

## Order statuses

| Status | Meaning |
|--------|---------|
| **pending** | Order row created; customer sent to Stripe Checkout but payment not yet confirmed. |
| **paid** | Stripe webhook confirmed payment (`checkout.session.completed`). |
| **failed** | The Stripe Checkout session could not be created. |
| **cancelled** | Reserved for cancelled/abandoned orders. |

## How an order is created

1. At checkout, an order row is written as **pending**, with line items **re-priced from the database**
   (client prices are never trusted) and a total.
2. The customer is redirected to Stripe's hosted checkout.
3. On success, the **webhook** verifies the Stripe signature and marks the order **paid**, then sends the
   order emails (admin notification + customer confirmation — see [Emails](admin-emails.md)).

## Notes

- The orders table is **auto-created** on first visit if it doesn't exist.
- Items are stored as JSON line items on the order row.
- Enquiries (no-payment requests) are separate from orders — check the Enquiries page for those.
