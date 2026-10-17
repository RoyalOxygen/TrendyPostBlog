<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$tags = Tag::getAll(true);
$siteName = (string) Setting::get('site_name', 'TrendyPost');
$pageTitle = seo_page_title('Tags', $siteName);
$pageDescription = 'Browse all topics and tags covered by ' . $siteName . '.';
$canonicalUrl = Seo::absolute(url('tags'));
$seo = Seo::build([
    'title' => $pageTitle,
    'description' => $pageDescription,
    'canonical' => $canonicalUrl,
    'schema_type' => 'CollectionPage',
    'breadcrumbs' => [['Home', Seo::home()], ['Tags', url('tags')]],
    'related' => array_map(static fn($t) => [
        'title' => (string) ($t['name'] ?? ''),
        'url' => Seo::absolute(tag_url((string) ($t['slug'] ?? ''))),
    ], array_slice($tags, 0, 10)),
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
<section class="page-header"><div class="container"><h1>Tags</h1></div></section>
<main class="main-content py-5"><div class="container">
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($tags as $t): ?>
        <a class="btn btn-outline-success btn-sm" href="<?php echo tag_url($t['slug']); ?>"><?php echo esc($t['name']); ?></a>
        <?php endforeach; ?>
    </div>
</div></main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>
