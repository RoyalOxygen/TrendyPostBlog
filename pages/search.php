<?php
/**
 * TRENDYPOST - Search Results Page
 */
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$query = trim($_GET['q'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = POSTS_PER_PAGE;
$posts = [];
$total = 0;
if (!empty($query)) {
    $posts = Post::getList(['search' => $query], $page, $perPage);
    $total = Post::count(['search' => $query]);
}
$pageTitle = $query ? "Search: $query | TrendyPost" : 'Search | TrendyPost';
$seoMeta = meta_tags($pageTitle, "Search results for: $query", $query);
$canonicalUrl = canonical_url('search' . ($query ? '?q=' . urlencode($query) : ''));
$ogTags = og_tags(['title' => $pageTitle, 'description' => "Search results for: $query", 'type' => 'website', 'url' => $canonicalUrl]);
include INCLUDES_PATH . 'Header.php';
?>
<section class="page-header">
    <div class="container">
        <h1><i class="bi bi-search"></i> Search</h1>
        <?php if ($query): ?><p class="mb-0">Results for &ldquo;<?php echo esc_attr($query); ?>&rdquo;</p><?php endif; ?>
    </div>
</section>
<main class="main-content py-4">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <form action="<?php echo search_url(); ?>" method="GET" class="mb-4">
                    <div class="input-group input-group-lg">
                        <input type="text" name="q" class="form-control" placeholder="Search articles..." value="<?php echo esc_attr($query); ?>">
                        <button class="btn btn-success" type="submit" style="background-color:var(--tp-primary);border-color:var(--tp-primary);">Search</button>
                    </div>
                </form>
                <?php if ($query && empty($posts)): ?>
                <div class="alert alert-info">No results found for &ldquo;<?php echo esc_attr($query); ?>&rdquo;. Try different keywords.</div>
                <?php elseif ($query): foreach ($posts as $post): $pImg = media_url($post['featured_image']); ?>
                <article class="post-card horizontal-post mb-4">
                    <div class="row g-0">
                        <div class="col-md-4">
                            <div class="card-img-wrapper" style="padding-top:75%;"><img src="<?php echo $pImg; ?>" alt="" loading="lazy"></div>
                        </div>
                        <div class="col-md-8">
                            <div class="card-body">
                                <h5 class="card-title"><a href="<?php echo post_url($post['slug']); ?>"><?php echo $post['title']; ?></a></h5>
                                <p class="card-text"><?php echo excerpt($post['excerpt'] ?? $post['content'], 160); ?></p>
                                <div class="post-meta"><i class="bi bi-calendar3"></i> <?php echo format_date($post['published_at']); ?></div>
                            </div>
                        </div>
                    </div>
                </article>
                <?php endforeach; echo pagination($total, $perPage, $page, search_url()); endif; ?>
            </div>
            <aside class="col-lg-4">
                <div class="widget">
                    <h5 class="widget-title">Popular Posts</h5>
                    <?php foreach (Post::getPopular(4) as $mp): $mpImg = media_url($mp['featured_image']); ?>
                    <div class="sidebar-post">
                        <div class="post-thumb"><img src="<?php echo $mpImg; ?>" alt="" loading="lazy"></div>
                        <div class="post-info"><h6><a href="<?php echo post_url($mp['slug']); ?>"><?php echo $mp['title']; ?></a></h6></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>
    </div>
</main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>
