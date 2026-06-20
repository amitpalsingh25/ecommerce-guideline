# Build spec — Categories

> Tokens, layout & structure reference: [design-system.md](design-system.md).

**Goal:** CRUD for **self-nesting** categories used for navigation, filtering and URLs.

## Routes
- `GET /admin/categories` — tree + add form. `POST` (no id) → create.
- `GET /admin/categories/{id}` — edit. `POST /admin/categories/{id}` → update.
- `POST /admin/categories/{id}/delete`.

## Data — `categories`
id, parent_id (self-FK, nullable = nesting), name, slug (unique, URL), position (sort), description, image.

## Helpers to build
- `subtree_ids($id)` — id + all descendant ids (load all rows once, walk children map). Used by the
  product filter and category pages.
- `category_options()` / a tree builder with depth for indented selects and the admin list.

## Build — list + form (two columns)
1. Left: render the category **tree** indented by depth (`└` marker), each row showing product count,
   sub-cat count, Edit.
2. Right: the **add/edit form** — Name (required), Slug (blank=auto), **Parent** select (Top level or any
   category; a category can't be its own parent), Position (int), Description, Image upload.
3. Save: slugify; `parent_id` blank→NULL; guard self-parent; upload image if provided; insert/update.

## Frontend usage (build accordingly)
- Category page resolves the **full slug path** (`/category/a/b/c`) → deepest node + breadcrumbs +
  children + products (products filtered by `subtree_ids(node)`).

## Edge cases
- Deleting a category just removes the row; its products keep existing (category_id dangling/NULL) —
  don't cascade-delete products.
- Keep slugs stable after publishing (they're URLs).

## Acceptance
- Arbitrary nesting depth works; picking a parent in filters returns descendants' products too.
