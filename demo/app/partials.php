<?php
declare(strict_types=1);

/** A cart "Add" control that JS hydrates (Add button <-> qty stepper). */
function cart_control(array $product, string $btnClass = 'btn-sm', string $label = 'Add'): string
{
    $json = htmlspecialchars(json_encode($product, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
    return '<span class="js-cart-control" data-product="' . $json . '" data-btnclass="' . e($btnClass) . '" data-label="' . e($label) . '"></span>';
}

function price_or_enquire($price, bool $selling): string
{
    $f = $selling ? money($price) : null;
    if ($f) return '<span class="prod-price">' . e($f) . '</span>';
    return '<span class="prod-price">Enquire<span>for price</span></span>';
}

function product_card(array $p, bool $selling): string
{
    $href = url('products/' . $p['slug']);
    $hasVar = !empty($p['variants']);
    $imgs = json_arr($p['images'] ?? null);
    if ($hasVar) {
        foreach ($p['variants'] as $v) { $vi = trim((string)($v['image'] ?? '')); if ($vi !== '' && !in_array($vi, $imgs, true)) $imgs[] = $vi; }
    }
    $img = $imgs[0] ?? null;
    $media = $img
        ? '<img class="pc-img" src="' . e($img) . '" alt="' . e($p['title']) . '">'
        : icon(product_icon($p['category_slug'] ?? null)) . '<span class="ph">PHOTO</span>';
    $skuLine = $p['sku'] ?: ($hasVar ? (count($p['variants']) . ' sizes') : '');

    // Variant-image swatches (hover to preview), with +N overflow.
    $swatches = '';
    if (count($imgs) > 1) {
        $max = 5;
        $shown = array_slice($imgs, 0, $max);
        $extra = count($imgs) - count($shown);
        $sw = '';
        foreach ($shown as $si) {
            $sw .= '<button type="button" class="pc-swatch" data-img="' . e($si) . '" style="background-image:url(\'' . e($si) . '\')" aria-label="Preview image"></button>';
        }
        if ($extra > 0) $sw .= '<span class="pc-swatch-more">+' . $extra . '</span>';
        $swatches = '<div class="pc-swatches" data-first="' . e($imgs[0]) . '">' . $sw . '</div>';
    }

    $foot = price_or_enquire($p['price'] ?? null, $selling);
    if ($hasVar) {
        $foot .= '<a href="' . $href . '" class="btn-sm view-sizes">View ' . icon('arrow-right') . '</a>';
    } else {
        $foot .= cart_control([
            'productId' => $p['sku'] ?: ('p' . $p['id']),
            'sku' => $p['sku'] ?: ('p' . $p['id']),
            'title' => $p['title'], 'slug' => $p['slug'], 'image' => $img,
            'price' => $selling ? ($p['price'] !== null ? (float)$p['price'] : null) : null,
        ]);
    }

    return '<div class="prod-card reveal">'
        . '<a href="' . $href . '" class="prod-media">' . $media . '</a>'
        . '<div class="prod-body"><div class="prod-head"><span class="prod-sku">' . e($skuLine) . '</span>' . $swatches . '</div>'
        . '<h3><a href="' . $href . '">' . e($p['title']) . '</a></h3>'
        . '<div class="prod-foot">' . $foot . '</div></div></div>';
}

function category_card(array $c, ?string $href = null): string
{
    $href = $href ?: url('category/' . $c['slug']);
    return '<a class="cat-card reveal" href="' . $href . '">'
        . '<div class="cat-top"><div class="cat-ic">' . icon(product_icon($c['slug'])) . '</div></div>'
        . '<h3>' . e($c['name']) . '</h3>'
        . ($c['description'] ? '<p>' . e($c['description']) . '</p>' : '')
        . '<span class="cat-link">View range ' . icon('arrow-right') . '</span></a>';
}

function blog_card(array $post): string
{
    $href = url('blog/' . $post['slug']);
    $media = !empty($post['cover_image'])
        ? '<img src="' . e($post['cover_image']) . '" alt="' . e($post['title']) . '">'
        : icon('sign', 'icon pm-ic', 'width:42px;height:42px');
    return '<a class="post-card reveal" href="' . $href . '">'
        . '<div class="post-media">' . ($post['category'] ? '<span class="post-cat">' . e($post['category']) . '</span>' : '') . $media . '</div>'
        . '<div class="post-body"><span class="post-date">' . e(fmt_date($post['published_at'])) . '</span>'
        . '<h3>' . e($post['title']) . '</h3>'
        . ($post['excerpt'] ? '<p>' . e($post['excerpt']) . '</p>' : '')
        . '<span class="post-read">Read article ' . icon('arrow-right') . '</span></div></a>';
}

/** Windowed pagination. $hrefFn(int $page): string */
function pagination_html(int $page, int $totalPages, callable $hrefFn): string
{
    if ($totalPages <= 1) return '';
    $win = [];
    if ($totalPages <= 7) { $win = range(1, $totalPages); }
    else {
        $set = [1, $totalPages, $page, $page - 1, $page + 1];
        $set = array_values(array_unique(array_filter($set, fn($n) => $n >= 1 && $n <= $totalPages)));
        sort($set);
        $prev = 0;
        foreach ($set as $n) { if ($n - $prev > 1) $win[] = '…'; $win[] = $n; $prev = $n; }
    }
    $h = '<nav class="pagination" aria-label="Pagination">';
    $h .= $page > 1
        ? '<a href="' . e($hrefFn($page - 1)) . '" class="page-link">' . icon('arrow-right', 'icon', 'transform:rotate(180deg);width:16px;height:16px') . '</a>'
        : '<span class="page-link disabled">' . icon('arrow-right', 'icon', 'transform:rotate(180deg);width:16px;height:16px') . '</span>';
    foreach ($win as $it) {
        if ($it === '…') { $h .= '<span class="page-gap">…</span>'; continue; }
        $h .= $it === $page
            ? '<span class="page-link active">' . $it . '</span>'
            : '<a href="' . e($hrefFn($it)) . '" class="page-link">' . $it . '</a>';
    }
    $h .= $page < $totalPages
        ? '<a href="' . e($hrefFn($page + 1)) . '" class="page-link">' . icon('arrow-right', 'icon', 'width:16px;height:16px') . '</a>'
        : '<span class="page-link disabled">' . icon('arrow-right', 'icon', 'width:16px;height:16px') . '</span>';
    return $h . '</nav>';
}

/** Variant size table (product detail) — each row hydrated by JS. */
function variant_table(array $product, bool $selling): string
{
    $img = json_arr($product['images'] ?? null)[0] ?? null;
    $anyImg = false;
    foreach ($product['variants'] as $v) { if (trim((string)($v['image'] ?? '')) !== '') { $anyImg = true; break; } }

    // image col (only if some variant has one) · label fills · part no & action fit content
    $cols = ($anyImg ? '56px ' : '') . 'minmax(0,1fr) max-content' . ($selling ? ' max-content' : '') . ' max-content';
    $colStyle = 'grid-template-columns:' . $cols;

    $h = '<div class="variant-table"><div class="variant-row variant-head" style="' . $colStyle . '">'
        . ($anyImg ? '<span></span>' : '')
        . '<span>Size / Option</span><span>Part No.</span>' . ($selling ? '<span style="text-align:right">Price</span>' : '') . '<span></span></div>';
    foreach ($product['variants'] as $v) {
        $hasOwn = trim((string)($v['image'] ?? '')) !== '';
        $vimg = $hasOwn ? $v['image'] : $img;
        $ctrl = cart_control([
            'productId' => $v['sku'], 'sku' => $v['sku'],
            'title' => $product['title'] . ' — ' . $v['label'], 'slug' => $product['slug'], 'image' => $vimg,
            'price' => $selling ? ($v['price'] !== null ? (float)$v['price'] : null) : null,
        ], 'btn-sm', 'Add');
        $rowAttr = ' style="' . $colStyle . ($vimg ? ';cursor:pointer' : '') . '"' . ($vimg ? ' data-img="' . e($vimg) . '"' : '');
        $imgCell = $anyImg ? '<span class="variant-imgcell">' . ($hasOwn ? '<img class="variant-thumb" src="' . e($v['image']) . '" alt="">' : '') . '</span>' : '';
        $h .= '<div class="variant-row"' . $rowAttr . '>'
            . $imgCell
            . '<span class="variant-label">' . e($v['label']) . '</span>'
            . '<span class="variant-sku">' . e($v['sku']) . '</span>'
            . ($selling ? '<span class="variant-price">' . e(money($v['price']) ?? 'Enquire') . '</span>' : '')
            . '<span class="variant-action">' . $ctrl . '</span></div>';
    }
    return $h . '</div>';
}
