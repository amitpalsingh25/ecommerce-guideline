<?php
// Fire Safe Australia — config. Local defaults here; override on the server
// by creating app/config.local.php (NOT committed/deployed publicly).

declare(strict_types=1);

// ---- Database (local dev defaults) ----
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', '3306');
if (!defined('DB_NAME')) define('DB_NAME', 'firesafe_php');
if (!defined('DB_USER')) define('DB_USER', 'firesafe');
if (!defined('DB_PASS')) define('DB_PASS', 'change_me');

// ---- Site ----
if (!defined('SITE_NAME')) define('SITE_NAME', 'Fire Safe Australia');
if (!defined('SITE_URL'))  define('SITE_URL', '');            // e.g. https://firesafeaustralia.com.au ; '' = relative
if (!defined('BASE_PATH')) define('BASE_PATH', '');           // set to '/subdir' if not at domain root
if (!defined('ADMIN_EMAIL')) define('ADMIN_EMAIL', 'info@firesafeaustralia.com.au');

// Key for encrypting stored secrets (SMTP password, Stripe secret). 64-hex.
// DO NOT hard-code the real key here. Define it in app/config.local.php (gitignored).
// Generate one: php -r "echo bin2hex(random_bytes(32));"
// IMPORTANT: keep it STABLE across deploys or previously-saved secrets won't decrypt.
if (!defined('APP_SECRET')) define('APP_SECRET', 'SET_IN_CONFIG_LOCAL_64_HEX');

// Company contact (matches the brand)
if (!defined('CO_PHONE')) define('CO_PHONE', '0449 794 559');
if (!defined('CO_PHONE_RAW')) define('CO_PHONE_RAW', '0449794559');
if (!defined('CO_EMAIL')) define('CO_EMAIL', 'info@firesafeaustralia.com.au');
if (!defined('CO_ADDRESS')) define('CO_ADDRESS', '795 Thompson Road, Lyndhurst');

// Server overrides (GoDaddy): create app/config.local.php returning nothing,
// just defining the DB_* (and SITE_URL) constants before the defaults above.
// We load it FIRST so its define()s win.

// uploads dir (filesystem) + public URL path.
// Tie the dir to the web root (DOCUMENT_ROOT) so it matches UPLOAD_URL (/uploads)
// regardless of deploy layout. Falls back to public/ for CLI/dev.
if (!defined('UPLOAD_DIR')) {
    $__docroot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    define('UPLOAD_DIR', ($__docroot !== '' ? $__docroot : __DIR__ . '/../public') . '/uploads');
}
if (!defined('UPLOAD_URL')) define('UPLOAD_URL', BASE_PATH . '/uploads');
