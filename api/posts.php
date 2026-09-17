<?php
header('Content-Type: application/json');
if (!defined('TRENDYPOST')) { define('TRENDYPOST', true); require_once __DIR__.'/../config/config.php'; require_once INCLUDES_PATH.'Database.php'; require_once INCLUDES_PATH.'Helpers.php'; require_once INCLUDES_PATH.'Models.php'; }
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = min(50, intval($_GET['per_page'] ?? 10));
$posts = Post::getList([], $page, $perPage);
echo json_encode(['success'=>true,'posts'=>$posts]);
?>