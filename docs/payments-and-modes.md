# Build spec — Store modes & Stripe payments

**Goal:** one codebase that runs **enquiry-first** by default and can be switched to **online selling**.

## Toggles — `setting('toggles')`
`sellingEnabled`, `enquiryEnabled`, `guestCheckout`. Plus `setting('stripe')` (enabled + encrypted keys).

Build `payments_enabled()` ≈ `sellingEnabled && stripe.enabled && stripe.secretKey set`. Everything that
shows price/checkout branches on this. If selling is on but Stripe isn't fully configured → **stay in
enquiry mode** (safe fallback).

| sellingEnabled | stripe enabled + keys | Mode |
|:---:|:---:|---|
| off | — | Enquiry: "Enquire for price", cart → enquiry email |
| on | no | Enquiry (fallback) |
| on | yes | Purchasing: prices shown, checkout → Stripe |

## Guest checkout
- `guestCheckout` lets visitors order/enquire without an account. This build is guest-first; keep the flag
  as the hook point if you later gate checkout behind login (see [commerce-accounts](commerce-accounts.md)).

## Stripe — build (no SDK, raw cURL)
1. Keys in Settings → Payments, **encrypted** (`enc()`); decrypt on read.
2. **Checkout** (`POST /stripe/checkout`): validate; create a **pending** order with line items
   **re-priced from the DB** (never trust client prices) + total; create a Checkout Session via
   `POST https://api.stripe.com/v1/checkout/sessions` (Bearer secret key) with `success_url`/`cancel_url`;
   store `stripe_session`; redirect to the session URL. On failure → mark order `failed`.
3. **Webhook** (`POST /stripe/webhook`): read raw body + `Stripe-Signature`; verify with
   `hash_hmac('sha256', "$t.$payload", webhookSecret)` + `hash_equals`; on `checkout.session.completed`
   mark the order `paid` and send order emails. Show the webhook URL in Settings for registration.

## Security
- Re-price server-side at checkout.
- Reject webhooks with an invalid signature.
- Store secret + webhook secret encrypted; never echo them.

## Acceptance
- Default deploy works with zero payment config (enquiry mode).
- Turning on selling + valid keys flips prices/checkout to Stripe; test-mode card → order marked paid via
  webhook → order emails sent.
