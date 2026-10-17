<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$categories = Category::getAll();
$siteName = (string) Setting::get('site_name', 'TrendyPost');
$pageTitle = seo_page_title('All Categories', $siteName);
$pageDescription = 'Browse every news category on ' . $siteName . ' and find the stories you care about.';
$canonicalUrl = Seo::absolute(url('categories'));
$seo = Seo::build([
    'title' => $pageTitle,
    'description' => $pageDescription,
    'canonical' => $canonicalUrl,
    'schema_type' => 'CollectionPage',
    'breadcrumbs' => [['Home', Seo::home()], ['Categories', url('categories')]],
    'related' => array_map(static fn($cat) => [
        'title' => (string) ($cat['name'] ?? ''),
        'url' => Seo::absolute(category_url((string) ($cat['slug'] ?? ''))),
    ], array_slice($categories, 0, 10)),
]);
$pageTitle = $seo['pageTitle'];
$pageDescription = $seo['pageDescription'];
$canonicalUrl = $seo['canonicalUrl'];
$seoMeta = $seo['seoMeta'];
$ogTags = $seo['ogTags'];
$twitterTags = $seo['twitterTags'];
$schemaMarkup = $seo['schemaMarkup'];
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