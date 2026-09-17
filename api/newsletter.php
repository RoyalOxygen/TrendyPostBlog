<?php
header('Content-Type: application/json');
if (!defined('TRENDYPOST')) { define('TRENDYPOST', true); require_once __DIR__.'/../config/config.php'; require_once INCLUDES_PATH.'Database.php'; require_once INCLUDES_PATH.'Helpers.php'; require_once INCLUDES_PATH.'Models.php'; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }
$input = json_decode(file_get_contents('php://input'), true);
$email = sanitize($input['email'] ?? '');
if (!is_valid_email($email)) { echo json_encode(['success'=>false,'message'=>'Invalid email']); exit; }
$result = NewsletterModel::subscribe($email);
echo json_encode($result);
?>