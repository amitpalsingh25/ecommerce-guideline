# Build spec — Hero

**Goal:** editable homepage banner with two modes — **slideshow** and **single image**.

## Route
- `GET/POST /admin/hero` → `admin_hero()`.

## Data — `setting('hero')` (with defaults via `hero()`)
```
mode: 'slideshow' | 'image'      // default 'slideshow'
image: ''                        // single-image source
height: 620                      // px (applies to both modes)
transition: 'fade'|'slide', speed: 600, interval: 6   // slideshow
padTop, padBottom, titleSize, subSize, eyebrowSize    // px
slides: [ { image, eyebrow, heading, accent, sub, ctaText, ctaLink, cta2Text, cta2Link, align } ]
```

## Build — admin
1. **Type radio**: Slideshow vs Single image. JS toggles which block is visible. A **Hero height** field
   is always visible (one `name="height"` only — don't duplicate it across hidden blocks or POST collides).
2. **Single image block**: one image uploader → hidden `image` field.
3. **Slideshow block**: display fields (transition/speed/interval/padding/type sizes) + a repeatable
   **slides** editor (image upload, eyebrow, alignment, heading, accent, sub, 2× button text+link,
   up/down reorder, remove, "add slide").
4. Image uploads: resize client-side to ≤1920px, POST to the AJAX upload endpoint, store returned URL in
   the row's hidden field.
5. **Save**: rebuild the `slides` array from POST (skip fully-empty rows), persist whole `hero` object.

## Build — render (`render_hero()`)
- `mode==='image' && image` → output a plain full-bleed `<section>` with the image as background, **no
  overlay/JS**.
- else → slideshow: slides as layers; cross-fade or slide; show dots + prev/next when >1; autoplay when
  interval>0; expose sizes/padding as CSS variables.

## Edge cases
- Single `height` input shared by both modes (avoid duplicate POST keys).
- Hidden blocks still submit, so unused mode's data is preserved across saves.

## Acceptance
- Switching mode + save changes the homepage banner accordingly; slideshow autoplays only when interval>0.
