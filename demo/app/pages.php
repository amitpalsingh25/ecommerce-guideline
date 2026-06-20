<?php
declare(strict_types=1);
require __DIR__ . '/partials.php';

function render_hero(): string
{
    $h = hero();
    $height = max(280, (int)($h['height'] ?? 620));

    // Single-image mode: just the image, no slides, no text overlay.
    if (($h['mode'] ?? 'slideshow') === 'image' && !empty($h['image'])) {
        return '<section class="hero hero-image" style="min-height:' . $height . 'px;background-image:url(\'' . e($h['image']) . '\')"></section>';
    }

    $trans = in_array($h['transition'] ?? 'fade', ['fade', 'slide'], true) ? $h['transition'] : 'fade';
    $speed = max(150, (int)($h['speed'] ?? 600));
    $interval = max(0, (int)($h['interval'] ?? 6));
    $vars = '--hero-pt:' . max(0, (int)($h['padTop'] ?? 96)) . 'px'
        . ';--hero-pb:' . max(0, (int)($h['padBottom'] ?? 104)) . 'px'
        . ';--hero-title:' . max(16, (int)($h['titleSize'] ?? 84)) . 'px'
        . ';--hero-sub:' . max(11, (int)($h['subSize'] ?? 20)) . 'px'
        . ';--hero-eyebrow:' . max(9, (int)($h['eyebrowSize'] ?? 12)) . 'px';

    $slidesHtml = '';
    foreach ($h['slides'] as $s) {
        $align = in_array($s['align'] ?? 'center', ['left', 'center', 'right'], true) ? $s['align'] : 'center';
        $hasImg = !empty($s['image']);
        $bg = $hasImg ? "background-image:url('" . e($s['image']) . "')" : '';
        $cta = '';
        if (!empty($s['ctaText'])) $cta .= '<a href="' . e($s['ctaLink'] ?: '#') . '" class="btn btn-flame">' . e($s['ctaText']) . ' ' . icon('arrow-right') . '</a>';
        if (!empty($s['cta2Text'])) $cta .= '<a href="' . e($s['cta2Link'] ?: '#') . '" class="btn btn-ghost-dark">' . e($s['cta2Text']) . '</a>';
        $content = '<div class="wrap hero-content align-' . $align . '">'
            . (!empty($s['eyebrow']) ? '<span class="hero-eyebrow">' . e($s['eyebrow']) . '</span>' : '')
            . (!empty($s['heading']) ? '<h1>' . e($s['heading']) . (!empty($s['accent']) ? ' <span class="accent">' . e($s['accent']) . '</span>' : '') . '</h1>' : '')
            . (!empty($s['sub']) ? '<p class="hero-sub">' . e($s['sub']) . '</p>' : '')
            . ($cta ? '<div class="hero-cta">' . $cta . '</div>' : '')
            . '</div>';
        $glow = $hasImg ? '<span class="hero-overlay"></span>' : '<div class="hero-glow"></div>';
        $slidesHtml .= '<div class="hero-slide' . ($hasImg ? ' has-img' : '') . '" style="' . $bg . '">' . $glow . $content . '</div>';
    }

    $dots = '';
    if (count($h['slides']) > 1) {
        $dots = '<div class="hero-dots">';
        foreach ($h['slides'] as $i => $s) $dots .= '<button class="hero-dot' . ($i === 0 ? ' active' : '') . '" data-go="' . $i . '" aria-label="Slide ' . ($i + 1) . '"></button>';
        $dots .= '</div>';
        $dots .= '<button class="hero-arrow prev" data-prev aria-label="Previous">' . icon('arrow-right', 'icon', 'transform:rotate(180deg)') . '</button>'
            . '<button class="hero-arrow next" data-next aria-label="Next">' . icon('arrow-right') . '</button>';
    }

    return '<section class="hero hero-slider" data-transition="' . $trans . '" data-speed="' . $speed . '" data-interval="' . $interval . '" style="min-height:' . $height . 'px;' . $vars . '">'
        . '<div class="hero-track">' . $slidesHtml . '</div>' . $dots . '</section>';
}

/** Grouped category directory: parent heading + child links, in columns. */
function render_category_directory(): string
{
    $tops = top_categories();
    $h = '<div class="cat-dir">';
    foreach ($tops as $top) {
        $children = q_all('SELECT slug, name FROM categories WHERE parent_id = ? ORDER BY position, name', [(int)$top['id']]);
        $h .= '<div class="cat-dir-group"><h3><a href="' . url('category/' . $top['slug']) . '">' . e($top['name']) . '</a></h3>';
        if ($children) {
            $h .= '<ul>';
            foreach ($children as $c) {
                $h .= '<li><a href="' . url('category/' . $top['slug'] . '/' . $c['slug']) . '">' . e($c['name']) . '</a></li>';
            }
            $h .= '</ul>';
        } else {
            $h .= '<ul><li><a href="' . url('category/' . $top['slug']) . '">View range</a></li></ul>';
        }
        $h .= '</div>';
    }
    return $h . '</div>';
}

function section_head(string $eyebrow, string $title, string $lead = ''): string
{
    return '<div class="section-head reveal"><span class="eyebrow">' . e($eyebrow) . '</span><h2>' . e($title) . '</h2>'
        . ($lead ? '<p class="lead">' . e($lead) . '</p>' : '') . '</div>';
}

function page_home(): void
{
    $tog = toggles();
    $cats = top_categories();
    $fcfg = featured_cfg();
    $featured = featured_products(max(1, (int)$fcfg['limit']));
    $posts = recent_posts(3);

    $catGrid = '<div class="cat-grid">';
    foreach ($cats as $c) $catGrid .= category_card($c);
    $catGrid .= '</div>';

    $pv = max(1, (int)$fcfg['perView']);
    $cards = '';
    foreach ($featured as $p) $cards .= product_card($p, $tog['sellingEnabled']);
    if (!empty($fcfg['carousel']) && count($featured) > $pv) {
        $prodGrid = '<div class="feat-carousel" style="--pv:' . $pv . '" data-autoplay="' . (int)$fcfg['autoplay'] . '">'
            . '<button type="button" class="feat-arrow prev" aria-label="Previous">' . icon('arrow-right', 'icon', 'transform:rotate(180deg)') . '</button>'
            . '<div class="feat-track">' . $cards . '</div>'
            . '<button type="button" class="feat-arrow next" aria-label="Next">' . icon('arrow-right') . '</button>'
            . '</div>';
    } else {
        $prodGrid = '<div class="prod-grid">' . $cards . '</div>';
    }

    $blogGrid = '<div class="post-grid">';
    foreach ($posts as $p) $blogGrid .= blog_card($p);
    $blogGrid .= '</div>';

    $chips = ['Built to Australian standards', 'Australia-wide dispatch', 'Trade & bulk pricing', 'Real product support'];
    $chipHtml = '';
    foreach ($chips as $c) $chipHtml .= '<span class="chip">' . icon('check') . e($c) . '</span>';

    $steps = [
        ['1', 'Browse the catalogue', 'Find the products your site needs across our full range.'],
        ['2', 'Add to your cart', "Build a list of everything you'd like priced — no account required."],
        ['3', 'Send your enquiry', 'We reply with pricing, stock and lead times, usually within a business day.'],
    ];
    $stepHtml = '';
    foreach ($steps as $s) $stepHtml .= '<div class="step reveal"><div class="step-num">' . $s[0] . '</div><h3>' . e($s[1]) . '</h3><p>' . e($s[2]) . '</p>' . ($s[0] !== '3' ? icon('arrow-right', 'step-arrow icon') : '') . '</div>';

    $values = [
        ['shield-check', 'Compliance-focused range', 'Gear selected to meet Australian fire safety standards.'],
        ['truck', 'Fast dispatch', 'Stocked lines shipped Australia-wide with quick turnaround.'],
        ['headset', 'Expert support', 'Talk to people who know fire equipment, not a call centre.'],
        ['gem', 'Trade accounts', 'Bulk and contractor pricing for regular buyers.'],
    ];
    $valHtml = '';
    foreach ($values as $v) $valHtml .= '<div class="value-item reveal"><div class="value-ic">' . icon($v[0]) . '</div><div><h4>' . e($v[1]) . '</h4><p>' . e($v[2]) . '</p></div></div>';

    // Heredoc interpolates {$var}, so define everything it references as vars.
    $heroSlider = render_hero();
    $u_products = url('products');
    $u_contact = url('contact');
    $u_blog = url('blog');
    $ico_arrow = icon('arrow-right');
    $ico_phone = icon('phone');
    $ico_flame_big = icon('flame', 'ac-flame');
    $phone_raw = CO_PHONE_RAW;
    $phone = CO_PHONE;
    $catHead = section_head('Product Categories', 'Shop by category', 'Browse our product categories below to view the full range of fire-fighting equipment and industrial plumbing products. Each category links to detailed product listings with specifications and technical information.');
    $catDir = render_category_directory();

    $uc = url('contact');
    $welcome = '<section class="welcome"><div class="wrap">'
        . '<div class="section-head reveal" style="max-width:780px;margin:0 auto 36px;text-align:center"><span class="eyebrow" style="justify-content:center">Welcome</span>'
        . '<h2>Welcome to Fire Safe Australia</h2><p class="lead">Fire-fighting equipment &amp; industrial plumbing products, Australia-wide.</p></div>'
        . '<div class="prose-content reveal" style="max-width:900px;margin:0 auto">'
        . '<p>Fire Safe Australia is a Melbourne-based, family-owned Australian company supplying a comprehensive range of fire-fighting equipment and industrial plumbing products across Australia. With a strong national distribution network, we support fire services, contractors, and industrial projects nationwide.</p>'
        . '<p>We specialise in reliable, compliant, and competitively priced fire protection products designed to meet Australian and international standards.</p>'
        . '<h3>Trusted Australian supplier to fire services &amp; industry</h3>'
        . '<p>With many years of industry experience, Fire Safe Australia has built a strong reputation as a trusted supplier to the fire services and industrial plumbing industry. Our experienced management team, engineers, and technical staff work closely with customers to deliver quality products, dependable service, and technical expertise.</p>'
        . '<p>Whether you require standard fire protection components or specialised solutions, we are committed to supplying products that meet the performance, safety, and compliance requirements of modern fire protection systems.</p>'
        . '<h3>Comprehensive fire protection product range</h3>'
        . '<p>We offer a complete range of fire-fighting equipment and accessories supplied through our national distribution network, including:</p>'
        . '<ul><li>Fire hoses &amp; hose accessories</li><li>Fire hydrant fittings &amp; valves</li><li>Fire hose reels &amp; cabinets</li><li>Industrial plumbing components</li><li>Safety signs &amp; labels</li><li>Fire protection system accessories</li></ul>'
        . '<p>A new Fire Safe Australia catalogue has been finalised, showcasing our complete product range and technical specifications. <a href="' . $uc . '">Contact us</a> to request a copy.</p>'
        . '<h3>Compliance, certifications &amp; technical documentation</h3>'
        . '<p>All products supplied by Fire Safe Australia are designed to meet relevant Australian and international standards, ensuring safety, reliability, and regulatory compliance.</p>'
        . '<p>Data sheets and specification documents are available on request, including compliance with: <strong>AS, UL, FM, DIN, ULC, NEN 3374</strong> and <strong>WaterMark</strong> certification.</p>'
        . '<p>This makes Fire Safe Australia a reliable partner for commercial, industrial, and fire protection projects where compliance is critical.</p>'
        . '<h3>Australian owned. Industry focused. Quality driven.</h3>'
        . '<p>Based in Melbourne, Fire Safe Australia remains proudly Australian owned and family operated. We focus on supplying high-quality fire protection and industrial plumbing products at competitive prices, without compromising on safety or performance.</p>'
        . '<p>Our long-term relationships with fire services, installers, and industry professionals are built on:</p>'
        . '<ul><li>Consistent product quality</li><li>Technical support and industry knowledge</li><li>Reliable supply across Australia</li></ul>'
        . '</div></div></section>';

    $h = <<<HTML
{$heroSlider}
<div class="values"><div class="wrap"><div class="values-grid">{$valHtml}</div></div></div>
{$welcome}
<section id="categories"><div class="wrap">{$catHead}{$catDir}</div></section>
<section class="featured"><div class="wrap">
  <div class="feat-head reveal"><div><span class="eyebrow">Popular lines</span><h2>Featured products</h2></div>
  <a href="{$u_products}" class="btn btn-ghost">View all products {$ico_arrow}</a></div>
  {$prodGrid}
</div></section>
<section class="how"><div class="wrap">
  <div class="section-head reveal" style="margin-left:auto;margin-right:auto;text-align:center"><span class="eyebrow" style="justify-content:center">How it works</span><h2>Get pricing in three steps</h2><p class="lead">We're not taking online payments just yet — so you send an enquiry and we come straight back to you.</p></div>
  <div class="steps">{$stepHtml}</div>
</div></section>
<section class="about" id="about"><div class="wrap"><div class="about-grid">
  <div class="reveal"><span class="eyebrow">About</span>
    <h2 style="font-size:clamp(30px,3.6vw,46px);font-weight:800;margin-top:14px">Fire safety is all we do.</h2>
    <p class="body">Fire Safe Australia supplies fire protection and essential-services equipment to contractors, builders and facility managers across the country. We keep the range focused, the advice straight, and the gear compliant — so you can equip a site with confidence.</p>
    <div class="stats"><div class="stat"><div class="n">1,000+</div><div class="l">Product lines</div></div><div class="stat"><div class="n">Australia-wide</div><div class="l">Delivery</div></div><div class="stat"><div class="n">Trade</div><div class="l">Accounts welcome</div></div></div>
  </div>
  <div class="about-card reveal">{$ico_flame_big}<h3>Need help speccing a site?</h3><p>Not sure exactly what your building requires? Send us your details and our team will help you put together the right list of compliant equipment.</p><a href="{$u_contact}" class="btn btn-flame">Talk to our team {$ico_arrow}</a></div>
</div></div></section>
<section class="cta-band" id="cta"><div class="wrap">
  <h2>Need to equip a site?</h2>
  <p>Add the products you need to your cart and send us an enquiry — we'll come back with pricing and availability.</p>
  <div class="hero-cta"><a href="{$u_products}" class="btn btn-flame">Browse the catalogue {$ico_arrow}</a><a href="tel:{$phone_raw}" class="btn btn-ghost-dark">{$ico_phone}Call {$phone}</a></div>
</div></section>
<section class="blog" id="blog"><div class="wrap">
  <div class="feat-head reveal"><div><span class="eyebrow">Guides &amp; updates</span><h2 style="font-size:clamp(28px,3.2vw,42px);font-weight:800;margin-top:12px">From the blog</h2></div><a href="{$u_blog}" class="btn btn-ghost">All articles {$ico_arrow}</a></div>
  {$blogGrid}
</div></section>
HTML;

    respond($h);
}

function breadcrumbs(array $items): string
{
    $h = '<nav class="breadcrumbs" aria-label="Breadcrumb">';
    $n = count($items);
    foreach ($items as $i => $it) {
        $last = $i === $n - 1;
        if (!empty($it['href']) && !$last) $h .= '<a href="' . e($it['href']) . '">' . e($it['label']) . '</a>';
        else $h .= '<span' . ($last ? ' class="cur"' : '') . '>' . e($it['label']) . '</span>';
        if (!$last) $h .= '<span class="sep">/</span>';
    }
    return $h . '</nav>';
}

function page_products(): void
{
    $tog = toggles();
    $sortRaw = $_GET['sort'] ?? '';
    $sort = in_array($sortRaw, ['title-asc', 'title-desc'], true) ? $sortRaw : 'newest';
    $q = trim((string)($_GET['q'] ?? ''));
    $catSlug = trim((string)($_GET['category'] ?? ''));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 48;

    $tree = category_filter_tree();
    $activeNode = null;
    foreach ($tree as $n) if ($n['slug'] === $catSlug) { $activeNode = $n; break; }
    $catIds = $activeNode ? subtree_ids($activeNode['id']) : [];

    $res = query_products(['category_ids' => $catIds, 'q' => $q ?: null, 'sort' => $sort, 'page' => $page, 'perPage' => $perPage]);
    $total = $res['total'];
    $totalPages = max(1, (int)ceil($total / $perPage));

    $build = function (array $over) use ($catSlug, $sort, $q) {
        $cat = array_key_exists('category', $over) ? $over['category'] : $catSlug;
        $s = array_key_exists('sort', $over) ? $over['sort'] : $sort;
        $qq = array_key_exists('q', $over) ? $over['q'] : $q;
        $params = [];
        if ($cat) $params['category'] = $cat;
        if ($s && $s !== 'newest') $params['sort'] = $s;
        if ($qq) $params['q'] = $qq;
        if (!empty($over['page']) && $over['page'] > 1) $params['page'] = $over['page'];
        return url('products') . ($params ? '?' . http_build_query($params) : '');
    };

    // sidebar
    $sumTop = 0;
    foreach ($tree as $n) if ($n['depth'] === 0) $sumTop += $n['count'];
    $side = '<aside class="cat-filter"><div class="cat-filter-head">Categories</div><nav class="cat-filter-nav">';
    $side .= '<a href="' . e($build(['category' => ''])) . '" class="cat-filter-link' . (!$catSlug ? ' active' : '') . '"><span>All products</span><span class="cnt">' . $sumTop . '</span></a>';
    foreach ($tree as $n) {
        $side .= '<a href="' . e($build(['category' => $n['slug'], 'page' => 0])) . '" class="cat-filter-link' . ($catSlug === $n['slug'] ? ' active' : '') . '" style="padding-left:' . (12 + $n['depth'] * 16) . 'px">'
            . '<span>' . ($n['depth'] > 0 ? '<span class="cat-filter-tick">└ </span>' : '') . e($n['name']) . '</span><span class="cnt">' . $n['count'] . '</span></a>';
    }
    $side .= '</nav></aside>';

    $heading = $q ? ('Results for “' . e($q) . '”') : ($activeNode ? e($activeNode['name']) : 'All products');
    $from = $total === 0 ? 0 : ($page - 1) * $perPage + 1;
    $to = min($page * $perPage, $total);

    $sorts = ['newest' => 'Newest', 'title-asc' => 'A–Z', 'title-desc' => 'Z–A'];
    $sortBtns = '';
    foreach ($sorts as $k => $lbl) {
        $sortBtns .= '<a href="' . e($build(['sort' => $k, 'page' => 0])) . '" class="' . ($k === $sort ? 'btn btn-flame' : 'btn btn-ghost') . '" style="padding:9px 14px;font-size:13px">' . $lbl . '</a>';
    }

    $grid = '';
    if (!$res['items']) {
        $grid = '<p style="color:var(--muted)">No products match' . ($q ? ' “' . e($q) . '”' : ' this filter') . '.</p>';
    } else {
        $grid = '<div class="prod-grid">';
        foreach ($res['items'] as $p) $grid .= product_card($p, $tog['sellingEnabled']);
        $grid .= '</div>' . pagination_html($page, $totalPages, fn($pg) => $build(['page' => $pg]));
    }

    $crumbs = [['label' => 'Home', 'href' => url('')], ['label' => 'Products', 'href' => url('products')]];
    if ($activeNode) $crumbs[] = ['label' => $activeNode['name']];

    $clear = $q ? ' · <a href="' . e($build(['q' => ''])) . '" style="color:var(--flame-deep)">Clear search</a>' : '';
    $catHidden = $catSlug ? '<input type="hidden" name="category" value="' . e($catSlug) . '">' : '';
    $sortHidden = $sort !== 'newest' ? '<input type="hidden" name="sort" value="' . e($sort) . '">' : '';

    $h = '<section><div class="wrap">' . breadcrumbs($crumbs)
        . '<div class="products-layout" style="margin-top:18px">' . $side
        . '<div class="products-main">'
        . '<form action="' . url('products') . '" method="get" class="product-search">' . $catHidden . $sortHidden . icon('search')
        . '<input type="search" name="q" value="' . e($q) . '" placeholder="Search products by name or SKU…" aria-label="Search products">'
        . '<button type="submit" class="btn btn-flame" style="padding:9px 18px">Search</button></form>'
        . '<div class="feat-head reveal" style="margin-bottom:28px"><div><span class="eyebrow">The catalogue</span>'
        . '<h1 style="font-size:clamp(24px,2.8vw,38px);font-weight:800;margin-top:10px">' . $heading . '</h1>'
        . '<p style="color:var(--muted);font-size:14px;margin-top:6px">' . ($total === 0 ? 'No products' : "Showing $from–$to of $total") . $clear . '</p></div>'
        . '<div style="display:flex;gap:8px;align-items:center">' . $sortBtns . '</div></div>'
        . $grid . '</div></div></div></section>';
    respond($h, ['title' => $heading . ' · ' . SITE_NAME]);
}

function page_product(string $slug): void
{
    $tog = toggles();
    $p = product_by_slug($slug);
    if (!$p) { http_response_code(404); page_not_found(); return; }
    $selling = $tog['sellingEnabled'];
    $hasVar = !empty($p['variants']);

    // Build gallery: product images + any variant images (deduped).
    $gallery = json_arr($p['images'] ?? null);
    if ($hasVar) {
        foreach ($p['variants'] as $v) {
            $vi = trim((string)($v['image'] ?? ''));
            if ($vi !== '' && !in_array($vi, $gallery, true)) $gallery[] = $vi;
        }
    }
    $img = $gallery[0] ?? null;

    $crumbs = [['label' => 'Home', 'href' => url('')], ['label' => 'Products', 'href' => url('products')]];
    if ($p['category_name']) $crumbs[] = ['label' => $p['category_name'], 'href' => url('category/' . $p['category_slug'])];
    $crumbs[] = ['label' => $p['title']];

    if ($gallery) {
        $thumbs = '';
        if (count($gallery) > 1) {
            foreach ($gallery as $i => $g) {
                $thumbs .= '<button type="button" class="prod-thumb' . ($i === 0 ? ' active' : '') . '" data-img="' . e($g) . '"><img src="' . e($g) . '" alt=""></button>';
            }
            $thumbs = '<div class="prod-thumbs">' . $thumbs . '</div>';
        }
        $media = '<div class="prod-gallery"><div class="prod-main"><img id="prod-main-img" src="' . e($gallery[0]) . '" alt="' . e($p['title']) . '"></div>' . $thumbs . '</div>';
    } else {
        $media = icon(product_icon($p['category_slug']), 'icon', 'width:96px;height:96px') . '<span class="ph">PHOTO</span>';
    }
    $mediaClass = $gallery ? 'prod-media has-gallery' : 'prod-media';

    if ($hasVar) {
        $skuLine = count($p['variants']) . ' sizes available';
        $buy = '<p style="color:var(--muted);margin-bottom:16px">Choose a size and add it to your ' . ($selling ? 'cart' : 'enquiry') . '.</p>' . variant_table($p, $selling);
    } else {
        $skuLine = $p['sku'] ?: '';
        $buy = '<div style="margin-bottom:20px;font-size:18px">' . price_or_enquire($p['price'], $selling) . '</div>'
            . '<div style="margin-bottom:28px">' . cart_control([
                'productId' => $p['sku'] ?: ('p' . $p['id']), 'sku' => $p['sku'] ?: ('p' . $p['id']),
                'title' => $p['title'], 'slug' => $p['slug'], 'image' => $img,
                'price' => $selling ? ($p['price'] !== null ? (float)$p['price'] : null) : null,
            ], 'btn btn-flame', $selling ? 'Add to cart' : 'Add to enquiry') . '</div>';
    }

    $h = '<section class="product-detail"><div class="wrap">' . breadcrumbs($crumbs)
        . '<div class="product-detail-grid" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:48px;margin-top:24px;align-items:start">'
        . '<div class="' . $mediaClass . '" style="border-radius:var(--radius);border:1px solid var(--border)">' . $media . '</div>'
        . '<div>' . ($skuLine ? '<span class="prod-sku" style="font-size:13px">' . e($skuLine) . '</span>' : '')
        . '<h1 style="font-size:clamp(28px,3.4vw,40px);font-weight:800;margin:8px 0 16px">' . e($p['title']) . '</h1>'
        . $buy
        . '<div class="prose-content" style="margin-top:28px">' . ($p['description'] ?: '') . '</div>'
        . '</div></div></div></section>'
        . '<script>(function(){var m=document.getElementById("prod-main-img");if(!m)return;'
        . 'function set(s){if(!s)return;m.src=s;var ts=document.querySelectorAll(".prod-thumb");for(var i=0;i<ts.length;i++)ts[i].classList.toggle("active",ts[i].getAttribute("data-img")===s);}'
        . 'var tb=document.querySelectorAll(".prod-thumb");for(var i=0;i<tb.length;i++)(function(t){t.addEventListener("click",function(){set(t.getAttribute("data-img"));});})(tb[i]);'
        . 'var vr=document.querySelectorAll(".variant-row[data-img]");for(var j=0;j<vr.length;j++)(function(r){r.addEventListener("click",function(e){if(e.target.closest(".variant-action"))return;set(r.getAttribute("data-img"));});})(vr[j]);'
        . '})();</script>';
    $desc = trim(strip_tags($p['description'] ?? '')) ?: ($p['title'] . ' — ' . SITE_NAME);
    respond($h, ['title' => $p['title'] . ' · ' . SITE_NAME, 'desc' => mb_substr($desc, 0, 155)]);
}

function page_categories(): void
{
    $cats = top_categories();
    $grid = '<div class="cat-grid">';
    foreach ($cats as $c) $grid .= category_card($c);
    $grid .= '</div>';
    $h = '<section><div class="wrap">' . breadcrumbs([['label' => 'Home', 'href' => url('')], ['label' => 'Categories']])
        . '<div style="margin-top:18px">' . section_head('The catalogue', 'Shop by category', 'Everything you need to equip and maintain a fire-safe site.') . '</div>'
        . $grid . '</div></section>';
    respond($h, ['title' => 'Categories · ' . SITE_NAME]);
}

function page_category(array $slugs): void
{
    $tog = toggles();
    $r = resolve_category_path($slugs);
    if (!$r) { http_response_code(404); page_not_found(); return; }
    $res = query_products(['category_ids' => $r['descendantIds'], 'sort' => 'newest', 'page' => 1, 'perPage' => 96]);

    $crumbs = [['label' => 'Home', 'href' => url('')]];
    $acc = [];
    foreach ($r['chain'] as $i => $c) {
        $acc[] = $c['slug'];
        $isLast = $i === count($r['chain']) - 1;
        $crumbs[] = ['label' => $c['name'], 'href' => $isLast ? null : url('category/' . implode('/', $acc))];
    }

    $sub = '';
    if ($r['children']) {
        $sub = '<div style="margin-bottom:48px"><div class="cat-grid">';
        foreach ($r['children'] as $c) $sub .= category_card($c, url('category/' . implode('/', array_merge($slugs, [$c['slug']]))));
        $sub .= '</div></div>';
    }
    $grid = '';
    if ($res['items']) {
        $grid = '<div class="prod-grid">';
        foreach ($res['items'] as $p) $grid .= product_card($p, $tog['sellingEnabled']);
        $grid .= '</div>';
    } elseif (!$r['children']) {
        $grid = '<p style="color:var(--muted)">No products in this category yet.</p>';
    }

    $node = $r['node'];
    $h = '<section><div class="wrap">' . breadcrumbs($crumbs)
        . '<div style="margin-top:18px">' . section_head('The catalogue', $node['name'], $node['description'] ?? '') . '</div>'
        . $sub . $grid . '</div></section>';
    respond($h, ['title' => $node['name'] . ' · ' . SITE_NAME, 'desc' => $node['description'] ?: ('Browse ' . $node['name'] . ' at ' . SITE_NAME)]);
}

function page_blog(): void
{
    $posts = all_posts();
    $grid = $posts ? '<div class="post-grid">' : '<p style="color:var(--muted)">No articles published yet.</p>';
    foreach ($posts as $p) $grid .= blog_card($p);
    if ($posts) $grid .= '</div>';
    $h = '<section class="blog"><div class="wrap">' . breadcrumbs([['label' => 'Home', 'href' => url('')], ['label' => 'Blog']])
        . '<div style="margin-top:18px">' . section_head('Guides & updates', 'From the blog', 'Plain-English guidance on equipping and maintaining a fire-safe site.') . '</div>'
        . $grid . '</div></section>';
    respond($h, ['title' => 'Blog · ' . SITE_NAME]);
}

function page_post(string $slug): void
{
    $post = post_by_slug($slug);
    if (!$post) { http_response_code(404); page_not_found(); return; }
    $meta = implode(' · ', array_filter([$post['author'], fmt_date($post['published_at'])]));
    $h = '<section><div class="wrap"><article style="max-width:760px;margin:0 auto">'
        . breadcrumbs([['label' => 'Home', 'href' => url('')], ['label' => 'Blog', 'href' => url('blog')], ['label' => $post['title']]])
        . '<div style="margin-top:24px">' . ($post['category'] ? '<span class="eyebrow">' . e($post['category']) . '</span>' : '')
        . '<h1 style="font-size:clamp(30px,4vw,48px);font-weight:800;margin:10px 0 12px">' . e($post['title']) . '</h1>'
        . '<p class="post-date" style="font-size:13px">' . e($meta) . '</p></div>'
        . '<div class="prose-content" style="margin-top:28px">' . ($post['content'] ?: '') . '</div>'
        . '</article></div></section>';
    respond($h, ['title' => $post['title'] . ' · ' . SITE_NAME, 'desc' => $post['excerpt'] ?: '']);
}

function page_about(): void
{
    $certs = '<div class="cert-block reveal">'
        . '<span class="eyebrow" style="justify-content:center">Certified &amp; compliant</span>'
        . '<p style="color:var(--muted);max-width:560px;margin:10px auto 18px">All products are designed to meet relevant Australian and international standards. Data sheets and certification documents available on request.</p>'
        . '<div class="cert-stds">'
        . '<span>AS</span><span>UL</span><span>FM</span><span>DIN</span><span>ULC</span><span>NEN 3374</span><span>WaterMark</span>'
        . '</div>'
        . '<img src="' . url('assets/certified.png') . '" alt="Australian Standard Certified Product · FPA Australia" class="cert-img">'
        . '</div>';

    $h = '<section><div class="wrap">' . breadcrumbs([['label' => 'Home', 'href' => url('')], ['label' => 'About']])
        . '<div class="about-grid" style="margin-top:24px"><div class="reveal"><span class="eyebrow">About</span>'
        . '<h1 style="font-size:clamp(32px,4vw,52px);font-weight:800;margin-top:14px">Australia\'s trusted fire equipment supplier.</h1>'
        . '<p class="body">Fire Safe Australia is a proudly Australian owned business and a trusted independent supplier and wholesaler of fire equipment. With many years\' experience in the fire industry, our commitment to quality and customer satisfaction means you can trust us to deliver fire safety products to suit your business requirements.</p>'
        . '<div class="stats"><div class="stat"><div class="n">1,000+</div><div class="l">Product lines</div></div><div class="stat"><div class="n">Australia-wide</div><div class="l">Delivery</div></div><div class="stat"><div class="n">Trade</div><div class="l">Accounts welcome</div></div></div></div>'
        . '<div class="about-card reveal">' . icon('flame', 'ac-flame') . '<h3>Need help speccing a site?</h3><p>Send us your details and our team will help you put together the right list of compliant equipment.</p><a href="' . url('contact') . '" class="btn btn-flame">Talk to our team ' . icon('arrow-right') . '</a></div>'
        . '</div>'
        . '<div class="prose-content reveal" style="max-width:900px;margin:48px auto 0">'
        . '<h2 style="font-size:28px;margin-bottom:6px">Why Fire Safe Australia is the first choice</h2>'
        . '<h3>Largest range of products</h3>'
        . '<p>We take great pleasure in supplying the fire industry with a large range of Australian Standards approved products to help protect people and property every day. Fire Safe Australia is a one-stop shop — our aim is to make sure you can get everything you need from us.</p>'
        . '<p><strong>Fire Safe Australia specialises in supplying:</strong></p>'
        . '<ul><li>Portable fire equipment</li><li>Conventional &amp; addressable fire alarm detection products</li><li>AS 1851 maintenance &amp; testing equipment</li><li>Energy-efficient emergency &amp; exit lighting</li><li>Standard and customised signage</li></ul>'
        . '<p>We also distribute a wide range of ancillary products, including:</p>'
        . '<ul><li>Extinguisher servicing equipment</li><li>Extinguisher brackets and cabinets</li><li>Locks and straps</li><li>Hydrant valve caps, parts &amp; accessories</li><li>A full range of detection parts and equipment</li><li>Specialist detection systems</li><li>Testing and calibration equipment</li><li>A complete range of bulk AFFF, powder &amp; wet chemical to suit your needs</li></ul>'
        . '<h3>National distribution</h3>'
        . '<p>Fire Safe Australia delivers right across Australia through a strong national distribution network — hassle-free access to our products and services, fast turnaround and delivery, and the confidence we can deliver. We pride ourselves on quality products, fast turnaround and excellent customer service.</p>'
        . '<h3>Product development</h3>'
        . '<p>Over the years we have built strong expertise in the design and supply of fire safety products and equipment. We continue to add the latest products to our range, backed by comprehensive product knowledge and testing. We are continually testing and improving our products so they are among the best in the industry.</p>'
        . '<h3>Local knowledge &amp; experience</h3>'
        . '<p>We understand the different needs of each state. The staff, products and solutions available from Fire Safe Australia are tailored to meet your local requirements. Our team strives for professionalism — continually expanding their knowledge of our product range to offer better service, and always happy to give you a helpful voice on the other end of the phone.</p>'
        . '</div>' . $certs . '</div></section>';
    respond($h, ['title' => 'About Us · ' . SITE_NAME]);
}

function page_contact(): void
{
    $ok = isset($_GET['sent']);
    $banner = $ok ? '<div class="admin-card" style="margin-bottom:20px;border-color:#bfe6cd;background:#f0faf3">' . icon('check') . ' Thanks — we\'ll be in touch shortly.</div>' : '';
    $err = isset($_GET['err']) ? '<p class="form-error">Please check the form and try again.</p>' : '';
    $is = 'width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:10px;font-size:15px;background:#fff';
    $ls = 'display:block;font-family:var(--f-mono);font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:6px';
    $h = '<section><div class="wrap">' . breadcrumbs([['label' => 'Home', 'href' => url('')], ['label' => 'Contact']])
        . '<div class="about-grid" style="grid-template-columns:.9fr 1.1fr;gap:56px;margin-top:24px">'
        . '<div class="reveal"><span class="eyebrow">Contact</span><h1 style="font-size:clamp(30px,3.6vw,46px);font-weight:800;margin-top:14px">Let\'s talk fire safety.</h1>'
        . '<p class="body">Send us a message and we\'ll come back with pricing, stock and advice — usually within a business day.</p>'
        . '<div class="foot-contact" style="margin-top:28px;color:var(--text)">'
        . '<p style="display:flex;gap:10px;margin-bottom:12px">' . icon('pin', 'icon', 'color:var(--flame-deep)') . e(CO_ADDRESS) . '</p>'
        . '<p style="display:flex;gap:10px;margin-bottom:12px">' . icon('phone', 'icon', 'color:var(--flame-deep)') . '<a href="tel:' . e(CO_PHONE_RAW) . '">' . e(CO_PHONE) . '</a></p>'
        . '<p style="display:flex;gap:10px">' . icon('mail', 'icon', 'color:var(--flame-deep)') . '<a href="mailto:' . e(CO_EMAIL) . '">' . e(CO_EMAIL) . '</a></p></div></div>'
        . '<div class="reveal">' . $banner . $err
        . '<form method="post" action="' . url('contact') . '" style="display:grid;gap:16px">' . csrf_field()
        . '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px"><div><label style="' . $ls . '">Name</label><input name="name" required style="' . $is . '"></div><div><label style="' . $ls . '">Phone</label><input name="phone" style="' . $is . '"></div></div>'
        . '<div><label style="' . $ls . '">Email</label><input name="email" type="email" required style="' . $is . '"></div>'
        . '<div><label style="' . $ls . '">Message</label><textarea name="message" required rows="5" style="' . $is . '"></textarea></div>'
        . '<button class="btn btn-flame">Send message ' . icon('arrow-right') . '</button></form></div>'
        . '</div></div></section>';
    respond($h, ['title' => 'Contact Us · ' . SITE_NAME]);
}

function page_cart(): void
{
    $tog = toggles();
    $h = '<section><div class="wrap"><div class="section-head" style="max-width:760px"><span class="eyebrow">' . ($tog['sellingEnabled'] ? 'Your cart' : 'Your enquiry') . '</span><h2>' . ($tog['sellingEnabled'] ? 'Shopping cart' : 'Enquiry list') . '</h2></div>'
        . '<div style="max-width:760px" id="cart-page"></div></div></section>';
    respond($h, ['title' => 'Your Cart · ' . SITE_NAME]);
}

function page_checkout(): void
{
    $tog = toggles();
    $is = 'width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:10px;font-size:15px;background:#fff';
    $ls = 'display:block;font-family:var(--f-mono);font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:6px';
    $pay = payments_enabled();

    if (isset($_GET['sent']) || isset($_GET['paid'])) {
        $paid = isset($_GET['paid']);
        $h = '<section><div class="wrap"><div style="max-width:560px;margin:0 auto;text-align:center;padding:20px 0">' . icon('check', 'icon', 'width:40px;height:40px;color:var(--flame-deep);margin:0 auto 16px')
            . '<h1 style="font-size:clamp(28px,3.4vw,40px);font-weight:800;margin-bottom:12px">' . ($paid ? 'Payment received — thank you' : 'Enquiry sent — thank you') . '</h1>'
            . '<p style="color:var(--muted);margin-bottom:24px">' . ($paid
                ? 'Your order is confirmed' . (ctype_digit((string)$_GET['paid']) ? ' (#' . (int)$_GET['paid'] . ')' : '') . '. A receipt has been emailed to you.'
                : 'We\'ve received your enquiry and will reply with pricing and availability, usually within a business day.') . '</p>'
            . '<a href="' . url('products') . '" class="btn btn-flame">Continue browsing ' . icon('arrow-right') . '</a></div>'
            . '<script>if(window.FSACart)FSACart.clear();</script></div></section>';
        respond($h, ['title' => ($paid ? 'Order confirmed' : 'Enquiry sent') . ' · ' . SITE_NAME]);
        return;
    }

    $errMap = ['unpriced' => 'Those items aren\'t available for online purchase yet — send an enquiry instead.', 'stripe' => 'Could not start payment. Please try again.', '1' => 'Please check your details.'];
    $err = isset($_GET['err']) ? '<p class="form-error">' . e($errMap[$_GET['err']] ?? 'Something went wrong.') . '</p>' : '';

    $action = $pay ? url('stripe/checkout') : url('enquiry');
    $eyebrow = $pay ? 'Checkout' : 'Enquiry';
    $title = $pay ? 'Checkout & pay' : 'Send your enquiry';
    $btn = $pay ? ('Pay now ' . icon('arrow-right')) : ('Send enquiry ' . icon('arrow-right'));
    $notes = $pay ? '' : '<div><label style="' . $ls . '">Notes (optional)</label><textarea name="message" rows="4" style="' . $is . '"></textarea></div>';

    $h = '<section><div class="wrap"><div class="section-head" style="max-width:760px"><span class="eyebrow">' . $eyebrow . '</span><h2>' . $title . '</h2></div>'
        . '<div style="max-width:760px;display:grid;gap:28px">' . $err
        . '<div class="admin-card"><h3 style="font-size:16px;margin-bottom:14px">Your list</h3><div id="checkout-items"></div></div>'
        . '<form id="checkout-form" method="post" action="' . $action . '" style="display:grid;gap:16px">' . csrf_field()
        . '<input type="hidden" name="items" class="js-cart-json" value="[]">'
        . '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px"><div><label style="' . $ls . '">Name</label><input name="name" required style="' . $is . '"></div><div><label style="' . $ls . '">Phone</label><input name="phone" style="' . $is . '"></div></div>'
        . '<div><label style="' . $ls . '">Email</label><input name="email" type="email" required style="' . $is . '"></div>'
        . $notes
        . '<button class="btn btn-flame">' . $btn . '</button></form>'
        . '</div></div></section>';
    respond($h, ['title' => 'Checkout · ' . SITE_NAME]);
}

function page_not_found(): void
{
    $h = '<section style="min-height:50vh;display:grid;place-items:center"><div style="text-align:center;padding:0 24px">'
        . '<p style="font-family:var(--f-mono);color:var(--flame-deep);letter-spacing:.18em;text-transform:uppercase;font-size:13px">404</p>'
        . '<h1 style="font-size:clamp(30px,4vw,52px);font-weight:800;margin:12px 0 14px">Page not found</h1>'
        . '<p style="color:var(--muted);margin-bottom:24px">The page you\'re looking for doesn\'t exist or has moved.</p>'
        . '<a href="' . url('') . '" class="btn btn-flame">Back to home</a></div></section>';
    respond($h, ['title' => 'Not found · ' . SITE_NAME]);
}

function page_sitemap(): void
{
    header('Content-Type: application/xml; charset=utf-8');
    $base = rtrim(SITE_URL ?: '', '/');
    $urls = ['', 'products', 'categories', 'blog', 'about', 'contact'];
    foreach (q_all("SELECT slug FROM products WHERE status='published'") as $r) $urls[] = 'products/' . $r['slug'];
    foreach (q_all('SELECT slug FROM categories') as $r) $urls[] = 'category/' . $r['slug'];
    foreach (q_all("SELECT slug FROM blog_posts WHERE status='published'") as $r) $urls[] = 'blog/' . $r['slug'];
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) echo '<url><loc>' . e($base . url($u)) . "</loc></url>\n";
    echo '</urlset>';
}
