<?php
declare(strict_types=1);

// ---- Categories ----

function top_categories(): array
{
    return q_all('SELECT * FROM categories WHERE parent_id IS NULL ORDER BY position, name');
}

/** Flat, depth-ordered categories with rolled-up published product counts. */
function category_filter_tree(): array
{
    $cats = q_all('SELECT id, parent_id, name, slug FROM categories ORDER BY position, name');
    $self = [];
    foreach (q_all("SELECT category_id, COUNT(*) c FROM products WHERE status='published' GROUP BY category_id") as $r) {
        $self[(int)$r['category_id']] = (int)$r['c'];
    }
    $childrenOf = [];
    foreach ($cats as $c) $childrenOf[$c['parent_id'] ? (int)$c['parent_id'] : 0][] = $c;

    $rolled = [];
    $roll = function (int $id) use (&$roll, $childrenOf, $self): int {
        $total = $self[$id] ?? 0;
        foreach ($childrenOf[$id] ?? [] as $ch) $total += $roll((int)$ch['id']);
        return $total;
    };
    $out = [];
    $walk = function (int $parent, int $depth) use (&$walk, $childrenOf, $roll, &$out) {
        foreach ($childrenOf[$parent] ?? [] as $c) {
            $out[] = ['id' => (int)$c['id'], 'name' => $c['name'], 'slug' => $c['slug'], 'depth' => $depth, 'count' => $roll((int)$c['id'])];
            $walk((int)$c['id'], $depth + 1);
        }
    };
    $walk(0, 0);
    return $out;
}

/** Options for <select> (flat, depth-prefixed). */
function category_options(): array
{
    $t = category_filter_tree();
    return array_map(fn($n) => ['id' => $n['id'], 'name' => str_repeat('— ', $n['depth']) . $n['name']], $t);
}

function category_by_slug(string $slug): ?array
{
    return q_one('SELECT * FROM categories WHERE slug = ?', [$slug]);
}

/** Resolve a slug-path (array) to node + ancestor chain. */
function resolve_category_path(array $slugs): ?array
{
    if (!$slugs) return null;
    $chain = [];
    $parentId = null;
    foreach ($slugs as $slug) {
        $node = $parentId === null
            ? q_one('SELECT * FROM categories WHERE slug = ? AND parent_id IS NULL', [$slug])
            : q_one('SELECT * FROM categories WHERE slug = ? AND parent_id = ?', [$slug, $parentId]);
        if (!$node) return null;
        $chain[] = $node;
        $parentId = (int)$node['id'];
    }
    $node = end($chain);
    $children = q_all('SELECT * FROM categories WHERE parent_id = ? ORDER BY position, name', [(int)$node['id']]);
    return ['node' => $node, 'chain' => $chain, 'children' => $children, 'descendantIds' => subtree_ids((int)$node['id'])];
}

/** A category id + all descendant ids. */
function subtree_ids(int $id): array
{
    $all = q_all('SELECT id, parent_id FROM categories');
    $childrenOf = [];
    foreach ($all as $c) $childrenOf[$c['parent_id'] ? (int)$c['parent_id'] : 0][] = (int)$c['id'];
    $ids = [];
    $walk = function (int $x) use (&$walk, $childrenOf, &$ids) {
        $ids[] = $x;
        foreach ($childrenOf[$x] ?? [] as $ch) $walk($ch);
    };
    $walk($id);
    return $ids;
}

// ---- Products ----

function with_variants(array $products): array
{
    if (!$products) return [];
    $ids = array_column($products, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $vars = q_all("SELECT * FROM product_variants WHERE product_id IN ($in) ORDER BY position", $ids);
    $byProduct = [];
    foreach ($vars as $v) $byProduct[(int)$v['product_id']][] = $v;
    foreach ($products as &$p) $p['variants'] = $byProduct[(int)$p['id']] ?? [];
    return $products;
}

function order_clause(string $sort): string
{
    switch ($sort) {
        case 'title-asc':  return 'p.title ASC';
        case 'title-desc': return 'p.title DESC';
        default:           return 'p.created_at DESC, p.id DESC';
    }
}

/**
 * Paginated product query. opts: category_ids[], q, sort, page, perPage.
 * Returns ['items'=>[], 'total'=>int].
 */
function query_products(array $opts): array
{
    $perPage = $opts['perPage'] ?? 48;
    $page = max(1, (int)($opts['page'] ?? 1));
    $where = ["p.status = 'published'"];
    $params = [];

    if (!empty($opts['category_ids'])) {
        $in = implode(',', array_fill(0, count($opts['category_ids']), '?'));
        $where[] = "p.category_id IN ($in)";
        $params = array_merge($params, $opts['category_ids']);
    }
    if (!empty($opts['q'])) {
        $like = '%' . $opts['q'] . '%';
        $where[] = '(p.title LIKE ? OR p.sku LIKE ? OR p.description LIKE ? OR EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id AND (v.sku LIKE ? OR v.label LIKE ?)))';
        array_push($params, $like, $like, $like, $like, $like);
    }
    $whereSql = implode(' AND ', $where);

    $total = (int)q_val("SELECT COUNT(*) FROM products p WHERE $whereSql", $params);
    $order = order_clause($opts['sort'] ?? 'newest');
    $offset = ($page - 1) * $perPage;
    $rows = q_all(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug
         FROM products p LEFT JOIN categories c ON c.id = p.category_id
         WHERE $whereSql ORDER BY $order LIMIT $perPage OFFSET $offset",
        $params
    );
    return ['items' => with_variants($rows), 'total' => $total];
}

function featured_products(int $limit = 4): array
{
    $rows = q_all(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug
         FROM products p LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.status='published' AND p.featured=1 ORDER BY p.created_at DESC LIMIT $limit"
    );
    if (!$rows) {
        $rows = q_all(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.status='published' ORDER BY p.created_at DESC LIMIT $limit"
        );
    }
    return with_variants($rows);
}

function product_by_slug(string $slug): ?array
{
    $p = q_one(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug
         FROM products p LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.slug = ?",
        [$slug]
    );
    if (!$p) return null;
    return with_variants([$p])[0];
}

// ---- Blog ----

function recent_posts(int $limit = 3): array
{
    return q_all("SELECT * FROM blog_posts WHERE status='published' ORDER BY published_at DESC LIMIT $limit");
}

function all_posts(): array
{
    return q_all("SELECT * FROM blog_posts WHERE status='published' ORDER BY published_at DESC");
}

function post_by_slug(string $slug): ?array
{
    return q_one("SELECT * FROM blog_posts WHERE slug = ? AND status='published'", [$slug]);
}

/** Icon name for a product (by category slug) — mirrors the Next icon set. */
function product_icon(?string $categorySlug): string
{
    $map = [
        'fire-extinguishers' => 'extinguisher', 'abe-dry-powder' => 'extinguisher', 'co2-extinguishers' => 'extinguisher',
        'water-extinguishers' => 'extinguisher', 'foam-extinguishers' => 'extinguisher', 'wet-chemical' => 'extinguisher',
        'fire-hose-reels' => 'hose-reel', 'swing-type-reels' => 'hose-reel', 'hoses' => 'hose-reel', 'nozzles' => 'hose-reel',
        'hydrants-valves' => 'valve', 'valves' => 'valve', 'landing-valves' => 'valve', 'gate-valves' => 'valve',
        'ball-valves' => 'valve', 'hydrants' => 'valve',
        'cabinets-storage' => 'cabinet', 'hydrant-cabinets' => 'cabinet',
        'fire-signage' => 'sign',
        'pipe-fittings' => 'fittings', 'brass-fittings' => 'fittings', 'gal-mal-fittings' => 'fittings',
        'bi-fittings' => 'fittings', 'storz-fittings' => 'fittings', 'camlocks' => 'fittings',
        'black-steel-nipples' => 'fittings', 'stainless-steel-316-fittings' => 'fittings',
        'grooved-pipe-fittings' => 'fittings', 'pipes' => 'fittings',
    ];
    return $map[$categorySlug ?? ''] ?? 'extinguisher';
}
