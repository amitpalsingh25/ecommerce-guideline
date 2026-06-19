# Admin → Products

Path: **`/admin/products`**

## Product list

A filter bar sits above the table; all controls submit together as URL query params and persist.

| Control | Behaviour |
|---------|-----------|
| **Search** | Matches product **title**, product **SKU**, **category name**, and **variant SKU** (LIKE). |
| **Category** | Filters to a category **and all its sub-categories** (full sub-tree). |
| **Status** | All / Published / Draft. |
| **Sort** | Newest, Oldest, Title A–Z, Title Z–A, Price low→high, Price high→low. |
| **Apply / Clear** | Apply runs the filter; Clear resets. Header shows "· filtered" when active. |

Table columns: **Title** (with a *Featured* badge if featured), **SKU** (or "N variants" for variant
products), **Category**, **Price** (or `—` for variant products / "Enquire" when no price), **Status**
badge, and **Edit**. The list is capped at 500 rows.

## New / edit product

Fields (top card):

| Field | Notes |
|-------|-------|
| **Title** | Required. |
| **SKU** | Leave blank if the product uses variants. |
| **Slug** | Blank = auto-generated from the title. |
| **Price (AUD)** | Blank = **"Enquire"** (no fixed price). |
| **Category** | Single category (supports the nested tree). |
| **Status** | Draft or Published. |
| **Feature on homepage** | Adds it to the homepage Featured row. |

**Main image** — set via the media picker (choose from the library or upload). Used on the product page
when there are no variant images, and as the default/card image.

**Variants / sizes** — one row per size, each with: a compact **image** picker, a **label**
(e.g. "65 × 38"), a **part number / SKU**, and an optional **price**. "Add size" appends a row; the
remove (trash) button deletes one. Leave the whole section empty for a single-item product (then use the
SKU + price above).

**Description** — a rich-text (WYSIWYG) editor; stored as HTML and rendered on the product page.

Buttons: **Save** (stays on the edit page with a "Product saved" banner), **View product** (opens the
public page in a new tab), **Delete**.

## Save behaviour & rules

- Slug is auto-slugified from the title if left blank.
- Price blank → stored as `null` → the storefront shows **"Enquire for price"**.
- **Variants are replaced wholesale** on each save (existing rows deleted, then re-inserted in order),
  so the form is the source of truth — don't expect partial edits to merge.
- The main image is stored as a single URL pointing at a media-library file.
- The `product_variants.image` column is **auto-created** on first save if missing (lazy migration).
- Prices are re-validated server-side; variant products display per-variant pricing, not a single price.

See also: [Media gallery](media-gallery.md) for the image picker, and
[Payments & store modes](payments-and-modes.md) for how price/"Enquire" depends on store mode.
