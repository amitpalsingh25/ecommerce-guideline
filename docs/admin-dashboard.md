# Build spec — Dashboard

**Goal:** an at-a-glance admin landing screen. Read-only.

## Route
- `GET /admin` → `admin_dashboard()` (after `require_admin()`).

## Data
Count queries: products, product_variants, categories, blog_posts, enquiries, and enquiries where
`status='new'`.

## Build
1. Render a grid of **stat cards**, each = a count + label, linking to its section:
   Products→`/admin/products`, Variants→`/admin/products`, Categories→`/admin/categories`,
   Blog posts→`/admin/blog`, Enquiries→`/admin/enquiries`, New enquiries→`/admin/enquiries`.
2. Render **quick-action** buttons: Add product, Categories, Write post, Settings; plus a "New product"
   button in the top bar.
3. Wrap output in the admin layout (`respond_admin`).

## Acceptance
- Each card shows a live count and navigates to the right section.
- No write actions on this page.
