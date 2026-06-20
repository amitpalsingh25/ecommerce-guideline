# Build spec — Customer accounts & commerce (future)

> Tokens, layout & structure reference: [design-system.md](design-system.md).

**Status:** ✅ **BUILT** — register, login, logout, forgot/reset password, account area (enquiry + order
history, profile), and an admin **Customers** tab are live. Still future: Stripe `customer_id` linkage at
checkout, email verification, saved addresses. Below: the as-built design + the remaining future items.

## As built (current implementation)

- **Files:** `app/customer.php` (page handlers + `ensure_customers_table()`), customer helpers in
  `app/auth.php` (`current_customer`, `require_customer`, `customer_session_set`, `customer_logout`),
  routes in `public/index.php`, admin `admin_customers()`/`admin_customer_view()` in `app/admin.php`,
  two email templates in `app/emails.php`.
- **Table `customers`:** id, name, email (unique), password_hash, reset_token, reset_expires, created_at.
  Lazy-created on first use.
- **Routes:** `/register`, `/login`, `/logout`, `/forgot`, `/reset?token=`, `/account`,
  `/account/profile`.
- **Auth:** bcrypt (`password_hash`/`password_verify`); login stored in `$_SESSION['customer']`. Forgot
  always shows the same message (no enumeration); reset token is 24-byte hex, **1-hour** expiry,
  single-use. `require_customer()` redirects to `/login` with `after_login` return path.
- **Account area:** shows the customer's **enquiries and orders matched by email** (no schema change to
  orders/enquiries needed) + a profile form (name + optional new password).
- **Emails:** `account_welcome` + `password_reset` (with `{reset_url}`) added to `email_defaults()`, so
  they're editable in Admin → Emails with the live preview.
- **Admin:** Customers list (name, email, enquiry count, joined) + per-customer view (their enquiries).
- **Header:** the account icon points to `/account` when logged in, else `/login`.

## Future blueprint (not yet built)

The original full blueprint for the remaining pieces (Stripe `customer_id` linkage, email verification,
saved addresses, checkout prefill/guest-gate):

---

## 1. Data model (new/extended tables)

- **customers**: id, name, email (unique), password_hash, email_verified_at NULL, reset_token NULL,
  reset_expires NULL, created_at.
- **sessions** (optional if not using PHP sessions): id, customer_id, token, expires, created_at.
- **orders** (extend existing): add `customer_id` NULL (guest orders keep it NULL), shipping/billing JSON,
  fulfilment status.
- Reuse `enquiries`, `logs`, `settings`.

## 2. Auth

- Hash passwords with `password_hash()` / verify with `password_verify()` (bcrypt). Never store plaintext.
- Session: store `customer_id` in `$_SESSION` after login (regenerate id on login). `current_customer()`
  helper; `require_customer()` guard for account routes.
- Rate-limit login + reset (e.g. log attempts; soft lock).

### Register — `GET/POST /register`
Fields: name, email, password (+confirm). Validate email format + uniqueness + password length. Create
customer, hash password, start session, send a **verification email** (optional). Re-use the email
template system (`render_email('account_verify')` etc.).

### Login — `GET/POST /login`
Email + password. On success: regenerate session, set `customer_id`, redirect to `?next` or `/account`.
Generic error on failure (don't reveal which field).

### Logout — `POST /logout`
Clear session, redirect home.

### Forgot / reset password
- `GET/POST /forgot`: take email; if it exists, generate a random `reset_token` + `reset_expires`
  (~1h), email a link `/reset?token=…`. **Always** show the same "if that email exists…" message (no
  user enumeration).
- `GET/POST /reset?token=`: validate token + not expired; set new password (hash); clear token; log in or
  send to login.

> Add new email templates (`account_verify`, `password_reset`, `welcome`) to `email_defaults()` so they're
> editable in Admin → Emails, with placeholders like `{name}`, `{reset_url}`, `{verify_url}`.

## 3. Account area — `/account` (require_customer)
- Dashboard: greeting + recent orders.
- **Orders**: list the customer's orders (`WHERE customer_id = ?`), each → detail (items, total, status,
  date). Reuse the order line-item rendering.
- **Profile**: edit name, change password (verify current).
- **Addresses** (optional): saved shipping/billing.

## 4. Cart (already partly built — formalise)

- Client cart in `localStorage` (vanilla JS): `{sku,title,slug,image,price,qty}[]`.
- **Side cart drawer**: open button in header with a live count badge; list items with qty steppers +
  remove; subtotal; "Checkout" / "Send enquiry" CTA depending on store mode.
- Persist across pages; hydrate on load; keep a single source of truth module (`cart-store`).
- On add-to-cart, re-validate the SKU server-side at checkout (don't trust localStorage prices).

## 5. Checkout — `/checkout`

Branch on store mode (see [payments-and-modes](payments-and-modes.md)):

- **Enquiry mode:** collect name/email/phone/message → create enquiry + emails (existing flow). If logged
  in, prefill from the account and attach `customer_id`.
- **Purchasing mode:** collect contact + shipping; create a **pending** order (re-price from DB) with
  `customer_id` if logged in; create Stripe Checkout Session; redirect. Webhook marks paid + emails.
- Respect `guestCheckout`: if off, require login/registration before checkout (redirect to `/login?next=`).

## 6. Admin additions
- A **Customers** section: list/search customers, view their orders, resend verification, deactivate.
- Orders list: show customer (or "Guest"); filter by customer.

## 7. Security checklist
- bcrypt password hashing; session id regeneration on login/privilege change.
- CSRF on every auth/account POST.
- No user enumeration on login/forgot.
- Time-boxed, single-use reset tokens (store a hash of the token, not the raw token, if you want extra
  safety).
- Re-price/re-validate cart server-side at checkout.
- Verify Stripe webhook signatures; encrypt secrets at rest.
- Escape all output; parameterised PDO queries only.

## 8. Build order
1. customers table + register/login/logout + session helpers.
2. Account area (profile + orders) behind `require_customer`.
3. Forgot/reset password + verification emails (extend Emails templates).
4. Formalise cart store + side drawer.
5. Checkout branching (enquiry vs Stripe) with optional `customer_id` + guest gate.
6. Admin Customers section.

## Acceptance
- A visitor can register, verify, log in, see their orders, and reset a forgotten password.
- Logged-in checkout prefills + links orders to the account; guest checkout still works when allowed.
- All the security checks above hold.
