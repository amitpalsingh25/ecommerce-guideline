<?php
declare(strict_types=1);

// app/ may sit beside public/ (secure) or inside the web root (simple deploy).
$APP = is_dir(__DIR__ . '/../app') ? __DIR__ . '/../app' : __DIR__ . '/app';
require $APP . '/bootstrap.php';

$path = current_path();
$seg = array_values(array_filter(explode('/', $path), fn($s) => $s !== ''));

try {
    dispatch($path, $seg, $APP);
} catch (Throwable $ex) {
    error_log('[fsa] ' . $ex->getMessage());
    http_response_code(503);
    echo '<!doctype html><html lang="en-AU"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Fire Safe Australia</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#17120f;color:#efe7df;font-family:system-ui,sans-serif;text-align:center;padding:24px}'
        . 'h1{font-size:clamp(28px,6vw,52px);margin:0 0 12px}span{color:#ea580c}p{color:#cdbfb4;max-width:42ch;margin:0 auto;line-height:1.6}</style></head>'
        . '<body><div><h1>Fire<span>Safe</span>Australia</h1><p>Our site is being updated — please check back shortly. For enquiries call 0449 794 559 or email info@firesafeaustralia.com.au.</p></div></body></html>';
}
exit;

function dispatch(string $path, array $seg, string $APP): void
{
    // ---- Admin area ----
    if (($seg[0] ?? '') === 'admin') {
        require $APP . '/admin.php';
        admin_dispatch(array_slice($seg, 1));
        return;
    }

    // ---- POST endpoints ----
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($path === '/stripe/checkout' || $path === '/stripe/webhook') {
            require $APP . '/stripe.php';
            if ($path === '/stripe/checkout') { handle_stripe_checkout(); return; }
            handle_stripe_webhook(); return;
        }
        require $APP . '/forms.php';
        if ($path === '/enquiry') { handle_enquiry(); return; }
        if ($path === '/contact') { handle_contact(); return; }
    }

    // ---- Public routes ----
    require $APP . '/pages.php';
    require $APP . '/customer.php';

    switch (true) {
        case $path === '/':                                 page_home(); break;
        case $path === '/register':                         page_register(); break;
        case $path === '/login':                            page_login(); break;
        case $path === '/logout':                           page_logout(); break;
        case $path === '/forgot':                           page_forgot(); break;
        case $path === '/reset':                            page_reset(); break;
        case $path === '/account':                          page_account(); break;
        case $path === '/account/profile':                  page_account_profile(); break;
        case $path === '/products':                         page_products(); break;
        case $seg[0] === 'products' && isset($seg[1]):      page_product($seg[1]); break;
        case $path === '/categories':                       page_categories(); break;
        case $seg[0] === 'category' && isset($seg[1]):      page_category(array_slice($seg, 1)); break;
        case $path === '/blog':                             page_blog(); break;
        case $seg[0] === 'blog' && isset($seg[1]):          page_post($seg[1]); break;
        case $path === '/about':                            page_about(); break;
        case $path === '/contact':                          page_contact(); break;
        case $path === '/cart':                             page_cart(); break;
        case $path === '/checkout':                         page_checkout(); break;
        case $path === '/sitemap.xml':                      page_sitemap(); break;
        default:                                            http_response_code(404); page_not_found();
    }
}
