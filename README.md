# E-commerce Platform — Guideline

Operating and feature documentation for the catalogue / e-commerce platform.

This repository holds **documentation only** — no application code and no credentials.

## Contents

| Doc | What it covers |
|-----|----------------|
| [Admin settings](docs/admin-settings.md) | Every section of the admin Settings page and what it controls |
| [Payments & store modes](docs/payments-and-modes.md) | Stripe gateway, guest checkout, enquiry mode, purchasing mode |
| [Media gallery](docs/media-gallery.md) | Admin media library, attachment details, and the product/variant picker modal |

## Platform at a glance

- **Stack:** vanilla PHP (8-style code, runs on PHP 7.4 shared hosting), MySQL via PDO, no build step.
- **Default mode:** enquiry-first ("Enquire for price"); online payments are an optional toggle.
- **Settings:** stored as JSON rows in a `settings` table; secrets encrypted at rest (AES-256-GCM).

> ⚠️ Credentials (FTP, database, admin login, API keys) are **never** stored in this repository.
