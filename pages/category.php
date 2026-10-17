<?php
/**
 * TRENDYPOST - Category Archive Page
 */
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$slug = $_GET['slug'] ?? '';
$category = Category::getBySlug($slug);
if (!$category) { http_response_code(404); include PAGES_PATH . 'errors/404.php'; exit; }
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = POSTS_PER_PAGE;
$posts = Post::getList(['category' => $slug], $page, $perPage);
$total = Post::count(['category' => $slug]);
$siteName = (string) Setting::get('site_name', 'TrendyPost');
$pageTitle = seo_page_title((string) (!empty($category['seo_title']) ? $category['seo_title'] : ($category['name'] . ' News & Articles')), $siteName);
$pageDescription = $category['seo_description'] ?? ('Latest ' . $category['name'] . ' news, analysis and stories from ' . $siteName . '.');
$baseUrl = category_url($slug);
$seo = Seo::build([
    'title' => $pageTitle,
    'description' => $pageDescription,
    'keywords' => (string) ($category['seo_keywords'] ?? ''),
    'canonical' => Seo::absolute($baseUrl),
    'base_url' => Seo::absolute($baseUrl),
    'page' => $page,
    'has_next' => ($page * $perPage) < $total,
    'schema_type' => 'CollectionPage',
    'breadcrumbs' => [['Home', Seo::home()], [$category['name'], $baseUrl]],
    'related' => Seo::itemsFromPosts($posts),
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
    <div class="container">
        <h1><i class="bi bi-folder"></i> <?php echo $category['name']; ?></h1>
        <p class="mb-0"><?php echo $category['description'] ?? ''; ?></p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url(); ?>">Home</a></li>
                <li class="breadcrumb-item active"><?php echo $category['name']; ?></li>
            </ol>
        </nav>
    </div>
</section>
<main class="main-content py-4">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0"><?php echo $total; ?> Article<?php echo $total !== 1 ? 's' : ''; ?></h5>
                </div>
                <?php if (empty($posts)): ?>
                <div class="alert alert-info">No articles found in this category.</div>
                <?php else: foreach ($posts as $post):
                    $pImg = media_url($post['featured_image']);
                ?>
                <article class="post-card horizontal-post mb-4">
                    <div class="row g-0">
                        <div class="col-md-4">
                            <div class="card-img-wrapper" style="padding-top:75%;">
                                <img src="<?php echo $pImg; ?>" alt="<?php echo esc_attr($post['title']); ?>" loading="lazy">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card-body">
                                <h5 class="card-title"><a href="<?php echo post_url($post['slug']); ?>"><?php echo $post['title']; ?></a></h5>
                                <p class="card-text"><?php echo excerpt($post['excerpt'] ?? $post['content'], 160); ?></p>
                                <div class="post-meta">
                                    <span><i class="bi bi-person"></i> <?php echo $post['author_name'] ?? 'Admin'; ?></span>
                                    <span><i class="bi bi-calendar3"></i> <?php echo format_date($post['published_at']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
                <?php endforeach; echo pagination($total, $perPage, $page, category_url($slug)); endif; ?>
            </div>
            <aside class="col-lg-4">
                <div class="widget">
                    <h5 class="widget-title">Categories</h5>
                    <?php foreach (Category::getAll() as $cat): ?>
                    <div class="category-list-item">
                        <a href="<?php echo category_url($cat['slug']); ?>" class="<?php echo $cat['slug'] === $slug ? 'fw-bold text-success' : ''; ?>">
                            <i class="bi bi-folder" style="color:<?php echo $cat['color'] ?? '#00C853'; ?>"></i> <?php echo $cat['name']; ?>
                        </a>
                        <span class="count"><?php echo $cat['post_count'] ?? 0; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php $sAds = get_ads('sidebar_top', 1); if (!empty($sAds)): ?>
                <div class="ad-container ad-sidebar"><div class="ad-label">Advertisement</div><?php echo render_ad($sAds[0]); ?></div>
                <?php endif; ?>
                <?php $sbAds = get_ads('sidebar_bottom', 1); if (!empty($sbAds)): ?>
                <div class="ad-container ad-sidebar mt-3"><div class="ad-label">Advertisement</div><?php echo render_ad($sbAds[0]); ?></div>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>
