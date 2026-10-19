<?php
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$blocked = ['/config/', '/database/', '/includes/', '/logs/', '/cache/', '/cron/'];
foreach ($blocked as $prefix) {
    if ($uri === rtrim($prefix, '/') || str_starts_with($uri, $prefix)) {
        http_response_code(403);
        exit;
    }
}
$file = __DIR__ . $uri;

if ($uri !== '/' && is_file($file)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
