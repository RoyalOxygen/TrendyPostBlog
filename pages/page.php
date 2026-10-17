<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$slug = $_GET['slug'] ?? '';
$page = Page::getBySlug($slug);
if (!$page) { http_response_code(404); include PAGES_PATH . 'errors/404.php'; exit; }
$siteName = (string) Setting::get('site_name', 'TrendyPost');
$pageTitle = seo_page_title((string) (!empty($page['seo_title']) ? $page['seo_title'] : $page['title']), $siteName);
$pageDescription = $page['seo_description'] ?? excerpt($page['content'], 160);
$pageUrl = page_url($slug);
$pageImage = !empty($page['featured_image']) ? Seo::absolute(media_url($page['featured_image'], '')) : Seo::absolute(SEO_DEFAULT_IMAGE);
$seo = Seo::build([
    'title' => $pageTitle,
    'description' => $pageDescription,
    'canonical' => Seo::absolute($pageUrl),
    'image' => $pageImage,
    'image_alt' => (string) $page['title'],
    'schema_type' => 'WebPage',
    'breadcrumbs' => [['Home', Seo::home()], [$page['title'], $pageUrl]],
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
<section class="page-header">
    <div class="container"><h1><?php echo $page['title']; ?></h1></div>
</section>
<main class="main-content py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <article class="single-post">
                    <?php if ($page['featured_image']): ?>
                    <div class="featured-image mb-4">
                        <img src="<?php echo media_url($page['featured_image']); ?>" alt="" class="img-fluid rounded">
                    </div>
                    <?php endif; ?>
                    <div class="post-content">
                        <?php echo $page['content']; ?>
                    </div>
                </article>
            </div>
        </div>
    </div>
</main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>