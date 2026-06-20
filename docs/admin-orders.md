# Build spec — Orders

> Tokens, layout & structure reference: [design-system.md](design-system.md).

**Goal:** record Stripe payments. Read-only admin list; rows created by the checkout flow.

## Route
- `GET /admin/orders` → `admin_orders()` (ensure orders table exists first).

## Data — `orders`
id, email, name, phone, items JSON (line items), total DECIMAL, status enum(pending,paid,failed,
cancelled), stripe_session, created_at. Auto-create the table if missing.

## Build — list
Table: Order #, Customer (name + email), Items (line count), Total, Status badge, Date. Newest first,
LIMIT 500. When `payments_enabled()` is false, show a notice that orders appear once Stripe + selling are
on (store is in enquiry mode meanwhile).

## Order lifecycle (build in the payment flow — see [payments-and-modes](payments-and-modes.md))
1. Checkout creates a **pending** order with items **re-priced from the DB** + total.
2. Create Stripe Checkout Session (cURL) → redirect; store `stripe_session`.
3. Webhook (`checkout.session.completed`, signature verified) → mark **paid** → send order emails.
4. Session-create failure → **failed**.

## Acceptance
- Orders are never hand-created here; statuses reflect the Stripe flow; totals come from server pricing.
