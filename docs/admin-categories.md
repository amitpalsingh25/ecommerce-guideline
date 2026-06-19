# Admin → Categories

Path: **`/admin/categories`**

The page is a two-column layout: the **category tree** on the left, an **Add category** form on the right.

## Category tree (list)

- Categories are shown as an indented **tree** — child categories are nested under their parent with a
  `└` marker and deeper left-padding per level.
- Each row shows: **Name**, **Products** count (products directly in that category), **Sub-cats** count,
  and an **Edit** button.

## Add / edit a category

| Field | Notes |
|-------|-------|
| **Name** | Required. |
| **Slug** | Blank = auto-generated from the name. Used in the URL. |
| **Parent** | "Top level" or any existing category — this is what creates nesting. A category can't be its own parent. |
| **Position** | Sort order among siblings (lower = first). |
| **Description** | Optional; shown on the category page. |
| **Image** | Optional category image (file upload). |

Editing opens the same form pre-filled, with **Save changes** and **Delete**.

## Nesting & URLs

- Nesting uses a self-relation (`parent_id`) and supports **arbitrary depth**.
- Category pages resolve the **full slug path**, e.g. `/category/<parent>/<child>/<grandchild>`, and show
  breadcrumbs plus any sub-categories and products.
- The product-list **category filter** matches the chosen category **and its whole sub-tree**, so picking
  a parent shows products from all descendants.

## Notes

- Deleting a category removes the category row; products that pointed at it simply lose that association
  (they remain, uncategorised) — re-assign them from the product editor if needed.
- Keep slugs stable once published; changing a slug changes the category URL.
