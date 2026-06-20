<?php
// Production/server overrides. Copy to app/config.local.php on the server and fill in.
// Loaded BEFORE config.php, so these win. This file (config.local.php) must be gitignored.

define('DB_HOST', 'localhost');                  // shared hosting is usually 'localhost'
define('DB_PORT', '3306');
define('DB_NAME', 'your_db_name');               // exact DB name from the host
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

define('SITE_URL', 'https://example.com');
define('BASE_PATH', '');                         // '' if at domain root, else '/subdir'
define('ADMIN_EMAIL', 'admin@example.com');

// Encryption key for stored secrets (SMTP password, Stripe secret). 64 hex chars.
// Generate: php -r "echo bin2hex(random_bytes(32));"
// IMPORTANT: keep this STABLE across deploys, or saved SMTP/Stripe secrets won't decrypt.
define('APP_SECRET', 'PUT_YOUR_64_HEX_KEY_HERE');
