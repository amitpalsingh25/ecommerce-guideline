# Build spec — Blog

**Goal:** CRUD for articles rendered on the public blog.

## Routes
- `GET /admin/blog` — list. `GET /admin/blog/new`, `GET /admin/blog/{id}` — form.
- `POST` → `admin_blog_save()`. `POST /admin/blog/{id}/delete`.

## Data — `blog_posts`
id, title, slug (unique, URL), excerpt, content TEXT (HTML), cover_image, category (free-text tag),
author, status enum(draft,published), published_at.

## Build — list
Table: Title, Category, Status badge, Published date, Edit. "New post" button.

## Build — form
Title (required), Slug (blank=auto), Category tag, Author (default = site name), Status, Excerpt,
Cover image (upload), **Content** `<textarea class="richtext">` (WYSIWYG → HTML). Save + Delete.

## Build — save
1. CSRF; require title; slugify.
2. Upload cover if provided (else keep existing).
3. **published_at**: set to now the first time status becomes `published`; preserve it afterwards; keep
   existing when reverting to draft.
4. Insert/update.

## Frontend
Only `published` posts appear on `/blog`, the post page, and the homepage "From the blog" row. Render
`content` as HTML; use excerpt + cover on cards.

## Acceptance
- Publishing stamps a date once and keeps it; drafts stay hidden.
