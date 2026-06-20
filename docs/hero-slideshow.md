# Build spec — Hero (admin + render)

> Tokens, layout & structure reference: [design-system.md](design-system.md).

**Goal:** editable homepage banner with two modes — **slideshow** and **single image** — managed at
`/admin/hero` and rendered by `render_hero()`.

## Route
- `GET/POST /admin/hero` → `admin_hero()` (after `require_admin()`).

## Data — `setting('hero')` (defaults via `hero()`)
```
mode: 'slideshow' | 'image'      // default 'slideshow'
image: ''                        // single-image source URL
height: 620                      // px — applies to BOTH modes
transition: 'fade'|'slide', speed: 600, interval: 6     // slideshow (interval sec, 0 = no autoplay)
padTop:96, padBottom:104, titleSize:84, subSize:20, eyebrowSize:12   // px
slides: [ { image, eyebrow, heading, accent, sub, ctaText, ctaLink, cta2Text, cta2Link, align } ]
```
`hero()` merges saved values over the defaults and, if `slides` is empty, seeds one starter slide.

---

## Admin build

### Layout
1. `<form method="post" id="hero-form">` with CSRF.
2. **Hero-type card** (always visible): two radios `name="mode"` (slideshow / image) calling `heroMode()`
   on change, **plus the single `name="height"` field** (see gotcha).
3. `#hero-image-only` card: one image uploader widget (thumb + file input + hidden `name="image"`).
4. `#hero-slideshow-only` wrapper: display fields (transition select, speed, interval, padTop/padBottom,
   titleSize/subSize/eyebrowSize) + a **Slides** section (`#hero-rows` + "Add slide" button).
5. Save button (outside the toggled wrappers so it's always visible).

### Slide row template — `hero_row_html($s=[])`
A `.hero-row` (with `data-uprow`) containing:
- a thumbnail + `<input type=file onchange="resizeUpload(this)">` + hidden `name="s_image[]"` (class
  `s-image`) + an `.up-status` element;
- inputs `s_eyebrow[]`, select `s_align[]` (left/center/right), `s_heading[]`, `s_accent[]`,
  textarea `s_sub[]`, `s_ctaText[]`, `s_ctaLink[]`, `s_cta2Text[]`, `s_cta2Link[]`;
- buttons: ↑/↓ (`moveSlide(this,-1|1)`), Remove (`this.closest('.hero-row').remove()`).

### JS (inline)
- `heroMode()` — show `#hero-image-only` xor `#hero-slideshow-only` from the checked radio; call once on load.
- `addSlide()` — `innerHTML` from a JSON-encoded empty `hero_row_html()` template, append to `#hero-rows`.
- `moveSlide(btn,dir)` — swap a row with its previous/next sibling.
- `resizeUpload(input)` — generic uploader keyed by `input.closest('[data-uprow]')`:
  load into an `Image`, canvas-downscale to **≤1920px wide**, `toBlob('image/jpeg', 0.85)`, POST as
  `file` to `/admin/upload`, then set the row's `.s-image`/hidden value + thumbnail + status. Both the
  single-image card and each slide row carry `data-uprow`, so one function serves all.

### Save (`admin_hero` POST)
1. CSRF.
2. Rebuild `slides`: iterate the parallel `s_*[]` arrays by index; **skip fully-empty rows**; whitelist
   `align` ∈ {left,center,right}.
3. Persist the whole object: `mode` (whitelist), `image`, `height` (min clamp), transition/speed/interval,
   padding + type sizes (sane min clamps), and `slides`.
4. Redirect back to `/admin/hero`.

### Gotchas
- **Only one `name="height"`** in the whole form. Don't put a height input in both the image card and the
  slideshow card — both submit and the later one wins. Keep it in the always-visible type card.
- Hidden (toggled-off) blocks still submit, so the unused mode's data is **preserved** across saves.
- The image field's hidden input is `name="image"`; slide images are `name="s_image[]"` — both use the
  shared `.s-image` class so `resizeUpload` works for either.

---

## Render build — `render_hero()`
- `mode==='image' && image` → output a plain full-bleed `<section class="hero hero-image">` with the image
  as `background` and `min-height:height`. **No overlay, no JS, no slides.**
- else → slideshow: each slide a positioned layer (image background + overlay + content: eyebrow,
  heading + accent span, sub, up to 2 CTA buttons, alignment class). When >1 slide, render **dots +
  prev/next arrows**. Expose `--hero-pt/pb/title/sub/eyebrow` as CSS variables and
  `data-transition/speed/interval` for the JS slideshow (`app.js`), which cross-fades or slides and
  autoplays when interval>0.

## Acceptance
- Switching mode + Save changes the homepage banner; single-image mode emits a clean banner with no
  overlay/JS; slideshow autoplays only when interval>0; reordering/removing slides in the editor is the
  source of truth on save; height applies in both modes.
