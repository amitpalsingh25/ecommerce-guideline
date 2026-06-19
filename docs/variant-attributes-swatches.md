# Build spec — Attribute variations & swatches (WooCommerce-style, future)

**Status:** NOT in the current build. Today a product has **flat variants** (rows of
`label, sku, price, image, position`). This spec upgrades that to **attribute-driven variations** with
**swatches** (colour / image / button) and per-variation pricing — like WooCommerce + a variation-swatches
plugin. Build it PHP 7.4-safe, PDO, server-rendered, same conventions as the rest.

---

## 1. Concept

- A product has one or more **attributes** (e.g. *Size*, *Material*, *Colour*).
- Each attribute has **terms** (Size → 65×38, 65×50; Material → Aluminium, Brass).
- A **variation** is one combination of terms (e.g. *65×38 / Brass*) with its own **SKU, price, sale
  price, image, stock, enabled**.
- The storefront shows a **swatch selector** per attribute; picking one term per attribute resolves to a
  variation and updates **price + image + SKU + availability**.

## 2. Data model (new tables)

```
attributes            id, product_id (NULL = global/reusable), name, slug, position,
                      swatch_type ENUM('button','color','image')        -- how terms render
attribute_terms       id, attribute_id, label, slug, position,
                      swatch_color VARCHAR(7) NULL,  swatch_image VARCHAR(500) NULL
variations            id, product_id, sku, price DECIMAL NULL, sale_price DECIMAL NULL,
                      image VARCHAR(500) NULL, stock INT NULL, enabled TINYINT DEFAULT 1, position
variation_terms       variation_id, attribute_id, term_id            -- one row per attribute in the combo
```

Notes:
- `swatch_type` decides rendering: **button** (text pill), **color** (uses `swatch_color`), **image**
  (uses `swatch_image`).
- `price NULL` → "Enquire" (consistent with current behaviour). `sale_price` optional.
- `stock NULL` → stock not tracked for that variation.
- Reuse the existing media library for `swatch_image` and variation `image`.

## 3. Migration from current flat variants

- Keep the existing `product_variants` working; treat the new system as **opt-in per product**.
- One-click "convert": create a single attribute **"Option"** (swatch_type=button), turn each existing
  variant `label` into a term, and create one variation per old row carrying its sku/price/image. After
  conversion the product uses the variation engine.
- Detection on the storefront: if a product has `attributes`, render the swatch UI; else fall back to the
  current flat variant table.

## 4. Admin — build

### Attributes editor (per product, optional global library)
- Add attribute → name + **swatch type**.
- Add terms → label; if type=color show a colour input (store `#RRGGBB`); if type=image show a media
  picker (`swatch_image`). Reorder/remove terms.
- Optional **global attributes**: define reusable attributes (product_id NULL) and attach to products.

### Variations editor
- **Generate variations**: cartesian product of selected terms → create missing `variations` +
  `variation_terms`. Don't duplicate existing combos.
- Per-variation fields: SKU, price, sale price, **image** (media picker), stock, enabled.
- **Bulk actions**: set price / set stock / enable-disable across all (quality-of-life).
- Validate: SKU unique within product; combo unique.

## 5. Storefront — build

### Selector
- Render one row per attribute. Term rendering by `swatch_type`:
  - **button** → text pills.
  - **color** → round colour dots (`background:swatch_color`), with a title/aria-label of the term.
  - **image** → small image swatches (`swatch_image`).
- Selected state = ring/active (border, per existing card-swatch style).
- **Resolve on selection**: when one term per attribute is chosen, look up the matching variation and
  update: **price** (with sale strikethrough if set), **main gallery image** (variation image), **SKU**,
  **availability** (in/out of stock, enabled), and enable the Add button.
- **Disable impossible combos**: grey out terms that can't form an enabled/in-stock variation given the
  current partial selection (compute valid term sets from variations).
- Before a full selection, show a price **range** ("From $X" / "$X–$Y") from the variations.

### Cart / checkout
- Add-to-cart carries the **variation id/SKU** (not just product). At checkout, **re-resolve + re-price
  the variation from the DB** (never trust client price). Stock-check if tracked.

## 6. Pricing rules
- Effective price = `sale_price ?? price`. Show original struck-through when `sale_price` set.
- Mixed/empty prices → "From {min}" or "Enquire" when all NULL (respect store mode — in enquiry mode hide
  prices entirely and just resolve image/SKU).

## 7. Security / validation
- Server resolves the variation from posted term ids; reject combos that don't map to an enabled variation.
- Re-price + stock-check server-side at checkout.
- Escape all output; parameterised queries; CSRF on admin saves.

## 8. Build order
1. Tables + a `Variations` repo (resolve combo → variation; valid-term computation).
2. Admin attributes editor (with swatch types + term colour/image).
3. Admin variations: generate matrix + per-variation edit + bulk actions.
4. Storefront swatch selector + JS resolver (price/image/SKU/availability, disable invalid).
5. Cart/checkout carry + re-price variation; stock handling.
6. "Convert flat variants" migration helper + fallback rendering.

## Acceptance
- A product with Size×Material renders two swatch rows (e.g. buttons + colour/image); choosing one of
  each updates price, image, SKU and the Add button; invalid combos are disabled; "From $X" shows before
  selection; cart/checkout re-price from the DB.
- Products without attributes still use the existing flat variant table unchanged.
