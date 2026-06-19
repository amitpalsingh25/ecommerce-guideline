# Admin → Blog

Path: **`/admin/blog`**

Manage articles/guides shown on the public blog.

## Post list

Columns: **Title**, **Category**, **Status** badge, **Published** date, **Edit**. "New post" creates one.

## New / edit post

| Field | Notes |
|-------|-------|
| **Title** | Required. |
| **Slug** | Blank = auto from title. Used in the URL. |
| **Category tag** | Free-text label (e.g. "Guide", "Basics"). |
| **Author** | Defaults to the site name. |
| **Status** | Draft or Published. |
| **Excerpt** | Short summary for cards/listings. |
| **Cover image** | Upload; shown on the post and in cards. |
| **Content** | Rich-text (WYSIWYG) editor; stored as HTML. |

Buttons: Save, Delete.

## Publishing behaviour

- **`published_at`** is set the **first time** a post is published, and preserved afterwards (re-saving a
  published post keeps the original date).
- Saving as Draft keeps it off the public blog.
- Only published posts appear on the public `/blog` and feed the homepage "From the blog" row.

## Notes

- Slug should stay stable once published (it's the article URL).
- Cover images go through the standard upload; large images are handled by the media pipeline.
