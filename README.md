# Fire Safe Australia — E-commerce Guideline

Operating and development guidelines for the **Fire Safe Australia** catalogue / e-commerce website
([firesafeaustralia.com.au](https://firesafeaustralia.com.au)).

This repository holds **documentation only** — no application code and no credentials.

## Contents

| Doc | What it covers |
|-----|----------------|
| [01 — Overview](docs/01-overview.md) | What the platform is, stack, brand kit |
| [02 — Architecture](docs/02-architecture.md) | File layout, routing, settings system |
| [03 — Admin guide](docs/03-admin-guide.md) | Day-to-day admin: products, categories, blog, hero, featured, settings |
| [04 — Media library](docs/04-media-library.md) | Uploading, the image picker, editing, compression |
| [05 — Emails](docs/05-emails.md) | Email templates & delivery (SMTP) |
| [06 — Deployment](docs/06-deployment.md) | How changes go live (FTP), server constraints |
| [07 — Content guidelines](docs/07-content-guidelines.md) | Image specs, SEO, alt text, tone |

## Quick facts

- **Stack:** vanilla PHP 8 code running on **PHP 7.4** shared hosting (GoDaddy cPanel), MySQL/PDO, no build step.
- **Mode:** enquiry-first ("Enquire for price"); optional Stripe checkout toggle.
- **Brand:** orange `#F97316` / `#EA580C`, ember `#FBBF24`, ink `#17120F`; fonts Archivo / IBM Plex Sans / IBM Plex Mono.
- **Contact:** 795 Thompson Road, Lyndhurst · 0449 794 559 · info@firesafeaustralia.com.au

> ⚠️ **Credentials** (FTP, DB, admin login) are **never** stored in this repo. They live only in the local
> `credentials.txt` on the maintainer's machine.
