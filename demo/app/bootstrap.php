<?php
declare(strict_types=1);

// Polyfills (in case the host runs PHP 7.4; match{} still needs PHP 8).
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $h, string $n): bool { return $n === '' || strncmp($h, $n, strlen($n)) === 0; }
}
if (!function_exists('str_contains')) {
    function str_contains(string $h, string $n): bool { return $n === '' || strpos($h, $n) !== false; }
}

session_start();

// Load server overrides FIRST (so their define()s win over config.php defaults).
$local = __DIR__ . '/config.local.php';
if (is_file($local)) require $local;

require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/repo.php';
require __DIR__ . '/emails.php';
