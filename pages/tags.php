<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$tags = Tag::getAll(true);
$pageTitle = 'Tags | ' . Setting::get('site_name', 'TrendyPost');
$seoMeta = meta_tags($pageTitle, 'Browse all tags on ' . Setting::get('site_name', 'TrendyPost'));
include INCLUDES_PATH . 'Header.php';
?>
<section class="page-header"><div class="container"><h1>Tags</h1></div></section>
<main class="main-content py-5"><div class="container">
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($tags as $t): ?>
        <a class="btn btn-outline-success btn-sm" href="<?php echo tag_url($t['slug']); ?>"><?php echo esc($t['name']); ?></a>
        <?php endforeach; ?>
    </div>
</div></main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>
