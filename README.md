# E-commerce Platform — Guideline

Operating and feature documentation for the catalogue / e-commerce platform.

This repository holds **documentation only** — no application code and no credentials.

## Contents

Admin guides, one per sidebar section:

| Doc | What it covers |
|-----|----------------|
| [Dashboard](docs/admin-dashboard.md) | The overview screen: stat cards, quick actions |
| [Hero slideshow](docs/hero-slideshow.md) | Homepage banner — slideshow & single-image modes |
| [Products](docs/admin-products.md) | Product list, filters/sort, editor, variants |
| [Categories](docs/admin-categories.md) | Category tree, nesting, slugs |
| [Media gallery](docs/media-gallery.md) | Media library, attachment details, picker modal |
| [Blog](docs/admin-blog.md) | Posts: editor, publishing behaviour |
| [Enquiries](docs/admin-enquiries.md) | Enquiry-mode customer requests |
| [Orders](docs/admin-orders.md) | Stripe orders & statuses |
| [Emails](docs/admin-emails.md) | Notification templates & delivery |
| [Event Logs](docs/admin-event-logs.md) | System event log & troubleshooting |
| [Admin settings](docs/admin-settings.md) | Every Settings card |
| [Payments & store modes](docs/payments-and-modes.md) | Stripe, guest checkout, enquiry vs purchasing |

**Maintaining these docs:** see [CONTRIBUTING.md](CONTRIBUTING.md) — how to amend/extend without
destroying existing guides.

## Platform at a glance

- **Stack:** vanilla PHP (8-style code, runs on PHP 7.4 shared hosting), MySQL via PDO, no build step.
- **Default mode:** enquiry-first ("Enquire for price"); online payments are an optional toggle.
- **Settings:** stored as JSON rows in a `settings` table; secrets encrypted at rest (AES-256-GCM).

> ⚠️ Credentials (FTP, database, admin login, API keys) are **never** stored in this repository.
