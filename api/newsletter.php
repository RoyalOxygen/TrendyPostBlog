<?php
if (!defined('TRENDYPOST')) {
    define('TRENDYPOST', true);
    require_once __DIR__ . '/../config/config.php';
    require_once INCLUDES_PATH . 'Database.php';
    require_once INCLUDES_PATH . 'Helpers.php';
    require_once INCLUDES_PATH . 'Models.php';
    require_once INCLUDES_PATH . 'Mailer.php';
}

ini_set('display_errors', '0');
if (ob_get_level() === 0) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$email = strtolower(trim((string) ($input['email'] ?? '')));
if (!is_valid_email($email)) {
    json_response(['success' => false, 'message' => 'Please enter a valid email address.']);
}

try {
    $result = NewsletterModel::subscribe($email);
    json_response($result, !empty($result['success']) ? 200 : 400);
} catch (Throwable $e) {
    error_log('Newsletter subscribe error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Could not subscribe. Please try again.'], 500);
}
