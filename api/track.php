<?php
header('Content-Type: application/json');
if (!defined('TRENDYPOST')) { define('TRENDYPOST', true); require_once __DIR__.'/../config/config.php'; require_once INCLUDES_PATH.'Database.php'; require_once INCLUDES_PATH.'Helpers.php'; }
$adId = intval($_GET['id'] ?? 0);
if (!$adId) { echo json_encode(['success'=>false]); exit; }
$type = str_contains($_SERVER['REQUEST_URI'], 'track-click') ? 'click' : 'impression';
if ($type === 'click') {
    Database::query("UPDATE advertisements SET click_count = click_count + 1 WHERE id = ?", [$adId]);
    $today = date('Y-m-d');
    $exists = Database::fetchOne("SELECT id FROM ad_statistics WHERE ad_id = ? AND date = ?", [$adId, $today]);
    if ($exists) { Database::query("UPDATE ad_statistics SET clicks = clicks + 1 WHERE ad_id = ? AND date = ?", [$adId, $today]); }
    else { Database::insert('ad_statistics', ['ad_id'=>$adId,'date'=>$today,'clicks'=>1]); }
} else {
    Database::query("UPDATE advertisements SET impression_count = impression_count + 1 WHERE id = ?", [$adId]);
}
echo json_encode(['success'=>true]);
?>