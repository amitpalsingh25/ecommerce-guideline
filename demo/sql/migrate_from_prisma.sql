-- Migrate data from the Prisma DB (`firesafe`) into the PHP DB (`firesafe_php`).
-- Mapping cuid string ids -> new int ids via unique slugs.

-- Categories (parents first via NULL, then link)
INSERT INTO firesafe_php.categories (name, slug, description, image, position)
SELECT name, slug, description, image, position FROM firesafe.Category;

UPDATE firesafe_php.categories nc
JOIN firesafe.Category oc ON oc.slug = nc.slug
JOIN firesafe.Category op ON op.id = oc.parentId
JOIN firesafe_php.categories np ON np.slug = op.slug
SET nc.parent_id = np.id;

-- Products
INSERT INTO firesafe_php.products (category_id, sku, title, slug, description, price, images, status, featured)
SELECT np.id, op.sku, op.title, op.slug, op.description, op.price,
       CAST(op.images AS CHAR), op.status, op.featured
FROM firesafe.Product op
LEFT JOIN firesafe.Category oc ON oc.id = op.categoryId
LEFT JOIN firesafe_php.categories np ON np.slug = oc.slug;

-- Variants
INSERT INTO firesafe_php.product_variants (product_id, sku, label, price, position)
SELECT np.id, ov.sku, ov.label, ov.price, ov.position
FROM firesafe.ProductVariant ov
JOIN firesafe.Product op ON op.id = ov.productId
JOIN firesafe_php.products np ON np.slug = op.slug;

-- Blog posts
INSERT INTO firesafe_php.blog_posts (title, slug, excerpt, content, cover_image, category, status, author, published_at)
SELECT title, slug, excerpt, content, coverImage, category, status, author, publishedAt
FROM firesafe.BlogPost;

-- Admin users (bcrypt hashes from bcryptjs are verified by PHP password_verify)
INSERT INTO firesafe_php.users (email, password_hash, name, role)
SELECT email, passwordHash, name, role FROM firesafe.User WHERE passwordHash IS NOT NULL;

-- Settings (toggles/general are plain JSON; encrypted secrets were never set)
INSERT INTO firesafe_php.settings (`key`, value)
SELECT `key`, CAST(value AS CHAR) FROM firesafe.Setting;
