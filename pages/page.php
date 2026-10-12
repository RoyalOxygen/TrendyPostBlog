<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$slug = $_GET['slug'] ?? '';
$page = Page::getBySlug($slug);
if (!$page) { http_response_code(404); include PAGES_PATH . 'errors/404.php'; exit; }
$pageTitle = ($page['seo_title'] ?? $page['title']) . ' | ' . Setting::get('site_name');
$pageDescription = $page['seo_description'] ?? excerpt($page['content'], 160);
$seoMeta = meta_tags($pageTitle, $pageDescription);
$canonicalUrl = canonical_url('page/' . $slug);
$ogTags = og_tags(['title'=>$pageTitle,'description'=>$pageDescription,'type'=>'website','url'=>$canonicalUrl]);
$schema = ['@type'=>'WebPage','name'=>$page['title'],'description'=>$pageDescription,'url'=>$canonicalUrl];
$schemaMarkup = schema_jsonld($schema);
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