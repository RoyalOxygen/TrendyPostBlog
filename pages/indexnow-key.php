<?php
if (!defined('TRENDYPOST')) {
    die('Direct access not permitted.');
}
$key = trim((string) SEOSetting::get('indexing_api_key', ''));
if ($key === '' || strlen($key) < 8 || strpos($key, '{') === 0) {
    $key = substr(hash('sha256', ENCRYPTION_KEY . 'indexnow'), 0, 32);
}
$key = preg_replace('/[^a-zA-Z0-9\-]/', '', $key);
$requested = basename((string) ($_GET['key'] ?? ''));
$requested = preg_replace('/\.txt$/i', '', $requested);
if ($key === '' || $requested === '' || !hash_equals($key, $requested)) {
    http_response_code(404);
    echo 'Not found';
    exit;
}
header('Content-Type: text/plain; charset=UTF-8');
echo $key;
