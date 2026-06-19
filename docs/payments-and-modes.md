# Payments & store modes

The store can run in two ways — **enquiry mode** (default) and **purchasing mode** — controlled by
toggles under **Admin → Settings → Store mode**, plus the **Payments (Stripe)** card.

## Store modes

### Enquiry mode (default)
- Prices are hidden; products show **"Enquire for price"**.
- The cart is a **request list**. Checkout collects name / email / phone and sends an **enquiry email**
  to the admin (and a confirmation to the customer).
- No payment is taken.

### Purchasing mode (selling)
- Prices are shown; the cart becomes a real cart and checkout goes to **Stripe Checkout**.
- Requires **both**: *Enable selling* ON **and** valid Stripe keys with *Enable Stripe checkout* ON.

The effective switch is `payments_enabled()` ≈ `sellingEnabled && stripe.enabled && stripe.secretKey set`.
If selling is on but Stripe isn't fully configured, the store safely stays in enquiry mode.

### Behaviour matrix

| Enable selling | Stripe enabled + keys | Result |
|:---:|:---:|---|
| Off | — | Enquiry mode. "Enquire for price", cart → enquiry email. |
| On | No / incomplete | Still enquiry mode (safe fallback). |
| On | Yes | Purchasing mode. Prices shown, checkout → Stripe. |

## Guest checkout

- **Allow guest checkout** (Store mode toggle) lets visitors enquire / order **without an account**.
- This build is guest-first: there is no customer login required to send an enquiry or pay.
- Turning it off is the hook point if account-gated checkout is added later.

## Stripe payment gateway

Keys live under **Settings → Payments** and are **encrypted at rest**:

- **Publishable key** (`pk_…`) — safe for the client.
- **Secret key** (`sk_…`) — encrypted; used server-side only.
- **Webhook signing secret** (`whsec_…`) — encrypted; verifies incoming webhooks.

### Setup

1. Enter the publishable + secret keys, tick **Enable Stripe checkout**, and turn on **Enable selling**.
2. Copy the **Webhook URL** shown on the Settings card.
3. In the Stripe dashboard → Developers → Webhooks, add that URL for the
   `checkout.session.completed` event, and paste the generated **signing secret** back into Settings.
4. Test with Stripe test keys + test cards before switching to live keys.

### Checkout flow

1. Customer checks out → an **order row is created** with status `pending` and its line items are
   **re-priced from the database** (never trusting client prices).
2. The server creates a **Stripe Checkout Session** (via the Stripe REST API) and redirects the
   customer to Stripe's hosted page.
3. On payment, Stripe calls the **webhook**. The signature is verified against the signing secret.
4. On `checkout.session.completed`, the order is marked **paid** and the **order emails** are sent
   (admin notification + customer confirmation).

### Security notes

- Prices are always recomputed server-side at checkout.
- Webhook requests are rejected unless the Stripe signature validates.
- Secret keys and webhook secrets are stored encrypted and never returned to the browser.
