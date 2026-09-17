<?php
header('Content-Type: application/json');
if (!defined('TRENDYPOST')) { define('TRENDYPOST', true); require_once __DIR__.'/../config/config.php'; require_once INCLUDES_PATH.'Database.php'; require_once INCLUDES_PATH.'Helpers.php'; require_once INCLUDES_PATH.'Models.php'; }
$query = trim($_GET['q'] ?? '');
if (strlen($query) < 2) { echo json_encode(['success'=>false,'results'=>[]]); exit; }
$posts = Post::getList(['search'=>$query], 1, 8);
$results = [];
foreach ($posts as $p) { $results[] = ['id'=>$p['id'],'title'=>$p['title'],'slug'=>$p['slug'],'url'=>post_url($p['slug']),'thumbnail'=>$p['featured_image']?upload_url($p['featured_image']):null,'category'=>$p['category_name']??'']; }
echo json_encode(['success'=>true,'results'=>$results]);
?>