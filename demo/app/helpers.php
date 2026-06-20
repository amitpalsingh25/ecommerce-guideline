<?php
declare(strict_types=1);

/** HTML-escape. */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Build a site URL honouring BASE_PATH. */
function url(string $path = ''): string
{
    if ($path === '' || $path === '/') return BASE_PATH . '/';
    return BASE_PATH . '/' . ltrim($path, '/');
}

/** Absolute URL (for emails/sitemap). */
function abs_url(string $path = ''): string
{
    return rtrim(SITE_URL, '/') . url($path);
}

function slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim((string)$s, '-');
    return substr($s, 0, 80);
}

/** Format AUD price, or null. */
function money($v): ?string
{
    if ($v === null || $v === '') return null;
    return '$' . number_format((float)$v, 2);
}

function fmt_date(?string $d): string
{
    if (!$d) return '';
    $t = strtotime($d);
    return $t ? date('j M Y', $t) : '';
}

/** Decode a JSON column to array (safe). */
function json_arr(?string $s): array
{
    if (!$s) return [];
    $v = json_decode($s, true);
    return is_array($v) ? $v : [];
}

/** Redirect helper. */
function redirect(string $path): void
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

/** Current request path (without BASE_PATH / query). */
function current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (BASE_PATH && str_starts_with($uri, BASE_PATH)) {
        $uri = substr($uri, strlen(BASE_PATH));
    }
    return '/' . ltrim($uri, '/');
}

/** Render a view file with $data extracted, return HTML string. */
function view(string $name, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . '/views/' . $name . '.php';
    return (string)ob_get_clean();
}

/** Render a public page inside the site layout and echo it. */
function render(string $name, array $data = [], array $layoutData = []): void
{
    $content = view($name, $data);
    echo view('layout', array_merge($layoutData, ['content' => $content]));
}

/** Echo raw HTML content wrapped in the public layout. */
function respond(string $content, array $opts = []): void
{
    echo view('layout', array_merge($opts, ['content' => $content]));
}

/** Render an admin page inside the admin layout. */
function render_admin(string $name, array $data = [], array $layoutData = []): void
{
    $content = view('admin/' . $name, $data);
    echo view('admin/layout', array_merge($layoutData, ['content' => $content]));
}

/** Echo raw HTML content wrapped in the admin layout. */
function respond_admin(string $content, array $opts = []): void
{
    echo view('admin/layout', array_merge($opts, ['content' => $content]));
}

/** Save an uploaded file ($_FILES entry) to UPLOAD_DIR; returns public URL or null. */
function save_upload(array $file): ?string
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > 8 * 1024 * 1024) return null;
    $allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/webp' => '.webp', 'image/gif' => '.gif', 'image/avif' => '.avif', 'image/x-icon' => '.ico', 'image/vnd.microsoft.icon' => '.ico'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!isset($allowed[$mime])) return null;
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);
    $name = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) return null;
    return UPLOAD_URL . '/' . $name;
}

/** Save an uploaded PDF to UPLOAD_DIR; returns public URL or null. */
function save_pdf(array $file): ?string
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > 30 * 1024 * 1024) return null;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if ($mime !== 'application/pdf') return null;
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);
    $name = 'catalogue-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) return null;
    return UPLOAD_URL . '/' . $name;
}

/** CSRF token for forms. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): bool
{
    return isset($_POST['_csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf']);
}

/** AES-256-GCM encrypt for stored secrets. Format v1:iv:tag:cipher (base64). */
function enc(string $plain): string
{
    if ($plain === '') return '';
    $key = hex2bin(APP_SECRET);
    $iv = random_bytes(12);
    $tag = '';
    $ct = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return 'v1:' . base64_encode($iv) . ':' . base64_encode($tag) . ':' . base64_encode($ct);
}

function dec(string $blob): string
{
    if ($blob === '' || strpos($blob, 'v1:') !== 0) return $blob; // plaintext / empty
    [, $iv, $tag, $ct] = explode(':', $blob, 4);
    $key = hex2bin(APP_SECRET);
    $out = openssl_decrypt(base64_decode($ct), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, base64_decode($iv), base64_decode($tag));
    return $out === false ? '' : $out;
}

function is_encrypted(string $v): bool
{
    return strpos($v, 'v1:') === 0;
}

/** SMTP settings (password decrypted), or null if not configured. */
function smtp_settings(): ?array
{
    $s = (array)setting('smtp', []);
    if (empty($s['host']) || empty($s['user'])) return null;
    return [
        'host' => (string)$s['host'],
        'port' => (int)($s['port'] ?? 587),
        'user' => (string)$s['user'],
        'pass' => dec((string)($s['password'] ?? '')),
        'fromName' => (string)($s['fromName'] ?? SITE_NAME),
        'fromEmail' => (string)($s['fromEmail'] ?? $s['user']),
    ];
}

/** Stripe settings (secret decrypted). */
function stripe_settings(): array
{
    $s = (array)setting('stripe', []);
    return [
        'enabled' => !empty($s['enabled']),
        'publishableKey' => (string)($s['publishableKey'] ?? ''),
        'secretKey' => dec((string)($s['secretKey'] ?? '')),
        'webhookSecret' => dec((string)($s['webhookSecret'] ?? '')),
    ];
}

/** True when real online payment is available. */
function payments_enabled(): bool
{
    $t = toggles();
    $st = stripe_settings();
    return !empty($t['sellingEnabled']) && $st['enabled'] && $st['secretKey'] !== '';
}

/** Read a setting (JSON-decoded) with default. */
function setting(string $key, $default = null)
{
    $v = q_val('SELECT value FROM settings WHERE `key` = ?', [$key]);
    if ($v === false || $v === null) return $default;
    $decoded = json_decode((string)$v, true);
    return $decoded === null ? $default : $decoded;
}

function set_setting(string $key, $value): void
{
    q_exec(
        'INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)',
        [$key, json_encode($value)]
    );
}

/** Store toggles with defaults (selling off, enquiry on). */
function toggles(): array
{
    return array_merge(
        ['sellingEnabled' => false, 'enquiryEnabled' => true, 'guestCheckout' => true],
        (array)setting('toggles', [])
    );
}

function branding(): array
{
    return array_merge(
        ['logoUrl' => url('assets/logo.png'), 'faviconUrl' => url('assets/favicon.ico'), 'logoWidth' => 0, 'logoHeight' => 60],
        (array)setting('general', [])
    );
}

/** Inline style for the header logo from branding settings. */
function logo_style(array $b): string
{
    $w = (int)($b['logoWidth'] ?? 0);
    $hh = (int)($b['logoHeight'] ?? 60);
    $style = 'max-width:100%;object-fit:contain;';
    if ($w > 0) {
        $style .= 'width:' . $w . 'px;height:' . ($hh > 0 ? $hh . 'px' : 'auto') . ';';
    } else {
        $style .= 'height:' . ($hh > 0 ? $hh : 60) . 'px;width:auto;';
    }
    return $style;
}

/** Hero slideshow config (with sensible defaults + one default slide). */
function hero(): array
{
    $h = (array)setting('hero', []);
    $defaults = [
        'mode' => 'slideshow',     // slideshow | image
        'image' => '',             // single-image mode source
        'height' => 620,
        'padTop' => 96,            // px
        'padBottom' => 104,        // px
        'titleSize' => 84,         // px (max)
        'subSize' => 20,           // px
        'eyebrowSize' => 12,       // px
        'transition' => 'fade',   // fade | slide
        'speed' => 600,            // ms
        'interval' => 6,           // seconds autoplay (0 = off)
        'slides' => [],
    ];
    $h = array_merge($defaults, $h);
    if (empty($h['slides']) || !is_array($h['slides'])) {
        $h['slides'] = [[
            'image' => '', 'eyebrow' => 'Fire protection equipment · Australia-wide',
            'heading' => 'Fire safety equipment, built to the', 'accent' => 'standard.',
            'sub' => 'Extinguishers, hose reels, hydrants, signage and fittings — compliant gear for commercial sites, trades and facilities, dispatched right across Australia.',
            'ctaText' => 'Browse the catalogue', 'ctaLink' => url('products'),
            'cta2Text' => 'Request a quote', 'cta2Link' => url('contact'),
            'align' => 'center',
        ]];
    }
    return $h;
}

/** Homepage featured-products config. */
function featured_cfg(): array
{
    $f = (array)setting('featured', []);
    return array_merge(
        ['limit' => 8, 'carousel' => true, 'perView' => 4, 'autoplay' => 0],
        $f
    );
}

/** Editable brand colours (override style.css :root vars). */
function theme(): array
{
    $saved = (array)setting('theme', []);
    $saved = array_filter($saved, fn($v) => is_string($v) && preg_match('/^#[0-9a-fA-F]{3,8}$/', $v));
    return array_merge(
        ['flame' => '#F97316', 'flameDeep' => '#EA580C', 'ember' => '#FBBF24', 'ink' => '#17120F'],
        $saved
    );
}

/** <style> tag overriding the brand CSS variables (inject after the stylesheet). */
function theme_style(): string
{
    $t = theme();
    return '<style>:root{--flame:' . e($t['flame']) . ';--flame-deep:' . e($t['flameDeep'])
        . ';--ember:' . e($t['ember']) . ';--ink:' . e($t['ink']) . ';}</style>';
}

/** Inline SVG icon by name (matches the design set). */
function icon(string $name, string $class = 'icon', string $style = ''): string
{
    static $stroke = [
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/>',
        'cart' => '<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.4 12h11l2-8H6"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'trash' => '<path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/>',
        'pin' => '<path d="M12 21s-7-5.7-7-11a7 7 0 0 1 14 0c0 5.3-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'shield-check' => '<path d="M12 3l8 3v5c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6z"/><path d="m9 12 2 2 4-4"/>',
        'truck' => '<path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="18" cy="18" r="1.6"/>',
        'headset' => '<path d="M4 13v-1a8 8 0 0 1 16 0v1"/><rect x="3" y="13" width="4" height="6" rx="1.5"/><rect x="17" y="13" width="4" height="6" rx="1.5"/><path d="M20 19a4 4 0 0 1-4 3h-3"/>',
        'gem' => '<path d="M3 11 11 3l10 10-8 8z"/><circle cx="8" cy="8" r="1.4"/>',
        'clock' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/>',
        'extinguisher' => '<rect x="8" y="7" width="8" height="14" rx="3"/><path d="M12 7V4h3M9 4h3"/><path d="M8 11h8"/>',
        'hose-reel' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/><path d="M20 12h2"/>',
        'valve' => '<circle cx="12" cy="8" r="3"/><path d="M12 11v7M8 20h8"/><path d="m9.5 6-2.5-3M14.5 6 17 3"/>',
        'cabinet' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M12 3v18"/><path d="M9.5 12h.01M14.5 12h.01"/>',
        'sign' => '<rect x="4" y="4" width="16" height="11" rx="2"/><path d="M12 15v5M9 20h6"/><path d="M12 7v3M12 12h.01"/>',
        'fittings' => '<path d="M4 20v-7a5 5 0 0 1 5-5h7"/><path d="M2 20h4M14 6v4"/><circle cx="18" cy="8" r="2"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m4 18 5-5 4 4 3-3 4 4"/>',
    ];
    if ($name === 'flame') {
        return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="currentColor"' . ($style ? ' style="' . e($style) . '"' : '') . '><path d="M12 2c1.4 3.8 5 5.3 5 9.5a5 5 0 0 1-10 0c0-2 .8-3.3 1.8-4.4C8 9.6 9 7.6 12 2z"/></svg>';
    }
    $body = $stroke[$name] ?? $stroke['extinguisher'];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24"' . ($style ? ' style="' . e($style) . '"' : '') . '>' . $body . '</svg>';
}

/** Event log (best-effort). */
function log_event(string $category, string $message, string $level = 'info', array $meta = []): void
{
    try {
        q_exec(
            'INSERT INTO logs (level, category, message, meta) VALUES (?, ?, ?, ?)',
            [$level, $category, $message, $meta ? json_encode($meta) : null]
        );
    } catch (Throwable $e) {
        error_log('[log] ' . $e->getMessage());
    }
}
