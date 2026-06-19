# Build spec — Products

**Goal:** CRUD for products with optional per-size **variants** and media-library images.

## Routes
- `GET /admin/products` — list with filters.
- `GET /admin/products/new`, `GET /admin/products/{id}` — form.
- `POST /admin/products/{id}` / `POST /admin/products` (new) → `admin_product_save()`.
- `POST /admin/products/{id}/delete`.

## Data
- `products` (see [BUILD-INSTRUCTIONS §6](../BUILD-INSTRUCTIONS.md)). `images` = JSON array (single main
  URL in practice). `price` NULL = "Enquire".
- `product_variants` (id, product_id, sku, label, price NULL, image NULL, position). Add `image` via lazy
  migration (`ALTER TABLE … ADD COLUMN image` guarded by an information_schema check) on first save/form.

## Build — list
1. Read `q` (search), `cat`, `status`, `sort` from query string.
2. WHERE: search matches `title|sku|category name|variant sku` (LIKE); `cat` matches the category **and
   its whole sub-tree** (`subtree_ids()` → `IN (...)`); `status` exact.
3. ORDER BY map: new/old/az/za/price_low(`price IS NULL, price ASC`)/price_high. LIMIT 500.
4. Render a filter bar (search field + category/status/sort selects + Apply/Clear) and a table
   (Title +Featured badge, SKU or "N variants", Category, Price or "—"/Enquire, Status badge, Edit).

## Build — form
- Top card: Title (required), SKU (blank if variants), Slug (blank=auto), Price (blank=Enquire), Category
  select, Status, "Feature on homepage" checkbox.
- **Main image**: reusable media picker → hidden `image_url`.
- **Variants**: repeatable rows — compact image picker (`v_image[]`), label (`v_label[]`),
  sku (`v_sku[]`), price (`v_price[]`), remove; "add size". Leave empty for a single-item product.
- **Description**: `<textarea class="richtext">` (WYSIWYG → HTML).
- Buttons: Save, View product (new tab), Delete.

## Build — save
1. CSRF; require title; slugify (blank=auto).
2. price/category blank → NULL. images = `[image_url]` or `[]`.
3. Insert/update product.
4. **Replace variants wholesale**: delete all for product, re-insert non-empty rows in order with image.
5. Redirect to `/admin/products/{id}?saved=1` (stay on page, show "saved" banner).

## Edge cases
- Variant arrays are parallel by index — keep `v_image[]/v_label[]/v_sku[]/v_price[]` aligned.
- Re-price from DB at checkout; the admin price is the source.

## Acceptance
- Filters combine + persist in the URL; category filter includes sub-categories.
- Saving keeps you on the edit page; variants reflect exactly what's in the form.
