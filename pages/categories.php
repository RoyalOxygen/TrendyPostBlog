<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$categories = Category::getAll();
$pageTitle = 'All Categories | TrendyPost';
$seoMeta = meta_tags($pageTitle, 'Browse all article categories on TrendyPost.');
include INCLUDES_PATH . 'Header.php';
?>
<section class="page-header"><div class="container"><h1><i class="bi bi-grid-3x3-gap"></i> All Categories</h1></div></section>
<main class="main-content py-5"><div class="container"><div class="row">
<?php foreach($categories as $cat): ?>
<div class="col-md-4 col-lg-3 mb-4">
<a href="<?php echo category_url($cat['slug']); ?>" class="card text-decoration-none h-100 border-0 shadow-sm hover-shadow">
<div class="card-body text-center p-4"><i class="bi bi-folder fs-1" style="color:<?php echo $cat['color']??'#00C853'; ?>"></i>
<h5 class="mt-3 mb-1"><?php echo $cat['name']; ?></h5><small class="text-muted"><?php echo $cat['post_count']??0; ?> articles</small></div>
</a>
</div>
<?php endforeach; ?></div></div></main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>