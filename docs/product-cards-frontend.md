# Build spec — Product display cards (frontend)

> Visual tokens (fonts/spacing/buttons): [design-system.md](design-system.md). This doc = **structure +
> behaviour**, which matters more than colour.

**Goal:** the product card used in grids and the **Featured** carousel — with **variant-image swatches**
that swap the card image on **click**, no layout jump, and a clean hover. Vanilla CSS + JS, no framework.

Used on: home (Featured), `/products`, category pages. Same component everywhere.

## Structure & invariants (get this right — it's what makes cards look consistent)

The card is a **flex column with 4 fixed zones**, in this order:

```
.prod-card (flex column, equal height in a row)
 ├─ .prod-media     fixed height, image contain  ── ALWAYS same height across all cards
 └─ .prod-body (flex column, flex:1)
     ├─ .prod-head  ONE row: SKU/category (left)  ↔  swatches (right)   ← swatches live HERE
     ├─ h3          title (can wrap to 2 lines)
     └─ .prod-foot  margin-top:auto → ONE row: price (left) ↔ Add/View (right)
```

**Invariants (these are what the demo violated):**
1. **Swatches belong in `.prod-head` (the meta line), never in the footer.** Putting them above the
   buttons makes only *some* cards taller and knocks the buttons out of alignment with their neighbours.
2. **`.prod-foot` is a single price↔action row, pinned with `margin-top:auto`.** That keeps the footer at
   the bottom so all cards in a row end at the same baseline regardless of title length or swatch count.
3. **`.prod-media` is a fixed height with `object-fit:contain`** (not a tall `cover` crop). Same image box
   on every card; the product floats centered; nothing jumps when swatches swap the image.
4. **One compact action**, not two full-width stacked buttons: price on the left, a single small **Add**
   (or **View** for variant products) on the right.
5. Card body uses `flex:1` so short and long titles still produce equal-height cards.

Result: a clean grid where every card is the same height, the meta row carries the swatches, and the
footer actions line up across the row — even when one product has swatches and another doesn't.

---

## Markup — `product_card($p, $selling)`

```html
<div class="prod-card reveal">
  <a href="{href}" class="prod-media">
    <img class="pc-img" src="{firstImage}" alt="{title}">   <!-- or icon + <span class="ph">PHOTO</span> -->
  </a>
  <div class="prod-body">
    <div class="prod-head">
      <span class="prod-sku">{sku or "N sizes"}</span>
      <!-- swatches sit on the RIGHT of the sku line -->
      <div class="pc-swatches" data-first="{firstImage}">
        <button class="pc-swatch" data-img="{url}" style="background-image:url('{url}')"></button>
        ... up to 5 ...
        <span class="pc-swatch-more">+{N}</span>           <!-- overflow when >5 -->
      </div>
    </div>
    <h3><a href="{href}">{title}</a></h3>
    <div class="prod-foot">{price-or-enquire}{Add or View}</div>
  </div>
</div>
```

### Image collection (PHP)
- `imgs = product.images + every variant.image` (de-duplicated, order preserved).
- If the product has **no main image but a variant does**, use the first variant image (not the
  placeholder).
- `firstImage = imgs[0]`. Render the swatch row only when `count(imgs) > 1`; show **up to 5** swatches, then
  a `+N` pill.

## JS — `initSwatches()` (in `app.js`)

For each `.pc-swatches`:
1. Find the card's `.pc-img`.
2. **Preload** every swatch `data-img` (`new Image()`).
3. On a swatch **click** (not hover): set `img.src = data-img`, mark that swatch `.active` (clear others),
   and **retrigger the slide animation** — remove `pc-slide`, force reflow (`void img.offsetWidth`),
   re-add `pc-slide`.

> History: this started as auto-shuffle + hover; final spec is **click only** (no hover, no leave-reset)
> with an **ease slide** transition (not fade).

## CSS

```css
/* fixed, responsive media box → image changes never shift layout */
.prod-media { height: clamp(190px, 21vw, 250px); background:#fff; display:grid; place-items:center;
              position:relative; overflow:hidden; border-bottom:1px solid var(--border); }
.prod-media .pc-img { position:absolute; inset:0; width:100%; height:100%;
                      object-fit:contain; padding:16px; }      /* contain + centered, no crop/jump */
.pc-img.pc-slide { animation: pcSlide .35s ease; }
@keyframes pcSlide { from{ transform:translateX(24px); opacity:0 } to{ transform:translateX(0); opacity:1 } }

.prod-card { background:#fff; border:1px solid var(--border); border-radius:var(--radius);
             overflow:hidden; transition:transform .2s, box-shadow .25s; }
.prod-card:hover { transform:translateY(-3px);                 /* CARD = shadow, not border */
                   box-shadow:0 14px 30px -20px rgba(40,20,0,.35); }

.prod-head { display:flex; align-items:center; justify-content:space-between; gap:10px; }
.pc-swatches { display:flex; align-items:center; gap:6px; flex-wrap:wrap; justify-content:flex-end; }
.pc-swatch { width:30px; height:30px; border-radius:50%; border:1px solid var(--border);
             background:#fff center/cover no-repeat; cursor:pointer; padding:0; }
.pc-swatch:hover, .pc-swatch.active { border-color:var(--flame); border-width:2px; }  /* SWATCH = border, no shadow */
.pc-swatch-more { font-family:var(--f-mono); font-size:12px; color:var(--muted); }
```

## Featured carousel (same cards)

- When featured count > cards-per-row, wrap the cards in a horizontal **scroll-snap** track with prev/next
  arrows (see [Settings → Featured](admin-settings.md) for the config). Track needs vertical padding so the
  card hover shadow isn't clipped by `overflow`; arrows sit in the gutter (`left/right:-28px`); cards
  scale to 2-up / 1-up on smaller screens. Arrows hidden on mobile.

## Gotchas (learned the hard way)

- **Don't let the card's `.prod-media` rules leak into the product-detail gallery.** The detail page reuses
  `.prod-media` with `.has-gallery`; reset it there: `height:auto; overflow:visible; aspect-ratio:auto`.
  Otherwise the fixed card height/overflow clips the detail thumbnail strip.
- Card hover = **shadow**; swatch active = **border** (no ring/shadow). Keep them distinct.
- Use **fixed height + `object-fit:contain`** so swapping uneven images never jumps the layout.
- Swatches go on the **right** of the sku line (`prod-head` space-between), not below the title.

## Acceptance
- A product with variant photos shows a swatch row; **clicking** a swatch slides in that image (no jump),
  marks it active; `+N` appears past 5; cards hover with a soft shadow; the detail-page gallery is
  unaffected; the same cards work inside the Featured carousel.
