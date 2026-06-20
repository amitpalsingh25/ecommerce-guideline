<?php
declare(strict_types=1);

const ADM_INPUT = 'width:100%;padding:11px 13px;border:1px solid var(--border);border-radius:10px;font-size:15px;background:#fff';
const ADM_LABEL = 'display:block;font-family:var(--f-mono);font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:6px';

function admin_dispatch(array $seg): void
{
    $a = $seg[0] ?? '';

    if ($a === 'login') { admin_login(); return; }
    if ($a === 'logout') { logout(); redirect('admin/login'); }

    require_admin();

    switch ($a) {
        case '':          admin_dashboard(); break;
        case 'upload':    admin_upload(); break;
        case 'hero':      admin_hero(); break;
        case 'products':  admin_products($seg); break;
        case 'categories':admin_categories($seg); break;
        case 'blog':      admin_blog($seg); break;
        case 'enquiries': admin_enquiries($seg); break;
        case 'orders':    admin_orders(); break;
        case 'customers': admin_customers($seg); break;
        case 'emails':    admin_emails($seg); break;
        case 'media':     admin_media($seg); break;
        case 'settings':  admin_settings(); break;
        case 'logs':      admin_logs(); break;
        default:          http_response_code(404); respond_admin('<h1>Not found</h1>');
    }
}

function admin_login(): void
{
    if (is_admin()) redirect('admin');
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (csrf_check() && attempt_login((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''))) {
            $to = $_SESSION['after_login'] ?? '/admin';
            unset($_SESSION['after_login']);
            redirect(ltrim($to, '/'));
        }
        $err = '<p class="form-error">Invalid email or password.</p>';
    }
    $brand = branding();
    $logo = !empty($brand['logoUrl'])
        ? '<img src="' . e($brand['logoUrl']) . '" alt="' . e(SITE_NAME) . '" style="height:54px;width:auto;margin:0 auto 6px;display:block">'
        : '<div class="admin-brand" style="justify-content:center;color:var(--charcoal)">' . icon('flame', 'wm-flame') . ' FireSafe</div>';

    $style = '<style>'
        . '.login-wrap{min-height:100vh;display:grid;place-items:center;background:var(--ink);position:relative;overflow:hidden;padding:24px}'
        . '.login-glow{position:absolute;top:-160px;right:-140px;width:560px;height:560px;border-radius:50%;background:radial-gradient(circle,rgba(251,191,36,.85),rgba(249,115,22,.5) 35%,rgba(234,88,12,.15) 58%,transparent 72%);filter:blur(16px);animation:fsaPulse 8s ease-in-out infinite}'
        . '.login-glow.two{top:auto;bottom:-180px;left:-160px;right:auto;width:480px;height:480px;animation-delay:2s}'
        . '.login-card{position:relative;z-index:2;width:400px;max-width:94vw;background:#fff;border-radius:18px;padding:38px 34px;box-shadow:0 40px 80px -30px rgba(0,0,0,.6)}'
        . '.login-card h1{font-size:24px;text-align:center;margin:8px 0 2px}'
        . '.login-sub{text-align:center;color:var(--muted);font-size:14px;margin:0 0 22px}'
        . '.login-back{display:block;text-align:center;margin-top:18px;color:rgba(255,255,255,.7);font-size:13px;position:relative;z-index:2}'
        . '.login-back:hover{color:#fff}'
        . '@keyframes fsaPulse{0%,100%{opacity:.8;transform:scale(1)}50%{opacity:1;transform:scale(1.06)}}'
        . '</style>';

    $body = '<div class="login-wrap"><div class="login-glow"></div><div class="login-glow two"></div>'
        . '<div><div class="login-card">' . $logo
        . '<h1>Admin sign in</h1><p class="login-sub">Manage products, enquiries &amp; settings</p>' . $err
        . '<form method="post" style="display:grid;gap:16px">' . csrf_field()
        . '<div><label style="' . ADM_LABEL . '">Email</label><input name="email" type="email" required autofocus style="' . ADM_INPUT . '"></div>'
        . '<div><label style="' . ADM_LABEL . '">Password</label><input name="password" type="password" required style="' . ADM_INPUT . '"></div>'
        . '<button class="btn btn-flame" style="justify-content:center;width:100%;padding:14px">Sign in ' . icon('arrow-right') . '</button></form></div>'
        . '<a class="login-back" href="' . url('') . '">← Back to ' . e(SITE_NAME) . '</a></div></div>';

    echo '<!doctype html><html lang="en-AU"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>Sign in · Admin · ' . e(SITE_NAME) . '</title><link rel="stylesheet" href="' . url('assets/css/style.css') . '">' . theme_style() . $style . '</head><body>'
        . $body . '</body></html>';
}

function admin_dashboard(): void
{
    $c = [
        'Products' => q_val('SELECT COUNT(*) FROM products'),
        'Variants' => q_val('SELECT COUNT(*) FROM product_variants'),
        'Categories' => q_val('SELECT COUNT(*) FROM categories'),
        'Blog posts' => q_val('SELECT COUNT(*) FROM blog_posts'),
        'Enquiries' => q_val('SELECT COUNT(*) FROM enquiries'),
        'New enquiries' => q_val("SELECT COUNT(*) FROM enquiries WHERE status='new'"),
    ];
    $hrefs = ['Products' => 'admin/products', 'Variants' => 'admin/products', 'Categories' => 'admin/categories', 'Blog posts' => 'admin/blog', 'Enquiries' => 'admin/enquiries', 'New enquiries' => 'admin/enquiries'];
    $cards = '';
    foreach ($c as $label => $val) {
        $cards .= '<a href="' . url($hrefs[$label]) . '" class="stat-card"><div class="num">' . (int)$val . '</div><div class="lbl">' . e($label) . '</div></a>';
    }
    $h = '<div class="admin-topbar"><h1>Dashboard</h1><a href="' . url('admin/products/new') . '" class="btn btn-flame">New product ' . icon('plus') . '</a></div>'
        . '<div class="stat-grid">' . $cards . '</div>'
        . '<div class="admin-card"><h3 style="margin-bottom:10px">Quick actions</h3><div style="display:flex;gap:10px;flex-wrap:wrap">'
        . '<a href="' . url('admin/products/new') . '" class="btn btn-ghost">Add product</a><a href="' . url('admin/categories') . '" class="btn btn-ghost">Categories</a>'
        . '<a href="' . url('admin/blog/new') . '" class="btn btn-ghost">Write post</a><a href="' . url('admin/settings') . '" class="btn btn-ghost">Settings</a></div></div>';
    respond_admin($h, ['title' => 'Dashboard · Admin']);
}

// ---------- AJAX image upload ----------
function admin_upload(): void
{
    header('Content-Type: application/json');
    if (empty($_FILES['file'])) { echo json_encode(['error' => 'No file']); return; }
    $url = save_upload($_FILES['file']);
    echo json_encode($url ? ['url' => $url] : ['error' => 'Upload failed (type/size)']);
}

// ---------- Media library ----------
/** List image files in the uploads dir, newest first. */
function media_files(): array
{
    if (!is_dir(UPLOAD_DIR)) return [];
    $out = [];
    foreach (scandir(UPLOAD_DIR) as $f) {
        if ($f === '.' || $f === '..') continue;
        if (!preg_match('/\.(jpe?g|png|webp|gif|avif)$/i', $f)) continue;
        $path = UPLOAD_DIR . '/' . $f;
        if (!is_file($path)) continue;
        $out[] = ['name' => $f, 'url' => UPLOAD_URL . '/' . $f, 'mtime' => @filemtime($path) ?: 0, 'size' => @filesize($path) ?: 0];
    }
    usort($out, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
    return $out;
}

/** Where a media URL is used (product main images + variant images). */
function media_usage(string $url): array
{
    $out = []; $seen = [];
    try {
        foreach (q_all('SELECT id, title FROM products WHERE images LIKE ?', ['%' . $url . '%']) as $r) {
            if (!isset($seen[$r['id']])) { $seen[$r['id']] = 1; $out[] = ['title' => $r['title'], 'url' => url('admin/products/' . $r['id'])]; }
        }
        foreach (q_all('SELECT DISTINCT p.id, p.title FROM product_variants v JOIN products p ON p.id = v.product_id WHERE v.image = ?', [$url]) as $r) {
            if (!isset($seen[$r['id']])) { $seen[$r['id']] = 1; $out[] = ['title' => $r['title'] . ' (variant)', 'url' => url('admin/products/' . $r['id'])]; }
        }
    } catch (Throwable $e) { /* variant col may not exist yet */ }
    return $out;
}

function media_info(string $name): array
{
    $path = UPLOAD_DIR . '/' . $name;
    if ($name === '' || !is_file($path)) return ['error' => 'not found'];
    $url = UPLOAD_URL . '/' . $name;
    $dim = @getimagesize($path);
    $meta = (array)setting('media_meta', []);
    $m = (array)($meta[$name] ?? []);
    return [
        'name' => $name, 'url' => $url,
        'w' => $dim ? (int)$dim[0] : 0, 'h' => $dim ? (int)$dim[1] : 0,
        'type' => ($dim && isset($dim['mime'])) ? $dim['mime'] : '',
        'size' => @filesize($path) ?: 0, 'mtime' => @filemtime($path) ?: 0,
        'title' => (string)($m['title'] ?? ''), 'alt' => (string)($m['alt'] ?? ''),
        'used' => media_usage($url),
        'gd' => function_exists('imagecreatetruecolor'),
    ];
}

function gd_load(string $path, string $ext)
{
    switch ($ext) {
        case 'jpg': case 'jpeg': return @imagecreatefromjpeg($path);
        case 'png':  return @imagecreatefrompng($path);
        case 'webp': return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null;
        case 'gif':  return @imagecreatefromgif($path);
    }
    return null;
}
function gd_save($im, string $path, string $ext): bool
{
    switch ($ext) {
        case 'jpg': case 'jpeg': return imagejpeg($im, $path, 88);
        case 'png':  return imagepng($im, $path);
        case 'webp': return function_exists('imagewebp') ? imagewebp($im, $path, 88) : false;
        case 'gif':  return imagegif($im, $path);
    }
    return false;
}

function media_edit(string $name, string $op, array $in): array
{
    $path = UPLOAD_DIR . '/' . $name;
    if ($name === '' || !is_file($path)) return ['error' => 'not found'];
    if (!function_exists('imagecreatetruecolor')) return ['error' => 'Image editing not available (server has no GD).'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $im = gd_load($path, $ext);
    if (!$im) return ['error' => 'Cannot read this image type for editing.'];

    $res = $im;
    if ($op === 'rotate') {
        $dir = (($in['dir'] ?? 'cw') === 'ccw') ? 90 : -90; // imagerotate is CCW-positive
        $bg = imagecolorallocatealpha($im, 0, 0, 0, 127);
        $res = imagerotate($im, $dir, $bg);
    } elseif ($op === 'flip') {
        imageflip($im, (($in['mode'] ?? 'h') === 'v') ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
        $res = $im;
    } elseif ($op === 'scale') {
        $w = (int)($in['w'] ?? 0);
        if ($w < 16) return ['error' => 'Width too small.'];
        $res = imagescale($im, $w);
    } elseif ($op === 'crop') {
        $x = max(0, (int)($in['x'] ?? 0)); $y = max(0, (int)($in['y'] ?? 0));
        $cw = (int)($in['cw'] ?? 0); $ch = (int)($in['ch'] ?? 0);
        if ($cw < 8 || $ch < 8) return ['error' => 'Crop area too small.'];
        $res = imagecrop($im, ['x' => $x, 'y' => $y, 'width' => $cw, 'height' => $ch]);
        if (!$res) return ['error' => 'Crop failed.'];
    } else {
        return ['error' => 'Unknown operation.'];
    }
    if (in_array($ext, ['png', 'webp'], true)) { imagealphablending($res, false); imagesavealpha($res, true); }
    $ok = gd_save($res, $path, $ext);
    if ($im !== $res) @imagedestroy($im);
    @imagedestroy($res);
    if (!$ok) return ['error' => 'Could not save edited image.'];
    clearstatcache(true, $path);
    $dim = @getimagesize($path);
    log_event('system', "Media edited: $name ($op)", 'info');
    return ['ok' => 1, 'w' => $dim ? (int)$dim[0] : 0, 'h' => $dim ? (int)$dim[1] : 0, 'size' => @filesize($path) ?: 0, 'url' => UPLOAD_URL . '/' . $name];
}

function admin_media(array $seg): void
{
    $action = $seg[1] ?? '';

    if ($action === 'list') {
        header('Content-Type: application/json');
        echo json_encode(['files' => array_map(fn($f) => ['name' => $f['name'], 'url' => $f['url']], media_files())]);
        return;
    }
    if ($action === 'info') {
        header('Content-Type: application/json');
        echo json_encode(media_info(basename((string)($_GET['name'] ?? ''))));
        return;
    }
    if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        if (!csrf_check()) { echo json_encode(['error' => 'bad token']); return; }
        $name = basename((string)($_POST['name'] ?? ''));
        if ($name === '' || !is_file(UPLOAD_DIR . '/' . $name)) { echo json_encode(['error' => 'not found']); return; }
        $meta = (array)setting('media_meta', []);
        $meta[$name] = ['title' => trim((string)($_POST['title'] ?? '')), 'alt' => trim((string)($_POST['alt'] ?? ''))];
        set_setting('media_meta', $meta);
        echo json_encode(['ok' => 1]);
        return;
    }
    if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        if (!csrf_check()) { echo json_encode(['error' => 'bad token']); return; }
        echo json_encode(media_edit(basename((string)($_POST['name'] ?? '')), (string)($_POST['op'] ?? ''), $_POST));
        return;
    }
    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        if (!csrf_check()) { echo json_encode(['error' => 'bad token']); return; }
        $name = basename((string)($_POST['name'] ?? ''));
        $path = UPLOAD_DIR . '/' . $name;
        $ok = $name !== '' && is_file($path) && @unlink($path);
        echo json_encode($ok ? ['ok' => 1] : ['error' => 'not deleted']);
        return;
    }

    $files = media_files();
    $tiles = '';
    foreach ($files as $f) {
        $tiles .= '<div class="media-tile" data-name="' . e($f['name']) . '" data-url="' . e($f['url']) . '">'
            . '<div class="media-img" style="background-image:url(\'' . e($f['url']) . '\')"></div>'
            . '<div class="media-meta"><span title="' . e($f['name']) . '">' . e($f['name']) . '</span>'
            . '<button type="button" class="media-del" title="Delete">' . icon('trash') . '</button></div></div>';
    }
    $grid = $files ? '<div class="media-grid" id="media-grid">' . $tiles . '</div>' : '<div class="admin-card">No images yet. Upload your first below.</div>';
    $h = '<div class="admin-topbar"><h1>Media library</h1>'
        . '<label class="btn btn-flame" style="cursor:pointer">Upload images ' . icon('plus')
        . '<input type="file" accept="image/*" multiple id="media-upload" style="display:none"></label></div>'
        . '<p style="color:var(--muted);margin-top:-10px;margin-bottom:18px">All uploaded images. Pick from here when setting a product or variant photo. Click an image to view it. Uploads are auto-resized to 1000px wide and compressed (WebP) — smaller files, same visible quality.</p>'
        . '<div id="media-status" style="margin-bottom:12px;color:var(--muted);font-size:13px"></div>'
        . $grid;
    respond_admin($h, ['title' => 'Media library · Admin']);
}

/** Ensure product_variants.image column exists (lazy migration). */
function ensure_variant_image_col(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $col = q_one("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'image'");
        if (!$col) db()->exec("ALTER TABLE product_variants ADD COLUMN image VARCHAR(500) NULL");
    } catch (Throwable $e) {
        log_event('system', 'variant image column check failed', 'warn', ['error' => $e->getMessage()]);
    }
}

/** Reusable image picker field (works with media.js). */
function img_field(string $name, string $url = '', bool $compact = false): string
{
    $bg = $url ? "background-image:url('" . e($url) . "')" : '';
    $has = $url ? ' has-img' : '';
    if ($compact) {
        return '<div class="img-field img-compact' . $has . '" data-img-field>'
            . '<button type="button" class="img-thumb img-pick" style="' . $bg . '" title="Choose / upload image"></button>'
            . '<input type="hidden" name="' . e($name) . '" class="img-val" value="' . e($url) . '">'
            . '<button type="button" class="img-clear" title="Remove image">&times;</button></div>';
    }
    return '<div class="img-field' . $has . '" data-img-field>'
        . '<div class="img-thumb" style="' . $bg . '"></div>'
        . '<input type="hidden" name="' . e($name) . '" class="img-val" value="' . e($url) . '">'
        . '<div class="img-actions"><button type="button" class="btn btn-ghost img-pick" style="padding:7px 12px;font-size:13px">Choose / upload</button>'
        . '<button type="button" class="btn btn-ghost img-clear" style="padding:7px 12px;font-size:13px">Remove</button></div></div>';
}

// ---------- Hero slideshow ----------
function admin_hero(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
        $slides = [];
        $img = $_POST['s_image'] ?? [];
        $cnt = is_array($img) ? count($img) : 0;
        for ($i = 0; $i < $cnt; $i++) {
            $get = fn($k) => trim((string)($_POST[$k][$i] ?? ''));
            // skip fully-empty rows
            if ($get('s_image') === '' && $get('s_heading') === '' && $get('s_eyebrow') === '' && $get('s_sub') === '') continue;
            $align = in_array($_POST['s_align'][$i] ?? 'center', ['left', 'center', 'right'], true) ? $_POST['s_align'][$i] : 'center';
            $slides[] = [
                'image' => $get('s_image'), 'eyebrow' => $get('s_eyebrow'), 'heading' => $get('s_heading'),
                'accent' => $get('s_accent'), 'sub' => $get('s_sub'),
                'ctaText' => $get('s_ctaText'), 'ctaLink' => $get('s_ctaLink'),
                'cta2Text' => $get('s_cta2Text'), 'cta2Link' => $get('s_cta2Link'), 'align' => $align,
            ];
        }
        set_setting('hero', [
            'mode' => ($_POST['mode'] ?? 'slideshow') === 'image' ? 'image' : 'slideshow',
            'image' => trim((string)($_POST['image'] ?? '')),
            'height' => max(280, (int)($_POST['height'] ?? 620)),
            'padTop' => max(0, (int)($_POST['padTop'] ?? 96)),
            'padBottom' => max(0, (int)($_POST['padBottom'] ?? 104)),
            'titleSize' => max(16, (int)($_POST['titleSize'] ?? 84)),
            'subSize' => max(11, (int)($_POST['subSize'] ?? 20)),
            'eyebrowSize' => max(9, (int)($_POST['eyebrowSize'] ?? 12)),
            'transition' => in_array($_POST['transition'] ?? 'fade', ['fade', 'slide'], true) ? $_POST['transition'] : 'fade',
            'speed' => max(150, (int)($_POST['speed'] ?? 600)),
            'interval' => max(0, (int)($_POST['interval'] ?? 6)),
            'slides' => $slides,
        ]);
        log_event('system', 'Hero updated', 'info', ['slides' => count($slides)]);
        redirect('admin/hero');
    }

    $h = hero();
    $sel = fn($cur, $v) => $cur === $v ? ' selected' : '';
    $mode = ($h['mode'] ?? 'slideshow') === 'image' ? 'image' : 'slideshow';
    $rows = '';
    foreach ($h['slides'] as $s) $rows .= hero_row_html($s);

    $hImg = (string)($h['image'] ?? '');
    $imgThumb = $hImg ? 'background-image:url(\'' . e($hImg) . '\')' : '';
    $modeCard = '<div class="admin-card" style="margin-bottom:20px"><h3 style="margin-bottom:6px">Hero type</h3>'
        . '<p style="color:var(--muted);font-size:13px;margin:0 0 14px">Choose a full slideshow with text and buttons, or a single plain image with no wording.</p>'
        . '<div class="hero-mode-pick">'
        . '<label class="hero-mode-opt"><input type="radio" name="mode" value="slideshow"' . ($mode === 'slideshow' ? ' checked' : '') . ' onchange="heroMode()"> <span><strong>Slideshow</strong><br><span style="color:var(--muted);font-size:12px">Slides with heading, text &amp; buttons</span></span></label>'
        . '<label class="hero-mode-opt"><input type="radio" name="mode" value="image"' . ($mode === 'image' ? ' checked' : '') . ' onchange="heroMode()"> <span><strong>Single image</strong><br><span style="color:var(--muted);font-size:12px">One image, no text overlay</span></span></label>'
        . '</div>'
        . '<div class="field" style="margin-top:14px;margin-bottom:0"><label>Hero height (px)</label><input name="height" type="number" value="' . e((string)$h['height']) . '" style="max-width:220px"></div>'
        . '</div>';

    $imageCard = '<div id="hero-image-only" class="admin-card" style="margin-bottom:20px"><h3 style="margin-bottom:10px">Hero image</h3>'
        . '<div data-uprow style="display:flex;gap:16px;align-items:flex-start">'
        . '<div style="flex:none;width:260px"><div class="thumb" style="' . $imgThumb . ';width:260px;height:130px;border:1px solid var(--border);border-radius:10px;background-size:cover;background-position:center;background-color:var(--bg-warm)"></div>'
        . '<input type="file" accept="image/*" onchange="resizeUpload(this)" style="margin-top:8px;font-size:12px;width:260px">'
        . '<input type="hidden" name="image" class="s-image" value="' . e($hImg) . '">'
        . '<div class="up-status" style="font-size:11px;color:var(--muted);margin-top:4px"></div>'
        . '<p style="font-size:11px;color:var(--muted);margin:6px 0 0">Auto-resized to ≤1920px wide. Set the banner height in the box above.</p></div>'
        . '</div></div>';

    $body = '<div class="admin-topbar"><h1>Hero</h1></div>'
        . '<form method="post" id="hero-form">' . csrf_field()
        . $modeCard . $imageCard
        . '<div id="hero-slideshow-only"><div class="admin-card" style="margin-bottom:20px"><h3 style="margin-bottom:10px">Display</h3><div class="field-row">'
        . '<div class="field"><label>Transition</label><select name="transition"><option value="fade"' . $sel($h['transition'], 'fade') . '>Fade</option><option value="slide"' . $sel($h['transition'], 'slide') . '>Slide</option></select></div>'
        . '<div class="field"><label>&nbsp;</label><div style="font-size:12px;color:var(--muted);padding-top:10px">Slides cross-fade or slide across.</div></div></div>'
        . '<div class="field-row"><div class="field"><label>Transition speed (ms)</label><input name="speed" type="number" value="' . e((string)$h['speed']) . '"></div>'
        . '<div class="field"><label>Auto-advance (seconds, 0 = off)</label><input name="interval" type="number" value="' . e((string)$h['interval']) . '"></div></div>'
        . '<div class="field-row"><div class="field"><label>Padding top (px)</label><input name="padTop" type="number" value="' . e((string)($h['padTop'] ?? 96)) . '"></div>'
        . '<div class="field"><label>Padding bottom (px)</label><input name="padBottom" type="number" value="' . e((string)($h['padBottom'] ?? 104)) . '"></div></div>'
        . '<div class="field-row"><div class="field"><label>Title size (px)</label><input name="titleSize" type="number" value="' . e((string)($h['titleSize'] ?? 84)) . '"></div>'
        . '<div class="field"><label>Sub-text size (px)</label><input name="subSize" type="number" value="' . e((string)($h['subSize'] ?? 20)) . '"></div></div>'
        . '<div class="field"><label>Eyebrow size (px)</label><input name="eyebrowSize" type="number" value="' . e((string)($h['eyebrowSize'] ?? 12)) . '" style="max-width:200px"></div></div>'
        . '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px"><h3 style="margin:0">Slides</h3>'
        . '<button type="button" class="btn btn-ghost" style="padding:8px 12px;font-size:13px" onclick="addSlide()">Add slide ' . icon('plus') . '</button></div>'
        . '<div id="hero-rows">' . $rows . '</div></div>'
        . '<div style="margin-top:16px"><button class="btn btn-flame">Save hero ' . icon('check') . '</button></div></form>'
        . hero_admin_script();
    respond_admin($body, ['title' => 'Hero · Admin']);
}

function hero_row_html(array $s = []): string
{
    $g = fn($k) => e((string)($s[$k] ?? ''));
    $align = $s['align'] ?? 'center';
    $aopt = '';
    foreach (['left' => 'Left', 'center' => 'Center', 'right' => 'Right'] as $v => $l) $aopt .= '<option value="' . $v . '"' . ($align === $v ? ' selected' : '') . '>' . $l . '</option>';
    $img = (string)($s['image'] ?? '');
    $thumb = $img ? 'background-image:url(\'' . e($img) . '\')' : '';
    return '<div class="admin-card hero-row" data-uprow style="margin-bottom:16px">'
        . '<div style="display:flex;gap:16px;align-items:flex-start">'
        . '<div style="flex:none;width:160px"><div class="thumb" style="' . $thumb . ';width:160px;height:90px;border:1px solid var(--border);border-radius:10px;background-size:cover;background-position:center;background-color:var(--bg-warm)"></div>'
        . '<input type="file" accept="image/*" onchange="resizeUpload(this)" style="margin-top:8px;font-size:12px;width:160px">'
        . '<input type="hidden" name="s_image[]" class="s-image" value="' . e($img) . '">'
        . '<div class="up-status" style="font-size:11px;color:var(--muted);margin-top:4px"></div>'
        . '<p style="font-size:11px;color:var(--muted);margin:6px 0 0">Auto-resized to ≤1920px wide.</p></div>'
        . '<div style="flex:1;min-width:0;display:grid;gap:8px">'
        . '<div class="field-row" style="margin:0"><input name="s_eyebrow[]" placeholder="Eyebrow" value="' . $g('eyebrow') . '" style="padding:9px 11px;border:1px solid var(--border);border-radius:8px">'
        . '<select name="s_align[]" style="padding:9px 11px;border:1px solid var(--border);border-radius:8px">' . $aopt . '</select></div>'
        . '<div class="field-row" style="margin:0"><input name="s_heading[]" placeholder="Heading" value="' . $g('heading') . '" style="padding:9px 11px;border:1px solid var(--border);border-radius:8px">'
        . '<input name="s_accent[]" placeholder="Highlighted word(s)" value="' . $g('accent') . '" style="padding:9px 11px;border:1px solid var(--border);border-radius:8px"></div>'
        . '<textarea name="s_sub[]" placeholder="Sub text" rows="2" style="padding:9px 11px;border:1px solid var(--border);border-radius:8px">' . $g('sub') . '</textarea>'
        . '<div class="field-row" style="margin:0"><input name="s_ctaText[]" placeholder="Button 1 text" value="' . $g('ctaText') . '" style="padding:9px 11px;border:1px solid var(--border);border-radius:8px">'
        . '<input name="s_ctaLink[]" placeholder="Button 1 link" value="' . $g('ctaLink') . '" style="padding:9px 11px;border:1px solid var(--border);border-radius:8px"></div>'
        . '<div class="field-row" style="margin:0"><input name="s_cta2Text[]" placeholder="Button 2 text" value="' . $g('cta2Text') . '" style="padding:9px 11px;border:1px solid var(--border);border-radius:8px">'
        . '<input name="s_cta2Link[]" placeholder="Button 2 link" value="' . $g('cta2Link') . '" style="padding:9px 11px;border:1px solid var(--border);border-radius:8px"></div>'
        . '<div style="display:flex;gap:8px"><button type="button" class="btn btn-ghost" style="padding:6px 10px;font-size:13px" onclick="moveSlide(this,-1)">↑ Up</button>'
        . '<button type="button" class="btn btn-ghost" style="padding:6px 10px;font-size:13px" onclick="moveSlide(this,1)">↓ Down</button>'
        . '<button type="button" class="btn btn-danger" style="padding:6px 10px;font-size:13px;margin-left:auto" onclick="this.closest(\'.hero-row\').remove()">Remove</button></div>'
        . '</div></div></div>';
}

function hero_admin_script(): string
{
    $tpl = json_encode(hero_row_html([]));
    $uploadUrl = url('admin/upload');
    return '<script>
var HERO_TPL=' . $tpl . ', UP="' . $uploadUrl . '";
function addSlide(){var d=document.createElement("div");d.innerHTML=HERO_TPL;document.getElementById("hero-rows").appendChild(d.firstChild);}
function moveSlide(btn,dir){var row=btn.closest(".hero-row");if(dir<0&&row.previousElementSibling)row.parentNode.insertBefore(row,row.previousElementSibling);if(dir>0&&row.nextElementSibling)row.parentNode.insertBefore(row.nextElementSibling,row);}
function heroMode(){var img=document.querySelector("input[name=mode][value=image]").checked;document.getElementById("hero-image-only").style.display=img?"":"none";document.getElementById("hero-slideshow-only").style.display=img?"none":"";}
function resizeUpload(input){var f=input.files[0];if(!f)return;var row=input.closest("[data-uprow]");var st=row.querySelector(".up-status");st.textContent="Resizing…";var img=new Image();var u=URL.createObjectURL(f);img.onload=function(){var max=1920,w=img.width,h=img.height;if(w>max){h=Math.round(h*max/w);w=max;}var c=document.createElement("canvas");c.width=w;c.height=h;c.getContext("2d").drawImage(img,0,0,w,h);c.toBlob(function(b){var fd=new FormData();fd.append("file",b,"hero.jpg");st.textContent="Uploading…";fetch(UP,{method:"POST",body:fd}).then(function(r){return r.json();}).then(function(j){if(j.url){row.querySelector(".s-image").value=j.url;row.querySelector(".thumb").style.backgroundImage="url(\'"+j.url+"\')";st.textContent="Uploaded ("+w+"px)";}else{st.textContent=j.error||"Failed";}}).catch(function(){st.textContent="Upload failed";});URL.revokeObjectURL(u);},"image/jpeg",0.85);};img.src=u;}
heroMode();
</script>';
}

// ---------- Products ----------
function admin_products(array $seg): void
{
    $sub = $seg[1] ?? '';
    if ($sub === 'new') { admin_product_form(null); return; }
    if ($sub !== '' && ctype_digit((string)$sub)) {
        if (($seg[2] ?? '') === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (csrf_check()) q_exec('DELETE FROM products WHERE id=?', [(int)$sub]);
            redirect('admin/products');
        }
        admin_product_form((int)$sub); return;
    }
    $q = trim((string)($_GET['q'] ?? ''));
    $cat = (string)($_GET['cat'] ?? '');
    $status = in_array($_GET['status'] ?? '', ['published', 'draft'], true) ? $_GET['status'] : '';
    $sort = (string)($_GET['sort'] ?? 'new');

    $cond = []; $params = [];
    if ($q !== '') {
        $like = '%' . $q . '%';
        $cond[] = '(p.title LIKE ? OR p.sku LIKE ? OR c.name LIKE ? OR EXISTS (SELECT 1 FROM product_variants v2 WHERE v2.product_id=p.id AND v2.sku LIKE ?))';
        array_push($params, $like, $like, $like, $like);
    }
    if ($cat !== '' && ctype_digit($cat)) {
        $ids = subtree_ids((int)$cat); // include sub-categories
        $cond[] = 'p.category_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
        foreach ($ids as $cid) $params[] = $cid;
    }
    if ($status !== '') { $cond[] = 'p.status = ?'; $params[] = $status; }
    $where = $cond ? ' WHERE ' . implode(' AND ', $cond) : '';

    switch ($sort) {
        case 'old':        $order = 'p.created_at ASC, p.id ASC'; break;
        case 'az':         $order = 'p.title ASC'; break;
        case 'za':         $order = 'p.title DESC'; break;
        case 'price_low':  $order = 'p.price IS NULL, p.price ASC'; break;
        case 'price_high': $order = 'p.price DESC'; break;
        default:           $sort = 'new'; $order = 'p.created_at DESC, p.id DESC';
    }

    $rows = q_all('SELECT p.*, c.name cat, (SELECT COUNT(*) FROM product_variants v WHERE v.product_id=p.id) vc FROM products p LEFT JOIN categories c ON c.id=p.category_id' . $where . ' ORDER BY ' . $order . ' LIMIT 500', $params);
    $body = '';
    foreach ($rows as $p) {
        $sku = $p['sku'] ?: ((int)$p['vc'] > 0 ? ((int)$p['vc'] . ' variants') : '—');
        $price = (int)$p['vc'] > 0 ? '—' : (money($p['price']) ?? 'Enquire');
        $body .= '<tr><td style="font-weight:600">' . e($p['title']) . ((int)$p['featured'] ? ' <span class="badge published">Featured</span>' : '') . '</td>'
            . '<td style="font-family:var(--f-mono);font-size:12px">' . e($sku) . '</td><td>' . e($p['cat'] ?? '—') . '</td><td>' . e($price) . '</td>'
            . '<td><span class="badge ' . e($p['status']) . '">' . e($p['status']) . '</span></td>'
            . '<td style="text-align:right"><a href="' . url('admin/products/' . $p['id']) . '" class="btn btn-ghost" style="padding:6px 11px;font-size:13px">Edit</a></td></tr>';
    }

    $catOpts = '<option value="">All categories</option>';
    foreach (category_options() as $o) {
        $catOpts .= '<option value="' . $o['id'] . '"' . ((string)$o['id'] === $cat ? ' selected' : '') . '>' . e($o['name']) . '</option>';
    }
    $statusOpts = '<option value="">All statuses</option>'
        . '<option value="published"' . ($status === 'published' ? ' selected' : '') . '>Published</option>'
        . '<option value="draft"' . ($status === 'draft' ? ' selected' : '') . '>Draft</option>';
    $sortList = ['new' => 'Newest first', 'old' => 'Oldest first', 'az' => 'Title A–Z', 'za' => 'Title Z–A', 'price_low' => 'Price low → high', 'price_high' => 'Price high → low'];
    $sortOpts = '';
    foreach ($sortList as $v => $l) $sortOpts .= '<option value="' . $v . '"' . ($sort === $v ? ' selected' : '') . '>' . $l . '</option>';

    $active = $q !== '' || $cat !== '' || $status !== '' || $sort !== 'new';
    $clear = $active ? '<a href="' . url('admin/products') . '" class="btn btn-ghost">Clear</a>' : '';
    $filters = '<form method="get" action="' . url('admin/products') . '" class="admin-filters">'
        . '<div class="admin-search-field">' . icon('search')
        . '<input type="search" name="q" value="' . e($q) . '" placeholder="Search title or SKU…"></div>'
        . '<select name="cat">' . $catOpts . '</select>'
        . '<select name="status">' . $statusOpts . '</select>'
        . '<select name="sort">' . $sortOpts . '</select>'
        . '<button class="btn btn-flame">Apply</button>' . $clear . '</form>';

    $title = 'Products (' . count($rows) . ')' . ($active ? ' · filtered' : '');
    $empty = $active ? '<div class="admin-card">No products match these filters. <a href="' . url('admin/products') . '">Clear</a></div>' : '<div class="admin-card">No products yet.</div>';
    $h = '<div class="admin-topbar"><h1>' . $title . '</h1><a href="' . url('admin/products/new') . '" class="btn btn-flame">New product ' . icon('plus') . '</a></div>'
        . $filters
        . ($rows ? '<table class="admin-table"><thead><tr><th>Title</th><th>SKU</th><th>Category</th><th>Price</th><th>Status</th><th></th></tr></thead><tbody>' . $body . '</tbody></table>' : $empty);
    respond_admin($h, ['title' => 'Products · Admin']);
}

function admin_product_form(?int $id): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') { admin_product_save($id); return; }
    ensure_variant_image_col();
    $p = $id ? q_one('SELECT * FROM products WHERE id=?', [$id]) : null;
    if ($id && !$p) { http_response_code(404); respond_admin('<h1>Not found</h1>'); return; }
    $variants = $id ? q_all('SELECT * FROM product_variants WHERE product_id=? ORDER BY position', [$id]) : [];
    $img = $p ? (json_arr($p['images'] ?? null)[0] ?? '') : '';

    $opts = '<option value="">— None —</option>';
    foreach (category_options() as $o) {
        $sel = $p && (int)$p['category_id'] === $o['id'] ? ' selected' : '';
        $opts .= '<option value="' . $o['id'] . '"' . $sel . '>' . e($o['name']) . '</option>';
    }

    $varRows = '';
    foreach ($variants as $i => $v) $varRows .= variant_row_html($v['label'], $v['sku'], $v['price'], (string)($v['image'] ?? ''));

    $statusSel = fn($s) => $p && $p['status'] === $s ? ' selected' : '';
    $saved = isset($_GET['saved']) ? '<div class="admin-flash">' . icon('check') . ' Product saved.</div>' : '';
    $view = ($id && !empty($p['slug'])) ? '<a href="' . url('products/' . $p['slug']) . '" target="_blank" class="btn btn-ghost">View product ' . icon('arrow-right') . '</a>' : '';
    $h = '<div class="admin-topbar"><h1>' . ($id ? 'Edit product' : 'New product') . '</h1>'
        . '<div style="display:flex;gap:10px">' . $view . '<a href="' . url('admin/products') . '" class="btn btn-ghost">Back to products</a></div></div>'
        . $saved
        . '<form method="post" enctype="multipart/form-data">' . csrf_field()
        . '<div class="admin-card" style="margin-bottom:20px"><div class="field-row">'
        . '<div class="field"><label>Title</label><input name="title" required value="' . e($p['title'] ?? '') . '"></div>'
        . '<div class="field"><label>SKU (blank if using variants)</label><input name="sku" value="' . e($p['sku'] ?? '') . '"></div></div>'
        . '<div class="field-row"><div class="field"><label>Slug (blank = auto)</label><input name="slug" value="' . e($p['slug'] ?? '') . '"></div>'
        . '<div class="field"><label>Price (AUD, blank = Enquire)</label><input name="price" type="number" step="0.01" value="' . e($p['price'] ?? '') . '"></div></div>'
        . '<div class="field-row"><div class="field"><label>Category</label><select name="category_id">' . $opts . '</select></div>'
        . '<div class="field"><label>Status</label><select name="status"><option value="draft"' . $statusSel('draft') . '>Draft</option><option value="published"' . $statusSel('published') . '>Published</option></select></div></div>'
        . '<div class="checkbox-row field"><input id="featured" type="checkbox" name="featured" value="1"' . ($p && (int)$p['featured'] ? ' checked' : '') . '><label for="featured" style="margin:0;text-transform:none;letter-spacing:0">Feature on homepage</label></div>'
        . '</div>'
        . '<div class="admin-card" style="margin-bottom:20px"><div class="field" style="margin-bottom:0"><label>Main image</label>'
        . '<p style="color:var(--muted);font-size:13px;margin:0 0 10px">Shown when the product has no variants, or as the default photo. Pick from the media library or upload a new one.</p>'
        . img_field('image_url', $img) . '</div></div>'
        . '<div class="admin-card" style="margin-bottom:20px"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">'
        . '<label style="margin:0;' . ADM_LABEL . '">Variants / sizes</label><button type="button" class="btn btn-ghost" style="padding:7px 12px;font-size:13px" onclick="addVarRow()">Add size ' . icon('plus') . '</button></div>'
        . '<p style="color:var(--muted);font-size:13px;margin-top:0">A row per size with its own part number. Leave empty for a single-item product (use SKU + price above).</p>'
        . '<div id="variant-rows">' . $varRows . '</div></div>'
        . '<div class="admin-card" style="margin-bottom:20px"><div class="field" style="margin-bottom:0"><label>Description</label><textarea name="description" class="richtext" rows="6">' . e($p['description'] ?? '') . '</textarea></div></div>'
        . '<div style="display:flex;gap:12px"><button class="btn btn-flame">' . ($id ? 'Save changes' : 'Create product') . ' ' . icon('check') . '</button>'
        . ($id ? '<button type="button" class="btn btn-danger" onclick="if(confirm(\'Delete this product?\')){document.getElementById(\'delform\').submit();}">' . icon('trash') . ' Delete</button>' : '')
        . '</div></form>'
        . ($id ? '<form id="delform" method="post" action="' . url('admin/products/' . $id . '/delete') . '" style="display:none">' . csrf_field() . '</form>' : '')
        . admin_variant_script();
    respond_admin($h, ['title' => ($id ? 'Edit' : 'New') . ' product · Admin']);
}

function variant_row_html(string $label = '', string $sku = '', $price = '', string $image = ''): string
{
    return '<div class="vrow" style="display:grid;grid-template-columns:56px 1.4fr 1fr .8fr auto;gap:8px;align-items:center;margin-bottom:8px">'
        . img_field('v_image[]', $image, true)
        . '<input name="v_label[]" placeholder="15mm" value="' . e($label) . '" style="padding:9px 11px;border:1px solid var(--border);border-radius:8px">'
        . '<input name="v_sku[]" placeholder="FSABR1536" value="' . e($sku) . '" style="padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-family:var(--f-mono);font-size:13px">'
        . '<input name="v_price[]" type="number" step="0.01" placeholder="—" value="' . e((string)$price) . '" style="padding:9px 11px;border:1px solid var(--border);border-radius:8px">'
        . '<button type="button" class="icon-btn" onclick="this.closest(\'.vrow\').remove()" aria-label="Remove">' . icon('trash') . '</button></div>';
}

function admin_variant_script(): string
{
    $tpl = json_encode(variant_row_html());
    return '<script>function addVarRow(){var d=document.createElement("div");d.innerHTML=' . $tpl . ';document.getElementById("variant-rows").appendChild(d.firstChild);}</script>';
}

function admin_product_save(?int $id): void
{
    if (!csrf_check()) redirect('admin/products');
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') redirect('admin/products');
    $slug = slugify((string)($_POST['slug'] ?: $title));
    $sku = trim((string)($_POST['sku'] ?? '')) ?: null;
    $price = ($_POST['price'] ?? '') === '' ? null : (float)$_POST['price'];
    $categoryId = ($_POST['category_id'] ?? '') === '' ? null : (int)$_POST['category_id'];
    $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    $featured = !empty($_POST['featured']) ? 1 : 0;
    $description = (string)($_POST['description'] ?? '');

    ensure_variant_image_col();

    // main image (URL chosen from media library / uploaded via picker)
    $imageUrl = trim((string)($_POST['image_url'] ?? ''));
    $imagesJson = json_encode($imageUrl !== '' ? [$imageUrl] : []);

    try {
        if ($id) {
            q_exec('UPDATE products SET category_id=?,sku=?,title=?,slug=?,description=?,price=?,images=?,status=?,featured=? WHERE id=?',
                [$categoryId, $sku, $title, $slug, $description, $price, $imagesJson, $status, $featured, $id]);
        } else {
            $id = (int)q_exec('INSERT INTO products (category_id,sku,title,slug,description,price,images,status,featured) VALUES (?,?,?,?,?,?,?,?,?)',
                [$categoryId, $sku, $title, $slug, $description, $price, $imagesJson, $status, $featured]);
        }
        // variants: replace all
        q_exec('DELETE FROM product_variants WHERE product_id=?', [$id]);
        $labels = $_POST['v_label'] ?? []; $skus = $_POST['v_sku'] ?? []; $prices = $_POST['v_price'] ?? []; $vimgs = $_POST['v_image'] ?? [];
        $pos = 0;
        foreach ($skus as $i => $vsku) {
            $vsku = trim((string)$vsku); $vlabel = trim((string)($labels[$i] ?? ''));
            if ($vsku === '' || $vlabel === '') continue;
            $vprice = ($prices[$i] ?? '') === '' ? null : (float)$prices[$i];
            $vimg = trim((string)($vimgs[$i] ?? '')) ?: null;
            q_exec('INSERT INTO product_variants (product_id,sku,label,price,image,position) VALUES (?,?,?,?,?,?)', [$id, $vsku, $vlabel, $vprice, $vimg, $pos++]);
        }
        log_event('system', "Product saved: $title", 'info', ['id' => $id]);
    } catch (Throwable $ex) {
        respond_admin('<div class="admin-topbar"><h1>Save failed</h1></div><div class="admin-card"><p class="form-error">' . e($ex->getMessage()) . '</p><a href="' . url('admin/products') . '" class="btn btn-ghost">Back</a></div>');
        return;
    }
    redirect('admin/products/' . $id . '?saved=1');
}

// ---------- Categories ----------
function admin_categories(array $seg): void
{
    $sub = $seg[1] ?? '';
    if ($sub !== '' && ctype_digit((string)$sub)) {
        if (($seg[2] ?? '') === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (csrf_check()) { try { q_exec('DELETE FROM categories WHERE id=?', [(int)$sub]); } catch (Throwable $e) {} }
            redirect('admin/categories');
        }
        admin_category_form((int)$sub); return;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') { admin_category_save(null); return; }

    $opts = category_filter_tree();
    $countById = [];
    foreach (q_all('SELECT c.id, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id) pc, (SELECT COUNT(*) FROM categories ch WHERE ch.parent_id=c.id) cc FROM categories c') as $r) $countById[(int)$r['id']] = $r;
    $rows = '';
    foreach ($opts as $o) {
        $rc = $countById[$o['id']] ?? ['pc' => 0, 'cc' => 0];
        $rows .= '<tr><td style="padding-left:' . (16 + $o['depth'] * 22) . 'px;font-weight:' . ($o['depth'] === 0 ? 700 : 500) . '">' . ($o['depth'] > 0 ? '<span style="color:var(--muted)">└ </span>' : '') . e($o['name']) . '</td>'
            . '<td>' . (int)$rc['pc'] . '</td><td>' . (int)$rc['cc'] . '</td>'
            . '<td style="text-align:right"><a href="' . url('admin/categories/' . $o['id']) . '" class="btn btn-ghost" style="padding:6px 11px;font-size:13px">Edit</a></td></tr>';
    }
    $h = '<div class="admin-topbar"><h1>Categories (' . count($opts) . ')</h1></div>'
        . '<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:24px;align-items:start">'
        . '<div>' . ($opts ? '<table class="admin-table"><thead><tr><th>Name</th><th>Products</th><th>Sub-cats</th><th></th></tr></thead><tbody>' . $rows . '</tbody></table>' : '<div class="admin-card">No categories yet.</div>') . '</div>'
        . '<div><h3 style="margin-bottom:12px;font-size:18px">Add category</h3>' . category_form_fields(null) . '</div></div>';
    respond_admin($h, ['title' => 'Categories · Admin']);
}

function category_form_fields(?array $c): string
{
    $opts = '<option value="">— Top level —</option>';
    foreach (category_options() as $o) {
        if ($c && $o['id'] === (int)$c['id']) continue;
        $sel = $c && (int)($c['parent_id'] ?? 0) === $o['id'] ? ' selected' : '';
        $opts .= '<option value="' . $o['id'] . '"' . $sel . '>' . e($o['name']) . '</option>';
    }
    $action = $c ? url('admin/categories/' . $c['id']) : url('admin/categories');
    return '<form method="post" enctype="multipart/form-data" action="' . $action . '" class="admin-card">' . csrf_field()
        . '<div class="field"><label>Name</label><input name="name" required value="' . e($c['name'] ?? '') . '"></div>'
        . '<div class="field"><label>Slug (blank = auto)</label><input name="slug" value="' . e($c['slug'] ?? '') . '"></div>'
        . '<div class="field"><label>Parent</label><select name="parent_id">' . $opts . '</select></div>'
        . '<div class="field"><label>Position</label><input name="position" type="number" value="' . e((string)($c['position'] ?? 0)) . '"></div>'
        . '<div class="field"><label>Description</label><textarea name="description" rows="2">' . e($c['description'] ?? '') . '</textarea></div>'
        . '<div class="field"><label>Image</label>' . (!empty($c['image']) ? '<div class="uploader-thumb" style="margin-bottom:8px"><img src="' . e($c['image']) . '"></div>' : '') . '<input type="file" name="image" accept="image/*"></div>'
        . '<div style="display:flex;gap:12px"><button class="btn btn-flame">' . ($c ? 'Save changes' : 'Add category') . '</button>'
        . ($c ? '<button type="button" class="btn btn-danger" onclick="if(confirm(\'Delete?\'))document.getElementById(\'cdel\').submit()">' . icon('trash') . ' Delete</button>' : '') . '</div></form>'
        . ($c ? '<form id="cdel" method="post" action="' . url('admin/categories/' . $c['id'] . '/delete') . '" style="display:none">' . csrf_field() . '</form>' : '');
}

function admin_category_form(int $id): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') { admin_category_save($id); return; }
    $c = q_one('SELECT * FROM categories WHERE id=?', [$id]);
    if (!$c) { http_response_code(404); respond_admin('<h1>Not found</h1>'); return; }
    $h = '<div class="admin-topbar"><h1>Edit category</h1><a href="' . url('admin/categories') . '" class="btn btn-ghost">Back</a></div><div style="max-width:560px">' . category_form_fields($c) . '</div>';
    respond_admin($h, ['title' => 'Edit category · Admin']);
}

function admin_category_save(?int $id): void
{
    if (!csrf_check()) redirect('admin/categories');
    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '') redirect('admin/categories');
    $slug = slugify((string)($_POST['slug'] ?: $name));
    $parent = ($_POST['parent_id'] ?? '') === '' ? null : (int)$_POST['parent_id'];
    if ($id && $parent === $id) $parent = null;
    $position = (int)($_POST['position'] ?? 0);
    $desc = trim((string)($_POST['description'] ?? '')) ?: null;
    $image = $id ? (q_val('SELECT image FROM categories WHERE id=?', [$id]) ?: null) : null;
    if (!empty($_FILES['image']['name'])) { $u = save_upload($_FILES['image']); if ($u) $image = $u; }
    try {
        if ($id) q_exec('UPDATE categories SET name=?,slug=?,parent_id=?,position=?,description=?,image=? WHERE id=?', [$name, $slug, $parent, $position, $desc, $image, $id]);
        else q_exec('INSERT INTO categories (name,slug,parent_id,position,description,image) VALUES (?,?,?,?,?,?)', [$name, $slug, $parent, $position, $desc, $image]);
    } catch (Throwable $e) {}
    redirect('admin/categories');
}

// ---------- Blog ----------
function admin_blog(array $seg): void
{
    $sub = $seg[1] ?? '';
    if ($sub === 'new') { admin_blog_form(null); return; }
    if ($sub !== '' && ctype_digit((string)$sub)) {
        if (($seg[2] ?? '') === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (csrf_check()) q_exec('DELETE FROM blog_posts WHERE id=?', [(int)$sub]);
            redirect('admin/blog');
        }
        admin_blog_form((int)$sub); return;
    }
    $rows = q_all('SELECT * FROM blog_posts ORDER BY created_at DESC');
    $body = '';
    foreach ($rows as $p) $body .= '<tr><td style="font-weight:600">' . e($p['title']) . '</td><td>' . e($p['category'] ?? '—') . '</td><td><span class="badge ' . e($p['status']) . '">' . e($p['status']) . '</span></td><td>' . e(fmt_date($p['published_at'])) . '</td><td style="text-align:right"><a href="' . url('admin/blog/' . $p['id']) . '" class="btn btn-ghost" style="padding:6px 11px;font-size:13px">Edit</a></td></tr>';
    $h = '<div class="admin-topbar"><h1>Blog (' . count($rows) . ')</h1><a href="' . url('admin/blog/new') . '" class="btn btn-flame">New post ' . icon('plus') . '</a></div>'
        . ($rows ? '<table class="admin-table"><thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Published</th><th></th></tr></thead><tbody>' . $body . '</tbody></table>' : '<div class="admin-card">No posts yet.</div>');
    respond_admin($h, ['title' => 'Blog · Admin']);
}

function admin_blog_form(?int $id): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') { admin_blog_save($id); return; }
    $p = $id ? q_one('SELECT * FROM blog_posts WHERE id=?', [$id]) : null;
    if ($id && !$p) { http_response_code(404); respond_admin('<h1>Not found</h1>'); return; }
    $statusSel = fn($s) => $p && $p['status'] === $s ? ' selected' : '';
    $h = '<div class="admin-topbar"><h1>' . ($id ? 'Edit post' : 'New post') . '</h1><a href="' . url('admin/blog') . '" class="btn btn-ghost">Back</a></div>'
        . '<form method="post" enctype="multipart/form-data">' . csrf_field()
        . '<div class="admin-card" style="margin-bottom:20px">'
        . '<div class="field"><label>Title</label><input name="title" required value="' . e($p['title'] ?? '') . '"></div>'
        . '<div class="field-row"><div class="field"><label>Slug (blank = auto)</label><input name="slug" value="' . e($p['slug'] ?? '') . '"></div><div class="field"><label>Category tag</label><input name="category" value="' . e($p['category'] ?? '') . '" placeholder="Guide, Basics…"></div></div>'
        . '<div class="field-row"><div class="field"><label>Author</label><input name="author" value="' . e($p['author'] ?? 'Fire Safe Australia') . '"></div><div class="field"><label>Status</label><select name="status"><option value="draft"' . $statusSel('draft') . '>Draft</option><option value="published"' . $statusSel('published') . '>Published</option></select></div></div>'
        . '<div class="field"><label>Excerpt</label><textarea name="excerpt" rows="2">' . e($p['excerpt'] ?? '') . '</textarea></div>'
        . '<div class="field" style="margin-bottom:0"><label>Cover image</label>' . (!empty($p['cover_image']) ? '<div class="uploader-thumb" style="margin-bottom:8px"><img src="' . e($p['cover_image']) . '"></div>' : '') . '<input type="file" name="cover" accept="image/*"></div></div>'
        . '<div class="admin-card" style="margin-bottom:20px"><div class="field" style="margin-bottom:0"><label>Content</label><textarea name="content" class="richtext" rows="12">' . e($p['content'] ?? '') . '</textarea></div></div>'
        . '<div style="display:flex;gap:12px"><button class="btn btn-flame">' . ($id ? 'Save changes' : 'Create post') . ' ' . icon('check') . '</button>'
        . ($id ? '<button type="button" class="btn btn-danger" onclick="if(confirm(\'Delete?\'))document.getElementById(\'bdel\').submit()">' . icon('trash') . ' Delete</button>' : '') . '</div></form>'
        . ($id ? '<form id="bdel" method="post" action="' . url('admin/blog/' . $id . '/delete') . '" style="display:none">' . csrf_field() . '</form>' : '');
    respond_admin($h, ['title' => ($id ? 'Edit' : 'New') . ' post · Admin']);
}

function admin_blog_save(?int $id): void
{
    if (!csrf_check()) redirect('admin/blog');
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') redirect('admin/blog');
    $slug = slugify((string)($_POST['slug'] ?: $title));
    $excerpt = trim((string)($_POST['excerpt'] ?? '')) ?: null;
    $content = (string)($_POST['content'] ?? '');
    $category = trim((string)($_POST['category'] ?? '')) ?: null;
    $author = trim((string)($_POST['author'] ?? '')) ?: null;
    $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    $cover = $id ? (q_val('SELECT cover_image FROM blog_posts WHERE id=?', [$id]) ?: null) : null;
    if (!empty($_FILES['cover']['name'])) { $u = save_upload($_FILES['cover']); if ($u) $cover = $u; }
    $existingPub = $id ? q_val('SELECT published_at FROM blog_posts WHERE id=?', [$id]) : null;
    $publishedAt = $status === 'published' ? ($existingPub ?: date('Y-m-d H:i:s')) : $existingPub;
    if ($id) q_exec('UPDATE blog_posts SET title=?,slug=?,excerpt=?,content=?,cover_image=?,category=?,author=?,status=?,published_at=? WHERE id=?', [$title, $slug, $excerpt, $content, $cover, $category, $author, $status, $publishedAt, $id]);
    else q_exec('INSERT INTO blog_posts (title,slug,excerpt,content,cover_image,category,author,status,published_at) VALUES (?,?,?,?,?,?,?,?,?)', [$title, $slug, $excerpt, $content, $cover, $category, $author, $status, $publishedAt]);
    redirect('admin/blog');
}

// ---------- Enquiries ----------
function admin_enquiries(array $seg): void
{
    $sub = $seg[1] ?? '';
    if ($sub !== '' && ctype_digit((string)$sub)) { admin_enquiry_view((int)$sub); return; }
    $rows = q_all('SELECT * FROM enquiries ORDER BY created_at DESC');
    $body = '';
    foreach ($rows as $en) {
        $items = json_arr($en['items']);
        $qty = array_sum(array_map(fn($i) => (int)($i['qty'] ?? 0), $items));
        $body .= '<tr><td style="font-weight:600">' . e($en['name']) . '</td><td>' . e($en['email']) . '</td><td>' . count($items) . ' lines · ' . $qty . ' units</td><td><span class="badge ' . e($en['status']) . '">' . e($en['status']) . '</span></td><td>' . e(fmt_date($en['created_at'])) . '</td><td style="text-align:right"><a href="' . url('admin/enquiries/' . $en['id']) . '" class="btn btn-ghost" style="padding:6px 11px;font-size:13px">View</a></td></tr>';
    }
    $h = '<div class="admin-topbar"><h1>Enquiries (' . count($rows) . ')</h1></div>'
        . ($rows ? '<table class="admin-table"><thead><tr><th>From</th><th>Email</th><th>Items</th><th>Status</th><th>Received</th><th></th></tr></thead><tbody>' . $body . '</tbody></table>' : '<div class="admin-card">No enquiries yet.</div>');
    respond_admin($h, ['title' => 'Enquiries · Admin']);
}

function admin_enquiry_view(int $id): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
        $st = in_array($_POST['status'] ?? '', ['new', 'responded', 'closed'], true) ? $_POST['status'] : 'new';
        q_exec('UPDATE enquiries SET status=? WHERE id=?', [$st, $id]);
        redirect('admin/enquiries/' . $id);
    }
    $en = q_one('SELECT * FROM enquiries WHERE id=?', [$id]);
    if (!$en) { http_response_code(404); respond_admin('<h1>Not found</h1>'); return; }
    $items = json_arr($en['items']);
    $rows = '';
    foreach ($items as $i) $rows .= '<tr><td style="font-family:var(--f-mono);font-size:12px">' . e($i['sku'] ?? '') . '</td><td>' . e($i['title'] ?? '') . '</td><td>' . (int)($i['qty'] ?? 0) . '</td></tr>';
    $sel = fn($s) => $en['status'] === $s ? ' selected' : '';
    $h = '<div class="admin-topbar"><h1>Enquiry from ' . e($en['name']) . '</h1><a href="' . url('admin/enquiries') . '" class="btn btn-ghost">Back</a></div>'
        . '<div style="display:grid;grid-template-columns:1fr 1.4fr;gap:24px;align-items:start">'
        . '<div class="admin-card"><h3 style="margin-bottom:14px">Contact</h3><p style="margin:0 0 8px"><strong>' . e($en['name']) . '</strong></p>'
        . '<p style="margin:0 0 6px"><a href="mailto:' . e($en['email']) . '" style="color:var(--flame-deep)">' . e($en['email']) . '</a></p>'
        . ($en['phone'] ? '<p style="margin:0 0 6px">' . e($en['phone']) . '</p>' : '')
        . '<p style="margin:0 0 16px;color:var(--muted);font-size:13px">Received ' . e(fmt_date($en['created_at'])) . '</p>'
        . '<form method="post"><label style="' . ADM_LABEL . '">Status</label>' . csrf_field()
        . '<select name="status" onchange="this.form.submit()" style="padding:8px 12px;border-radius:9px;border:1px solid var(--border)"><option value="new"' . $sel('new') . '>New</option><option value="responded"' . $sel('responded') . '>Responded</option><option value="closed"' . $sel('closed') . '>Closed</option></select></form>'
        . ($en['message'] ? '<h4 style="margin:20px 0 8px">Message</h4><p style="margin:0;white-space:pre-wrap">' . e($en['message']) . '</p>' : '') . '</div>'
        . '<div class="admin-card"><h3 style="margin-bottom:14px">Requested items</h3><table class="admin-table"><thead><tr><th>SKU</th><th>Item</th><th>Qty</th></tr></thead><tbody>' . $rows . '</tbody></table></div>'
        . '</div>';
    respond_admin($h, ['title' => 'Enquiry · Admin']);
}

// ---------- Orders ----------
function admin_orders(): void
{
    require_once __DIR__ . '/stripe.php';
    ensure_orders_table();
    $rows = q_all('SELECT * FROM orders ORDER BY created_at DESC LIMIT 500');
    $badge = ['paid' => 'published', 'pending' => 'new', 'failed' => 'closed', 'cancelled' => 'closed'];
    $body = '';
    foreach ($rows as $o) {
        $items = json_arr($o['items'] ?? '');
        $body .= '<tr><td style="font-family:var(--f-mono);font-size:12px">#' . (int)$o['id'] . '</td>'
            . '<td style="font-weight:600">' . e($o['name'] ?: '—') . '<br><span class="prod-sku">' . e($o['email']) . '</span></td>'
            . '<td>' . count($items) . ' lines</td>'
            . '<td>' . e(money($o['total']) ?? '—') . '</td>'
            . '<td><span class="badge ' . ($badge[$o['status']] ?? 'draft') . '">' . e($o['status']) . '</span></td>'
            . '<td>' . e(fmt_date($o['created_at'])) . '</td></tr>';
    }
    $h = '<div class="admin-topbar"><h1>Orders (' . count($rows) . ')</h1></div>'
        . (payments_enabled() ? '' : '<div class="admin-card" style="margin-bottom:16px;color:var(--muted)">Online payments are currently off — orders appear here once Stripe is enabled (Settings → Payments) and selling is on. The store runs in enquiry mode meanwhile (see Enquiries).</div>')
        . ($rows
            ? '<table class="admin-table"><thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th></tr></thead><tbody>' . $body . '</tbody></table>'
            : '<div class="admin-card">No orders yet.</div>');
    respond_admin($h, ['title' => 'Orders · Admin']);
}

// ---------- Customers ----------
function admin_customers(array $seg): void
{
    require_once __DIR__ . '/customer.php';
    ensure_customers_table();

    $id = $seg[1] ?? '';
    if ($id !== '' && ctype_digit((string)$id)) { admin_customer_view((int)$id); return; }

    $rows = q_all('SELECT c.*, (SELECT COUNT(*) FROM enquiries e WHERE e.email = c.email) enq FROM customers c ORDER BY c.created_at DESC LIMIT 1000');
    $body = '';
    foreach ($rows as $c) {
        $body .= '<tr><td style="font-weight:600">' . e($c['name']) . '</td>'
            . '<td>' . e($c['email']) . '</td>'
            . '<td>' . (int)$c['enq'] . '</td>'
            . '<td style="font-size:12px;color:var(--muted)">' . e(fmt_date($c['created_at'])) . '</td>'
            . '<td style="text-align:right"><a href="' . url('admin/customers/' . $c['id']) . '" class="btn btn-ghost" style="padding:6px 11px;font-size:13px">View</a></td></tr>';
    }
    $h = '<div class="admin-topbar"><h1>Customers (' . count($rows) . ')</h1></div>'
        . ($rows ? '<table class="admin-table"><thead><tr><th>Name</th><th>Email</th><th>Enquiries</th><th>Joined</th><th></th></tr></thead><tbody>' . $body . '</tbody></table>' : '<div class="admin-card">No customer accounts yet.</div>');
    respond_admin($h, ['title' => 'Customers · Admin']);
}

function admin_customer_view(int $id): void
{
    $c = q_one('SELECT * FROM customers WHERE id = ?', [$id]);
    if (!$c) { http_response_code(404); respond_admin('<h1>Not found</h1>'); return; }
    $enq = q_all('SELECT * FROM enquiries WHERE email = ? ORDER BY created_at DESC LIMIT 100', [$c['email']]);
    $rows = '';
    foreach ($enq as $en) {
        $rows .= '<tr><td>#' . (int)$en['id'] . '</td><td>' . count(json_arr($en['items'])) . ' items</td><td><span class="badge ' . e($en['status']) . '">' . e($en['status']) . '</span></td><td>' . e(fmt_date($en['created_at'])) . '</td>'
            . '<td style="text-align:right"><a href="' . url('admin/enquiries/' . $en['id']) . '" class="btn btn-ghost" style="padding:6px 11px;font-size:13px">Open</a></td></tr>';
    }
    $h = '<div class="admin-topbar"><h1>' . e($c['name']) . '</h1><a href="' . url('admin/customers') . '" class="btn btn-ghost">Back</a></div>'
        . '<div class="admin-card" style="margin-bottom:20px;max-width:520px"><p style="margin:0 0 6px"><a href="mailto:' . e($c['email']) . '" style="color:var(--flame-deep)">' . e($c['email']) . '</a></p>'
        . '<p style="margin:0;color:var(--muted);font-size:13px">Joined ' . e(fmt_date($c['created_at'])) . '</p></div>'
        . '<div class="admin-card"><h3 style="margin-bottom:12px">Enquiries</h3>'
        . ($rows ? '<table class="admin-table"><thead><tr><th>Ref</th><th>Items</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>' . $rows . '</tbody></table>' : '<p style="color:var(--muted)">No enquiries from this customer.</p>')
        . '</div>';
    respond_admin($h, ['title' => 'Customer · Admin']);
}

// ---------- Emails ----------
function admin_emails(array $seg): void
{
    $id = $seg[1] ?? '';
    $defs = email_defaults();
    if ($id !== '' && isset($defs[$id])) {
        if (($seg[2] ?? '') === 'preview') { admin_email_preview($id); return; }
        admin_email_edit($id);
        return;
    }

    $rows = '';
    foreach ($defs as $eid => $d) {
        $t = email_template($eid);
        $rows .= '<tr><td style="font-weight:600">' . e($d['label']) . '</td>'
            . '<td>' . ($d['to'] === 'admin' ? e(ADMIN_EMAIL) : 'Customer') . '</td>'
            . '<td><span class="badge ' . ($t['enabled'] ? 'published' : 'draft') . '">' . ($t['enabled'] ? 'On' : 'Off') . '</span></td>'
            . '<td style="text-align:right"><a href="' . url('admin/emails/' . $eid) . '" class="btn btn-ghost" style="padding:6px 11px;font-size:13px">Manage</a></td></tr>';
    }
    $h = '<div class="admin-topbar"><h1>Emails</h1></div>'
        . '<p style="color:var(--muted);margin-top:-10px;margin-bottom:18px">Notifications sent by the site. Click Manage to edit the subject, heading and content. Set up SMTP under <a href="' . url('admin/settings') . '" style="color:var(--flame-deep)">Settings → Email</a> so they deliver reliably.</p>'
        . '<table class="admin-table"><thead><tr><th>Email</th><th>Recipient</th><th>Status</th><th></th></tr></thead><tbody>' . $rows . '</tbody></table>';
    respond_admin($h, ['title' => 'Emails · Admin']);
}

function admin_email_edit(string $id): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
        $saved = (array)setting('emails', []);
        $saved[$id] = [
            'enabled' => !empty($_POST['enabled']),
            'subject' => trim((string)($_POST['subject'] ?? '')),
            'heading' => trim((string)($_POST['heading'] ?? '')),
            'body' => (string)($_POST['body'] ?? ''),
        ];
        set_setting('emails', $saved);
        redirect('admin/emails');
    }
    $t = email_template($id);
    $ph = '{name} {email} {phone} {message} {items} {order} {total} {site} {phone_co} {email_co}';
    $previewUrl = url('admin/emails/' . $id . '/preview');
    $recipient = $t['to'] === 'admin' ? ADMIN_EMAIL : 'The customer';

    $breadcrumb = '<div class="admin-breadcrumb"><a href="' . url('admin/emails') . '">Emails</a> <span>/</span> ' . e($t['label']) . '</div>';
    $head = $breadcrumb
        . '<div class="admin-topbar"><div><h1>Manage email</h1>'
        . '<p class="admin-sub">' . e($t['label']) . ' · content type <strong>text/html</strong></p></div>'
        . '<a href="' . url('admin/emails') . '" class="btn btn-ghost">Back</a></div>';

    $form = '<form method="post" id="email-form"><div class="admin-card">' . csrf_field()
        . '<label class="checkbox-row field"><input type="checkbox" name="enabled" value="1"' . ($t['enabled'] ? ' checked' : '') . '> <span style="margin-left:8px"><strong>Enable this email</strong><br><span style="color:var(--muted);font-size:12px">When off, this notification is never sent.</span></span></label>'
        . '<div class="field"><label>Recipient</label><input value="' . e($recipient) . '" disabled></div>'
        . '<div class="field"><label>Subject</label><input name="subject" value="' . e($t['subject']) . '"></div>'
        . '<div class="field"><label>Heading</label><input name="heading" value="' . e($t['heading']) . '"></div>'
        . '<div class="field" style="margin-bottom:0"><label>Body</label>'
        . '<p style="color:var(--muted);font-size:12px;margin:0 0 8px">Placeholders: <code style="font-family:var(--f-mono)">' . e($ph) . '</code> · <code>{items}</code> inserts the products table.</p>'
        . '<textarea name="body" class="richtext" rows="12">' . e($t['body']) . '</textarea></div>'
        . '</div><div style="margin-top:16px"><button class="btn btn-flame">Save changes ' . icon('check') . '</button></div></form>';

    $preview = '<div class="email-preview-pane">'
        . '<div class="email-preview-head"><span>Live preview</span><span style="color:var(--muted);font-weight:400">sample data · updates as you type</span></div>'
        . '<div class="email-preview-stage"><iframe id="email-preview" class="email-preview-frame" title="Email preview"></iframe></div></div>';

    $script = '<script>(function(){'
        . 'var f=document.getElementById("email-form"),fr=document.getElementById("email-preview");'
        . 'if(!f||!fr)return;var url=' . json_encode($previewUrl) . ',last="";'
        . 'function pl(){var h=f.querySelector("[name=heading]"),b=f.querySelector("[name=body]");return{h:h?h.value:"",b:b?b.value:""};}'
        . 'function go(){var p=pl(),k=p.h+"\\u0001"+p.b;if(k===last)return;last=k;'
        . 'var d=new FormData();d.append("heading",p.h);d.append("body",p.b);'
        . 'fetch(url,{method:"POST",body:d,credentials:"same-origin"}).then(function(r){return r.text();}).then(function(x){fr.srcdoc=x;}).catch(function(){});}'
        . 'go();setInterval(go,700);f.addEventListener("input",go);'
        . '})();</script>';

    $h = $head . $form . $preview . $script;
    respond_admin($h, ['title' => 'Manage email · Admin']);
}

function admin_email_preview(string $id): void
{
    $vars = email_sample_vars();
    header('Content-Type: text/html; charset=utf-8');
    header('X-Frame-Options: SAMEORIGIN');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // live render from the current (unsaved) editor values
        $heading = (string)($_POST['heading'] ?? '');
        $body = (string)($_POST['body'] ?? '');
        $vars += ['site' => SITE_NAME, 'phone_co' => CO_PHONE, 'email_co' => CO_EMAIL, 'address' => CO_ADDRESS];
        echo email_wrap(email_replace($heading, $vars), email_replace($body, $vars));
        return;
    }
    $r = render_email($id, $vars, true);
    echo $r ? $r['html'] : '<p style="font-family:sans-serif;padding:24px">Preview unavailable.</p>';
}

// ---------- Settings ----------
function admin_settings(): void
{
    $msg = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
        $section = $_POST['_section'] ?? '';
        if ($section === 'general') {
            $g = (array)setting('general', []);
            if (!empty($_FILES['logo']['name'])) { $u = save_upload($_FILES['logo']); if ($u) $g['logoUrl'] = $u; }
            if (!empty($_FILES['favicon']['name'])) { $u = save_upload($_FILES['favicon']); if ($u) $g['faviconUrl'] = $u; }
            if (!empty($_FILES['catalogue']['name'])) { $u = save_pdf($_FILES['catalogue']); if ($u) $g['catalogueUrl'] = $u; }
            if (!empty($_POST['remove_catalogue'])) $g['catalogueUrl'] = '';
            $g['logoWidth'] = max(0, (int)($_POST['logoWidth'] ?? 0));
            $g['logoHeight'] = max(0, (int)($_POST['logoHeight'] ?? 60));
            set_setting('general', $g);
        } elseif ($section === 'theme') {
            $hex = function ($v, $def) {
                $v = trim((string)$v);
                return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $def;
            };
            set_setting('theme', [
                'flame' => $hex($_POST['flame'] ?? '', '#F97316'),
                'flameDeep' => $hex($_POST['flameDeep'] ?? '', '#EA580C'),
                'ember' => $hex($_POST['ember'] ?? '', '#FBBF24'),
                'ink' => $hex($_POST['ink'] ?? '', '#17120F'),
            ]);
        } elseif ($section === 'toggles') {
            set_setting('toggles', [
                'sellingEnabled' => !empty($_POST['sellingEnabled']),
                'enquiryEnabled' => !empty($_POST['enquiryEnabled']),
                'guestCheckout' => !empty($_POST['guestCheckout']),
            ]);
        } elseif ($section === 'smtp') {
            $existing = (array)setting('smtp', []);
            $pwIn = (string)($_POST['password'] ?? '');
            $pw = $pwIn !== '' ? enc($pwIn) : (string)($existing['password'] ?? '');
            set_setting('smtp', [
                'host' => trim((string)($_POST['host'] ?? '')), 'port' => (int)($_POST['port'] ?? 587),
                'user' => trim((string)($_POST['user'] ?? '')), 'fromName' => trim((string)($_POST['fromName'] ?? '')),
                'fromEmail' => trim((string)($_POST['fromEmail'] ?? '')), 'password' => $pw,
            ]);
        } elseif ($section === 'featured') {
            set_setting('featured', [
                'limit' => max(1, min(40, (int)($_POST['limit'] ?? 8))),
                'carousel' => !empty($_POST['carousel']),
                'perView' => max(1, min(6, (int)($_POST['perView'] ?? 4))),
                'autoplay' => max(0, (int)($_POST['autoplay'] ?? 0)),
            ]);
        } elseif ($section === 'stripe') {
            $existing = (array)setting('stripe', []);
            $secIn = (string)($_POST['secretKey'] ?? '');
            $whIn = (string)($_POST['webhookSecret'] ?? '');
            set_setting('stripe', [
                'enabled' => !empty($_POST['enabled']),
                'publishableKey' => trim((string)($_POST['publishableKey'] ?? '')),
                'secretKey' => $secIn !== '' ? enc($secIn) : (string)($existing['secretKey'] ?? ''),
                'webhookSecret' => $whIn !== '' ? enc($whIn) : (string)($existing['webhookSecret'] ?? ''),
            ]);
        }
        $msg = '<p style="color:#1a7f43;margin:0 0 14px">Saved.</p>';
        log_event('system', "Settings saved: $section", 'info');
    }
    $g = (array)setting('general', []);
    $t = toggles();
    $th = theme();
    $smtp = (array)setting('smtp', []);
    $stripe = (array)setting('stripe', []);
    $fc = featured_cfg();
    $hasSmtpPass = !empty($smtp['password']);
    $hasStripeSecret = !empty($stripe['secretKey']);
    $hasStripeWh = !empty($stripe['webhookSecret']);
    $colorField = function (string $label, string $name, string $val): string {
        return '<div class="field"><label>' . e($label) . '</label>'
            . '<div style="display:flex;gap:8px;align-items:center">'
            . '<input type="color" name="' . $name . '" value="' . e($val) . '" style="width:48px;height:40px;padding:2px;border:1px solid var(--border);border-radius:8px;background:#fff" oninput="this.nextElementSibling.value=this.value">'
            . '<input type="text" value="' . e($val) . '" readonly style="flex:1;font-family:var(--f-mono);font-size:13px;padding:9px 11px;border:1px solid var(--border);border-radius:8px;background:#fff"></div></div>';
    };
    $cb = fn($k) => !empty($t[$k]) ? ' checked' : '';
    $h = '<div class="admin-topbar"><h1>Settings</h1></div><div style="display:grid;gap:22px;max-width:640px">' . $msg
        // Branding
        . '<form method="post" enctype="multipart/form-data" class="admin-card"><input type="hidden" name="_section" value="general">' . csrf_field()
        . '<h3 style="margin-bottom:4px">Branding</h3><p style="color:var(--muted);font-size:13px;margin-top:0">Logo (header) + favicon (browser tab).</p>'
        . '<div class="field-row"><div class="field"><label>Logo' . (!empty($g['logoUrl']) ? ' (saved)' : '') . '</label>' . (!empty($g['logoUrl']) ? '<div class="uploader-thumb" style="margin-bottom:8px"><img src="' . e($g['logoUrl']) . '"></div>' : '') . '<input type="file" name="logo" accept="image/*"></div>'
        . '<div class="field"><label>Favicon' . (!empty($g['faviconUrl']) ? ' (saved)' : '') . '</label>' . (!empty($g['faviconUrl']) ? '<div class="uploader-thumb" style="margin-bottom:8px"><img src="' . e($g['faviconUrl']) . '"></div>' : '') . '<input type="file" name="favicon" accept="image/*"></div></div>'
        . '<div class="field-row"><div class="field"><label>Logo width (px, 0 = auto)</label><input name="logoWidth" type="number" value="' . e((string)($g['logoWidth'] ?? 0)) . '"></div>'
        . '<div class="field"><label>Logo height (px)</label><input name="logoHeight" type="number" value="' . e((string)($g['logoHeight'] ?? 60)) . '"></div></div>'
        . '<p style="color:var(--muted);font-size:12px;margin:0 0 12px">Set width for a fixed size, or leave width 0 and use height (keeps aspect ratio).</p>'
        . '<div class="field" style="border-top:1px solid var(--border);padding-top:16px"><label>Catalogue PDF (header “Catalogue” button)</label>'
        . (!empty($g['catalogueUrl'])
            ? '<p style="margin:0 0 8px;font-size:13px"><a href="' . e($g['catalogueUrl']) . '" target="_blank" style="color:var(--flame-deep)">' . icon('sign') . ' View current catalogue</a></p><label class="checkbox-row" style="margin-bottom:8px;font-size:13px"><input type="checkbox" name="remove_catalogue" value="1"> remove current PDF</label>'
            : '<p style="color:var(--muted);font-size:12px;margin:0 0 8px">No PDF yet — the button links to the contact page until you upload one.</p>')
        . '<input type="file" name="catalogue" accept="application/pdf"><p style="color:var(--muted);font-size:12px;margin:6px 0 0">Max 30MB. Opens / downloads when visitors click “Catalogue” in the header.</p></div>'
        . '<button class="btn btn-flame">Save branding ' . icon('check') . '</button></form>'
        // Brand colours
        . '<form method="post" class="admin-card"><input type="hidden" name="_section" value="theme">' . csrf_field()
        . '<h3 style="margin-bottom:4px">Brand colours</h3><p style="color:var(--muted);font-size:13px;margin-top:0">Used across the whole site — buttons, links, accents, dark sections.</p>'
        . '<div class="field-row">' . $colorField('Primary (orange)', 'flame', $th['flame']) . $colorField('Primary dark', 'flameDeep', $th['flameDeep']) . '</div>'
        . '<div class="field-row">' . $colorField('Accent (amber)', 'ember', $th['ember']) . $colorField('Dark / ink', 'ink', $th['ink']) . '</div>'
        . '<button class="btn btn-flame">Save colours ' . icon('check') . '</button></form>'
        // Toggles
        . '<form method="post" class="admin-card"><input type="hidden" name="_section" value="toggles">' . csrf_field()
        . '<h3 style="margin-bottom:6px">Store mode</h3>'
        . '<label style="display:flex;gap:12px;align-items:flex-start;padding:10px 0"><input type="checkbox" name="sellingEnabled"' . $cb('sellingEnabled') . '><span><b>Enable selling</b><br><span style="color:var(--muted);font-size:13px">When off, the store runs in enquiry mode (“Enquire for price”). When on (with Stripe keys set in Payments below), customers can pay online.</span></span></label>'
        . '<label style="display:flex;gap:12px;align-items:flex-start;padding:10px 0"><input type="checkbox" name="enquiryEnabled"' . $cb('enquiryEnabled') . '><span><b>Enable enquiry mode</b></span></label>'
        . '<label style="display:flex;gap:12px;align-items:flex-start;padding:10px 0"><input type="checkbox" name="guestCheckout"' . $cb('guestCheckout') . '><span><b>Allow guest checkout</b></span></label>'
        . '<button class="btn btn-flame" style="margin-top:8px">Save store mode ' . icon('check') . '</button></form>'
        // Featured products (home)
        . '<form method="post" class="admin-card"><input type="hidden" name="_section" value="featured">' . csrf_field()
        . '<h3 style="margin-bottom:4px">Featured products (home)</h3><p style="color:var(--muted);font-size:13px;margin-top:0">The “Featured products” row on the homepage. Tick a product’s “Feature on homepage” box to include it. When there are more featured items than cards-per-row, it turns into a swipeable carousel with arrows.</p>'
        . '<div class="field-row"><div class="field"><label>How many to show</label><input name="limit" type="number" min="1" max="40" value="' . e((string)$fc['limit']) . '"></div>'
        . '<div class="field"><label>Cards per row</label><input name="perView" type="number" min="1" max="6" value="' . e((string)$fc['perView']) . '"></div></div>'
        . '<div class="field"><label>Autoplay (seconds, 0 = off)</label><input name="autoplay" type="number" min="0" value="' . e((string)$fc['autoplay']) . '" style="max-width:220px"></div>'
        . '<label style="display:flex;gap:12px;align-items:flex-start;padding:8px 0"><input type="checkbox" name="carousel"' . (!empty($fc['carousel']) ? ' checked' : '') . '><span><b>Enable carousel</b><br><span style="color:var(--muted);font-size:13px">When featured items exceed cards-per-row, show arrows + swipe instead of wrapping to a second line.</span></span></label>'
        . '<button class="btn btn-flame">Save featured ' . icon('check') . '</button></form>'
        // SMTP — Gmail recommended
        . '<form method="post" class="admin-card"><input type="hidden" name="_section" value="smtp">' . csrf_field()
        . '<h3 style="margin-bottom:4px">Email (SMTP)</h3><p style="color:var(--muted);font-size:13px;margin-top:0">When set, enquiry/contact emails send via this SMTP account. <b>Gmail:</b> host <code>smtp.gmail.com</code>, port <code>587</code>, username = your Gmail, password = a <b>16-char App Password</b> (not your normal password). Leave blank to use the server\'s mail().</p>'
        . '<div class="field-row"><div class="field"><label>SMTP host</label><input name="host" value="' . e($smtp['host'] ?? '') . '" placeholder="smtp.gmail.com"></div><div class="field"><label>Port</label><input name="port" type="number" value="' . e((string)($smtp['port'] ?? 587)) . '"></div></div>'
        . '<div class="field"><label>Username (email)</label><input name="user" value="' . e($smtp['user'] ?? '') . '" placeholder="you@gmail.com"></div>'
        . '<div class="field"><label>App password' . ($hasSmtpPass ? ' (saved — leave blank to keep)' : '') . '</label><input name="password" type="password" placeholder="' . ($hasSmtpPass ? '••••••••' : 'abcd efgh ijkl mnop') . '"></div>'
        . '<div class="field-row"><div class="field"><label>From name</label><input name="fromName" value="' . e($smtp['fromName'] ?? SITE_NAME) . '"></div><div class="field"><label>From email</label><input name="fromEmail" value="' . e($smtp['fromEmail'] ?? CO_EMAIL) . '"></div></div>'
        . '<button class="btn btn-flame">Save email settings ' . icon('check') . '</button></form>'
        // Stripe payments
        . '<form method="post" class="admin-card"><input type="hidden" name="_section" value="stripe">' . csrf_field()
        . '<h3 style="margin-bottom:4px">Payments (Stripe)</h3><p style="color:var(--muted);font-size:13px;margin-top:0">Secret keys stored encrypted. Enable only with valid keys AND "Enable selling" on. Webhook URL: <code>' . e(rtrim(SITE_URL, '/')) . url('stripe/webhook') . '</code></p>'
        . '<label style="display:flex;gap:12px;align-items:flex-start;padding:8px 0"><input type="checkbox" name="enabled"' . (!empty($stripe['enabled']) ? ' checked' : '') . '><span><b>Enable Stripe checkout</b><br><span style="color:var(--muted);font-size:13px">Customers pay online instead of enquiring.</span></span></label>'
        . '<div class="field"><label>Publishable key</label><input name="publishableKey" value="' . e($stripe['publishableKey'] ?? '') . '" placeholder="pk_live_…"></div>'
        . '<div class="field"><label>Secret key' . ($hasStripeSecret ? ' (saved — blank keeps)' : '') . '</label><input name="secretKey" type="password" placeholder="' . ($hasStripeSecret ? '••••••••' : 'sk_live_…') . '"></div>'
        . '<div class="field"><label>Webhook signing secret' . ($hasStripeWh ? ' (saved — blank keeps)' : '') . '</label><input name="webhookSecret" type="password" placeholder="' . ($hasStripeWh ? '••••••••' : 'whsec_…') . '"></div>'
        . '<button class="btn btn-flame">Save payment settings ' . icon('check') . '</button></form>'
        . '</div>';
    respond_admin($h, ['title' => 'Settings · Admin']);
}

// ---------- Logs ----------
function admin_logs(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check() && ($_POST['_action'] ?? '') === 'clear') {
        q_exec('DELETE FROM logs');
        redirect('admin/logs');
    }
    $level = $_GET['level'] ?? '';
    $category = $_GET['category'] ?? '';
    $where = []; $params = [];
    if (in_array($level, ['info', 'warn', 'error'], true)) { $where[] = 'level=?'; $params[] = $level; }
    if ($category) { $where[] = 'category=?'; $params[] = $category; }
    $sql = 'SELECT * FROM logs' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC LIMIT 300';
    $rows = q_all($sql, $params);
    $lvlBadge = ['info' => 'published', 'warn' => 'new', 'error' => 'closed'];
    $body = '';
    foreach ($rows as $l) {
        $meta = $l['meta'] ? '<span style="display:block;font-family:var(--f-mono);font-size:11px;color:var(--muted);margin-top:2px">' . e($l['meta']) . '</span>' : '';
        $body .= '<tr><td><span class="badge ' . ($lvlBadge[$l['level']] ?? 'draft') . '">' . e($l['level']) . '</span></td><td style="font-family:var(--f-mono);font-size:12px">' . e($l['category']) . '</td><td>' . e($l['message']) . $meta . '</td><td style="font-size:12px;color:var(--muted)">' . e($l['created_at']) . '</td></tr>';
    }
    $opt = function ($name, $cur, $vals) {
        $h = '';
        foreach ($vals as $v => $lbl) $h .= '<option value="' . e($v) . '"' . ($cur === $v ? ' selected' : '') . '>' . e($lbl) . '</option>';
        return $h;
    };
    $cats = ['' => 'All categories', 'auth' => 'auth', 'enquiry' => 'enquiry', 'contact' => 'contact', 'email' => 'email', 'system' => 'system'];
    $h = '<div class="admin-topbar"><h1>Event logs</h1></div>'
        . '<form method="get" style="display:flex;gap:10px;margin-bottom:16px;align-items:center;flex-wrap:wrap">'
        . '<select name="level" onchange="this.form.submit()" style="padding:8px 12px;border-radius:9px;border:1px solid var(--border)">' . $opt('level', $level, ['' => 'All levels', 'info' => 'INFO', 'warn' => 'WARN', 'error' => 'ERROR']) . '</select>'
        . '<select name="category" onchange="this.form.submit()" style="padding:8px 12px;border-radius:9px;border:1px solid var(--border)">' . $opt('category', $category, $cats) . '</select>'
        . '<a href="' . url('admin/logs') . '" class="btn btn-ghost" style="padding:8px 12px;font-size:13px">Reset</a>'
        . '<span style="margin-left:auto;color:var(--muted);font-size:13px">' . count($rows) . ' events</span></form>'
        . '<form method="post" style="margin-bottom:14px">' . csrf_field() . '<input type="hidden" name="_action" value="clear"><button class="btn btn-danger" style="padding:8px 12px;font-size:13px" onclick="return confirm(\'Clear all logs?\')">' . icon('trash') . ' Clear</button></form>'
        . ($rows ? '<table class="admin-table"><thead><tr><th style="width:90px">Level</th><th style="width:110px">Category</th><th>Message</th><th style="width:150px">Time</th></tr></thead><tbody>' . $body . '</tbody></table>' : '<div class="admin-card">No events yet.</div>');
    respond_admin($h, ['title' => 'Logs · Admin']);
}
