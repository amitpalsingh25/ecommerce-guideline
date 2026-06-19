# Build spec — Enquiries

**Goal:** capture and manage no-payment customer requests (the default "enquiry mode" flow).

## Routes
- `GET /admin/enquiries` — list. `GET /admin/enquiries/{id}` — detail.
- `POST /admin/enquiries/{id}` — update status (CSRF).

## Data — `enquiries`
id, name, email, phone, items JSON `[{sku,title,qty}]`, message, status enum(new,responded,closed),
created_at.

## Build — capture (public)
`POST` enquiry handler: validate name + email + items; store row; send **two emails** (admin notification
+ customer confirmation) via the email templates; log the result; redirect with a "sent" flag.

## Build — list
Table: From (name), Email, Items (line count · total units), Status badge, Received, View.

## Build — detail
Two panels: **Contact** (name, mailto email, phone, received) with a **status select that auto-submits**
(`new`/`responded`/`closed`), plus the message; **Requested items** table (SKU/title/qty from the JSON).

## Notes
- Dashboard "New enquiries" = rows with `status='new'`.
- Enquiries are independent of Orders (payments). Both can exist depending on store mode.

## Acceptance
- Submitting the cart creates an enquiry + sends both emails; status changes persist instantly.
