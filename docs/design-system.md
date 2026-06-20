# Build spec — Design system (tokens, type, buttons, cards)

**Goal:** the exact visual foundation so a rebuild *looks* the same, not just behaves the same. Copy these
values verbatim. (Brand colours/logo are overridable from Admin → Settings, but these are the defaults.)

---

## 1. Fonts (load first)

```css
@import url('https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800;900&family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap');
```
- **Display / headings / buttons:** Archivo (700–900).
- **Body:** IBM Plex Sans.
- **Mono** (SKUs, eyebrows, small labels): IBM Plex Mono.

## 2. Design tokens — `:root`

```css
:root{
  --ink:#17120f; --ink-2:#221a15; --charcoal:#2a2420; --text:#4f473f; --muted:#8c8077;
  --flame:#f97316; --flame-deep:#ea580c; --ember:#fbbf24;
  --bg:#ffffff; --bg-warm:#f7f4f0; --border:#eae3db; --border-dark:#3a302a;
  --radius:14px; --maxw:1180px;
  --f-display:"Archivo","Arial Narrow",system-ui,sans-serif;
  --f-body:"IBM Plex Sans",system-ui,-apple-system,Segoe UI,Roboto,sans-serif;
  --f-mono:"IBM Plex Mono",ui-monospace,SFMono-Regular,Menlo,monospace;
}
```
The brand-colour tokens (`--flame`, `--flame-deep`, `--ember`, `--ink`) are **injected from settings** via
an inline `:root` block so the admin "Brand colours" card recolours the whole site.

- Body text: `--text` on `--bg`; warm sections use `--bg-warm`.
- Headings: `--charcoal` (light bg) / `#fff` (dark). Page width capped at `--maxw` (1180px).
- Corner radius default `--radius` 14px.

## 3. Buttons

```css
.btn{ display:inline-flex; align-items:center; gap:9px; font-family:var(--f-display); font-weight:700;
      font-size:15px; letter-spacing:-.01em; padding:13px 22px; border-radius:11px; cursor:pointer;
      border:1.5px solid transparent; transition:transform .15s, box-shadow .2s, background .2s, color .2s, border-color .2s; }
.btn-flame{ background:linear-gradient(180deg,var(--flame) 0%,var(--flame-deep) 100%); color:#fff;
            box-shadow:0 8px 20px -8px rgba(234,88,12,.7); }       /* primary */
.btn-ghost{ border-color:var(--border); color:var(--charcoal); background:#fff; }   /* secondary */
.btn-ghost-dark{ border-color:var(--border-dark); color:#fff; background:transparent; } /* on dark */
.btn-sm{ font-family:var(--f-display); font-weight:700; font-size:13px; padding:9px 14px; border-radius:9px;
         background:var(--charcoal); color:#fff; display:inline-flex; align-items:center; gap:6px;
         border:none; cursor:pointer; }                            /* card "Add" pill */
```
- **Primary = flame gradient** (orange), white text, soft orange glow. Not navy, not flat.
- The card **Add** button is a small **charcoal** pill (`btn-sm`), not the flame button.

## 4. Product card — exact anatomy

> See [product-cards-frontend.md](product-cards-frontend.md) for behaviour/JS; this is the visual layer.

```css
.prod-card{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); overflow:hidden;
            display:flex; flex-direction:column; transition:transform .2s, box-shadow .25s; }
.prod-card:hover{ transform:translateY(-3px); box-shadow:0 14px 30px -20px rgba(40,20,0,.35); }

/* IMAGE: fixed height, white bg, CONTAIN (not cover) so products float, centered, no crop/jump */
.prod-media{ height:clamp(190px,21vw,250px); background:#fff; display:grid; place-items:center;
             position:relative; overflow:hidden; border-bottom:1px solid var(--border); color:var(--flame); }
.prod-media .pc-img{ position:absolute; inset:0; width:100%; height:100%; object-fit:contain; padding:16px; }
.prod-media .ph{ position:absolute; bottom:10px; right:12px; font-family:var(--f-mono); font-size:10px;
                 color:#c9a989; letter-spacing:.1em; }   /* "PHOTO" placeholder label */

.prod-body{ padding:18px 18px 20px; display:flex; flex-direction:column; flex:1; }
.prod-head{ display:flex; align-items:center; justify-content:space-between; gap:10px; }  /* sku ↔ swatches */
.prod-sku{ font-family:var(--f-mono); font-size:11px; color:var(--muted); letter-spacing:.08em; }
.prod-card h3{ font-family:var(--f-display); font-weight:700; margin:8px 0 0; }
.prod-foot{ margin-top:auto; padding-top:16px; display:flex; align-items:center; justify-content:space-between; gap:10px; }
.prod-price{ font-weight:700; }   /* "Enquire" with a small muted "for price" line under it */
```

Key visual decisions (what made the demo differ):
- **Images are `contain` on white**, not `cover` full-bleed.
- **Swatches sit top-right on the SKU line** (`.prod-head` space-between) — *not* stacked in the footer.
- **Primary action = flame gradient**, card Add = charcoal pill; headings Archivo, SKU/eyebrow mono.
- Card hover = soft shadow + 3px lift; swatch active = flame **border** (no shadow).

## 5. Eyebrows / section labels
Small uppercase mono labels in `--flame` or `--muted` (e.g. "POPULAR LINES", category names on cards).
```css
.eyebrow{ font-family:var(--f-mono); font-size:12px; letter-spacing:.14em; text-transform:uppercase; color:var(--flame); }
```

## 6. Add-to-cart control (how the Add button is produced)
`cart_control()` outputs a placeholder `<span class="js-cart-control" data-product='{json}' data-btnclass
data-label>`; the cart JS renders either an **Add** button or a **qty stepper** into it (when the item is
already in the cart). So don't hard-code the Add button — emit the placeholder and let `app.js` hydrate it.

## 7. Layout & storefront chrome

```css
.wrap{ max-width:var(--maxw); margin:0 auto; padding:0 24px; }   /* page container, 1180px */
section{ padding:88px 0; }                                       /* default section rhythm */
.site-header{ position:sticky; top:0; z-index:50; background:rgba(255,255,255,.92);
              backdrop-filter:saturate(140%) blur(10px); border-bottom:1px solid var(--border); }
.cat-bar{ background:#fff; border-bottom:1px solid var(--border); position:sticky; top:84px; z-index:40; }
.eyebrow{ font-family:var(--f-mono); font-size:12px; letter-spacing:.18em; text-transform:uppercase;
          color:var(--flame-deep); font-weight:600; display:inline-flex; align-items:center; gap:8px; }
```
- Sticky translucent header (blur). A secondary **category bar** sticks just under it (`top:84px`).
- Product-detail sections use a tighter `padding:44px 0` (class `.product-detail`) — the global 88px is too
  tall there.

## 8. Admin UI (used by every admin spec)

```css
.admin-shell{ display:grid; grid-template-columns:248px 1fr; min-height:100vh; background:var(--bg-warm); }
.admin-sidebar{ background:var(--ink); color:#cdbfb4; padding:22px 16px; position:sticky; top:0;
                height:100vh; display:flex; flex-direction:column; gap:4px; }   /* dark sidebar */
.admin-main{ padding:28px 32px 60px; min-width:0; }
.admin-topbar{ display:flex; align-items:center; justify-content:space-between; margin-bottom:26px;
               gap:16px; flex-wrap:wrap; }
.admin-topbar h1{ font-size:26px; font-weight:800; }
.admin-card{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:22px; }
.admin-table{ width:100%; border-collapse:collapse; background:#fff; border:1px solid var(--border);
              border-radius:var(--radius); overflow:hidden; }
.admin-table th{ text-align:left; font-family:var(--f-mono); font-size:11px; letter-spacing:.08em;
                 text-transform:uppercase; color:var(--muted); padding:12px 16px; background:var(--bg-warm);
                 border-bottom:1px solid var(--border); }
.admin-table td{ padding:12px 16px; border-bottom:1px solid var(--border); font-size:14px;
                 color:var(--charcoal); vertical-align:middle; }
```
- Admin = light `--bg-warm` canvas, **dark (`--ink`) sidebar**, white cards/tables, mono uppercase table
  headers. Primary actions use `.btn-flame`; secondary `.btn-ghost`.

## 9. Status badges

```css
.badge{ display:inline-block; font-family:var(--f-mono); font-size:10.5px; letter-spacing:.06em;
        text-transform:uppercase; padding:3px 8px; border-radius:6px; font-weight:600; }
.badge.published{ background:#e7f6ec; color:#1a7f43; }   /* also: "On", paid, info */
.badge.draft    { background:#f1ede8; color:var(--muted); }
.badge.new      { background:#fff0e6; color:var(--flame-deep); }
.badge.responded{ background:#e7f0fb; color:#2563eb; }
.badge.closed   { background:#f1ede8; color:var(--muted); }
```
Map statuses to these classes (product/blog status, enquiry status, order status, email on/off).

## 10. Admin form fields

```css
.field{ margin-bottom:18px; }
.field label{ display:block; font-family:var(--f-mono); font-size:12px; letter-spacing:.06em;
              text-transform:uppercase; color:var(--muted); margin-bottom:6px; }
.field input, .field select, .field textarea{ width:100%; padding:11px 13px; border:1px solid var(--border);
              border-radius:10px; font-size:15px; background:#fff; }
.field-row{ display:grid; grid-template-columns:1fr 1fr; gap:16px; }   /* two-up fields */
```
For inline-styled inputs the codebase also uses constants `ADM_INPUT` / `ADM_LABEL` with the same values.

## Acceptance
- Side-by-side, a rebuild matches: white card, contained centered product image, mono SKU top-left with
  circular swatches top-right, flame-gradient primary buttons, charcoal Add pill, Archivo headings, dark
  admin sidebar with white tables and mono uppercase headers.
