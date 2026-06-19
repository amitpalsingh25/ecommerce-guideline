# Build spec — Settings

**Goal:** one page of independent setting cards, each saving on its own; secrets encrypted.

## Route
- `GET/POST /admin/settings` → `admin_settings()`. Each card is a separate `<form>` carrying a hidden
  `_section`; the POST handler branches on it and writes **only** that section's keys.

## Pattern
- Store each section as a JSON row via `set_setting($key,$value)`; read with `setting($key,default)` or a
  typed accessor. Validate inputs. For secrets, `enc()` on save and **never** render them back — a blank
  field means "keep existing".

## Cards to build

| `_section` | setting key | Fields |
|-----------|-------------|--------|
| `general` | `general` | logo upload, favicon upload, logoWidth, logoHeight, **catalogue PDF** (validate `application/pdf`, ≤30MB) + remove |
| `theme` | `theme` | 4 colour pickers (primary, primary-dark, accent, ink); validate `#RRGGBB`; injected as CSS vars site-wide |
| `toggles` | `toggles` | sellingEnabled, enquiryEnabled, guestCheckout (checkboxes) |
| `featured` | `featured` | limit (1–40), perView (1–6), autoplay (sec), carousel (checkbox) |
| `smtp` | `smtp` | host, port, user, **app password (encrypted)**, fromName, fromEmail |
| `stripe` | `stripe` | enabled, publishableKey, **secretKey (encrypted)**, **webhookSecret (encrypted)**; show webhook URL read-only |

## Build — render
Show saved logo/favicon/catalogue if present; colour pickers with current values; toggles checked from
`toggles()`; mask secret fields (placeholder if a value is saved).

## Edge cases
- Don't share a field `name` across two cards (separate forms avoid collisions).
- Catalogue PDF wires the header "Catalogue" button; with none set, that button falls back to contact.

## Acceptance
- Saving one card never affects another; secrets persist encrypted and are never exposed; colour changes
  recolour the site; toggles switch store behaviour (see [payments-and-modes](payments-and-modes.md)).
