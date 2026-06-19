# Admin → Hero

Path: **`/admin/hero`**

Controls the big banner at the top of the homepage. There are two **hero types**, chosen with a radio
picker at the top of the page. A **Hero height (px)** field is always visible and applies to both types.

---

## Hero type

### Slideshow
A rotating set of slides with heading, text and buttons.

**Display settings**

| Field | Effect |
|-------|--------|
| **Transition** | `Fade` or `Slide` between slides. |
| **Transition speed (ms)** | Animation duration. |
| **Auto-advance (seconds, 0 = off)** | Autoplay interval; `0` disables autoplay. |
| **Padding top / bottom (px)** | Vertical spacing inside the hero. |
| **Title / Sub-text / Eyebrow size (px)** | Type sizes for the slide text. |

**Slides** — add as many as needed; each slide has:

| Field | Notes |
|-------|-------|
| **Image** | Background image. Auto-resized to **≤ 1920 px** wide on upload. |
| **Eyebrow** | Small label above the heading. |
| **Alignment** | Left / Center / Right text alignment. |
| **Heading** | Main line. |
| **Highlighted word(s)** | Accent-coloured part of the heading. |
| **Sub text** | Supporting paragraph. |
| **Button 1 / Button 2** | Each has text + link (leave blank to omit). |
| **Reorder / Remove** | ↑/↓ move a slide; Remove deletes it. |

On the storefront: slides cross-fade or slide; if there's more than one, **dots + prev/next arrows**
appear, and autoplay runs if the interval is non-zero. Empty slide rows are ignored on save.

### Single image
One plain image, **no text overlay, no slides** — just a clean full-width banner.

- Upload a single **Hero image** (auto-resized to ≤ 1920 px).
- Uses the same **Hero height** field for the banner height.

Switching the radio to *Single image* hides the slideshow/slide editors; switching back hides the image
field. Whatever you don't use is preserved, so you can flip between modes without losing content.

---

## Save behaviour

- The whole hero config is saved at once (mode, height, display settings, image, and the full slide list).
- Slides are **replaced wholesale** on save; reorder/remove in the editor is the source of truth.
- Sizes and padding are applied via CSS variables, so the same slide content adapts to the values you set.

## Tips

- Keep hero images wide and not too tall; the **Hero height** crops them to the banner area.
- For a fast, image-only homepage banner, use **Single image** — it skips all slideshow JS and overlays.
