# Build spec — Media library, picker & product imagery

> Tokens, layout & structure reference: [design-system.md](design-system.md).

**Goal:** a reusable image system: a library page, a picker modal for any image field, client-side
compression, and a server-side image editor.

## Routes (all under `require_admin`)
- `GET /admin/media` — library page.
- `GET /admin/media/list` — JSON `{files:[{name,url}]}` (scan `/uploads`).
- `GET /admin/media/info?name=` — JSON: name,url,w,h,type,size,mtime,title,alt,used[],gd.
- `POST /admin/media/save` — set title/alt (CSRF).
- `POST /admin/media/edit` — GD op (CSRF): rotate/flip/scale/crop.
- `POST /admin/media/delete` — unlink (CSRF).
- `POST /admin/upload` — generic upload → `{url}`.

## Storage
- Files live in `/uploads` (the library = a listing of that folder, image extensions only).
- Title/Alt stored in `setting('media_meta')` keyed by file name (no DB table/migration).

## Build — client compression (before every upload)
Canvas: downscale to **≤1000px wide**, re-encode **WebP ~0.85**; keep transparency; skip gif/svg/avif;
never upscale; if compressed isn't smaller and wasn't downscaled, keep original. Use for both the library
upload and the picker upload.

## Build — picker modal (`media.js`, `window.FSAMedia.pick(cb)`)
Two panes: selectable grid (left) + details (right). Top: title, **search** (filter by name), **Upload**.
Drag-and-drop onto the grid uploads. Click selects (✓); double-click or **Use this image** confirms →
`cb(url)`. Wire any `[data-img-field]` widget: a `.img-pick` button opens the picker and writes the URL +
thumb into the field; `.img-clear` empties it. Use event delegation so dynamically-added variant rows work.

## Build — attachment details (library page)
Click a tile → modal: file info (name/type/size/dimensions), **Title** + **Alt** (save), **URL** + copy
icon (clipboard SVG, flips to ✓), **Used by** (query products.images LIKE + variant.image = url), and
**Edit image** via GD: rotate L/R, flip H/V, scale (new width), crop (drag a box → natural-px coords).
Edits **rewrite the same file** (same URL stays valid); cache-bust the preview with `?v=timestamp`. Guard
when GD is absent.

## Build — storefront imagery
- `product_card()`: collect product images + every variant image (dedup). If a product has no main image
  but a variant does, use the first variant image. With >1 image, render a **swatch row** (circular,
  variant images, `+N` overflow). **Click** a swatch → slide the card's main image to it (ease slide
  animation). Fixed responsive media height + `object-fit:contain` so uneven images don't shift layout.
- Product detail: gallery (main + thumbnail strip from product+variant images); clicking a thumb or a
  variant row swaps the main image. Don't let card-only CSS (fixed height/overflow) leak into the
  detail gallery — scope it.

## Edge cases
- CSRF field name must match the server (`_csrf`).
- Deleting a file → referencing products fall back to placeholder.

## Acceptance
- Same picker works on product main image + each variant image; uploads compress; editing keeps URLs;
  cards show variant swatches and never jump on image change.
