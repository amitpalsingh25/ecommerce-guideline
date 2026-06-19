# Media gallery

Covers the admin **Media library**, the **Attachment details** view, the reusable **image picker
modal** used in the product editor, and how images surface on the **product page**.

---

## Admin → Media library (`/admin/media`)

A grid of every uploaded image.

- **Upload images** — multi-file upload button. Files are **compressed in the browser before upload**:
  downscaled to **≤ 1000 px wide** and re-encoded to **WebP (~0.85 quality)** — smaller files, same
  visible quality. Transparency is preserved; GIF/SVG/AVIF are left untouched.
- **Click an image** → opens the **Attachment details** modal.
- Each tile has a quick **delete** (×) control.

### Attachment details modal

Two panes — preview + image tools on the left, metadata on the right.

**Right pane**
- File name, type, size, **dimensions**.
- **Title** and **Alternative text** — saved per image (used for accessibility / SEO).
- **URL** field with a **copy icon** (click copies; shows a tick briefly).
- **Used by** — lists the products/variants currently using this image, with links.
- **Delete permanently**.

**Left pane — Edit image** (server-side, in place so existing links keep working)
- **Rotate** left / right (90°)
- **Flip** horizontal / vertical
- **Scale** to a new width
- **Crop** — drag a box on the preview, then **Apply crop**

Editing rewrites the same file (same URL), so any product already pointing at the image updates
automatically. Image editing needs the **GD** extension on the server; if it's missing the editor
shows a notice and the rest of the panel still works.

---

## Reusable image picker (product & variant fields)

Anywhere an image is set (product **main image**, each **variant image**), a **"Choose / upload"**
field opens a WordPress-style picker modal:

- **Two panes:** a selectable image grid on the left, a details sidebar on the right.
- **Search** box to filter by file name.
- **Drag-and-drop** or **Upload** to add new images (same compression as the library).
- Click a tile to **select** (shows a tick); the sidebar shows preview + dimensions + URL.
- Confirm with **"Use this image"** (or double-click a tile).

Selected URLs are written into the form's hidden field, so the product/variant saves a reference to the
file in the media library (no duplicate uploads).

---

## How images appear on the storefront

### Product card
- The card collects **all** of a product's images (main image **plus every variant image**, de-duplicated).
- If a product has no main image but a variant does, the card uses the **first variant image** instead
  of the placeholder.
- When there's more than one image, a row of **variant swatches** appears next to the size line.
  **Clicking a swatch** slides the card's main image to that variant (active state shown with a border).
- The image area is a **fixed responsive height** with `object-fit: contain` on a white background, so
  uneven images never cause layout jumps.

### Product detail page
- The left side is a **gallery**: a large main image plus a **thumbnail strip** built from the product
  and variant images.
- Clicking a thumbnail, or a **variant row** in the size table, swaps the main image.
- If a product has no images at all, a branded placeholder is shown instead.

---

### Storage notes

- Uploaded files live in the site's `/uploads` directory; the library simply lists that folder.
- Image **Title/Alt** metadata is stored in settings (keyed by file name) — no database migration needed.
- Deleting a file removes it from disk; products referencing it fall back to their placeholder.
