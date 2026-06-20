# E-commerce Platform — Build Guideline

**Build instructions** for recreating this catalogue / e-commerce platform, plus a per-section spec for
every admin area. Written so an AI agent or developer can rebuild it from scratch.

This repository holds **documentation only** — no application code and no credentials.

## Start here

➡️ **[BUILD-INSTRUCTIONS.md](BUILD-INSTRUCTIONS.md)** — the master blueprint: constraints, stack, file
layout, routing, data model, settings system, build order, and gotchas. Read this first.

➡️ **[docs/design-system.md](docs/design-system.md)** — exact visual foundation (fonts, colour tokens,
buttons, card styles). Read this to make a rebuild *look* the same, not just behave the same.

➡️ **[demo/](demo/)** — the **actual working source** (the live project). Clone it to see the real file
structure, run it locally, or edit and re-publish. See [demo/README.md](demo/README.md).

## Per-section build specs

Each doc = *how to build that section* (routes, data, UI, save logic, edge cases, acceptance):

| Spec | Section |
|------|---------|
| [Dashboard](docs/admin-dashboard.md) | Admin overview |
| [Hero](docs/hero-slideshow.md) | Homepage banner (slideshow / single image) |
| [Products](docs/admin-products.md) | Products + variants |
| [Categories](docs/admin-categories.md) | Nested categories |
| [Media gallery](docs/media-gallery.md) | Library, picker modal, image editor, storefront imagery |
| [Product cards (frontend)](docs/product-cards-frontend.md) | Card component, variant-swatch image swap, Featured carousel |
| [Blog](docs/admin-blog.md) | Articles |
| [Enquiries](docs/admin-enquiries.md) | Enquiry-mode requests |
| [Orders](docs/admin-orders.md) | Stripe orders |
| [Emails](docs/admin-emails.md) | Notification templates |
| [Event Logs](docs/admin-event-logs.md) | System log |
| [Settings](docs/admin-settings.md) | Branding, colours, modes, featured, SMTP, Stripe |
| [Store modes & payments](docs/payments-and-modes.md) | Enquiry vs selling, Stripe |
| [Customer accounts & commerce](docs/commerce-accounts.md) | **Future:** register, login, password reset, account, cart, checkout |
| [Attribute variations & swatches](docs/variant-attributes-swatches.md) | **Future:** WooCommerce-style attributes, per-variation price/image/stock, colour/image/button swatches (backend + frontend) |

**Maintaining these docs:** [CONTRIBUTING.md](CONTRIBUTING.md) — amend/extend without destroying existing
specs.

## Platform at a glance

- **Stack:** vanilla PHP (8-style code, runs on **PHP 7.4** shared hosting), MySQL via PDO, **no build
  step**; deploy by FTP.
- **Default mode:** enquiry-first ("Enquire for price"); online payments are an optional Stripe toggle.
- **Settings:** JSON rows in a `settings` table; secrets encrypted at rest (AES-256-GCM).

> ⚠️ Credentials (FTP, database, admin login, API keys) are **never** stored in this repository.
