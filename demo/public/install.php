<?php
// ONE-TIME DB installer. Visit /install.php?key=fsa-setup-9271 then DELETE this file.
declare(strict_types=1);
if (($_GET['key'] ?? '') !== 'fsa-setup-9271') { http_response_code(403); exit('Forbidden'); }
header('Content-Type: text/plain; charset=utf-8');

$app = is_dir(__DIR__ . '/../app') ? __DIR__ . '/../app' : __DIR__ . '/app';
$local = $app . '/config.local.php';
if (is_file($local)) require $local;
require $app . '/config.php';

echo "Connecting to " . DB_NAME . " @ " . DB_HOST . " ...\n";
$mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
if ($mysqli->connect_errno) { echo "CONNECT FAILED: " . $mysqli->connect_error . "\n"; exit; }
$mysqli->set_charset('utf8mb4');

// Guard: don't clobber an already-installed DB.
$res = $mysqli->query("SHOW TABLES LIKE 'products'");
if ($res && $res->num_rows > 0 && empty($_GET['force'])) {
    $c = $mysqli->query('SELECT COUNT(*) n FROM products')->fetch_assoc();
    echo "Already installed (products table exists, {$c['n']} rows). Add &force=1 to re-run.\n";
    exit;
}

$sqlFile = is_file($app . '/../sql/install.sql') ? $app . '/../sql/install.sql' : __DIR__ . '/sql/install.sql';
if (!is_file($sqlFile)) { echo "install.sql not found at $sqlFile\n"; exit; }
$sql = file_get_contents($sqlFile);
echo "Running install.sql (" . strlen($sql) . " bytes)...\n";

if ($mysqli->multi_query($sql)) {
    do { if ($r = $mysqli->store_result()) $r->free(); } while ($mysqli->more_results() && $mysqli->next_result());
}
if ($mysqli->errno) { echo "SQL ERROR: " . $mysqli->error . "\n"; }

foreach (['categories', 'products', 'product_variants', 'blog_posts', 'users', 'settings'] as $t) {
    $row = $mysqli->query("SELECT COUNT(*) n FROM `$t`");
    echo str_pad($t, 18) . ($row ? $row->fetch_assoc()['n'] : '—') . "\n";
}
echo "\nDONE. Now DELETE install.php from the server.\n";
