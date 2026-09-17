<?php
/**
 * TRENDYPOST - Homepage
 * =====================
 * Magazine-style layout matching reference images.
 */

if (!defined('TRENDYPOST')) die('Direct access not permitted.');

// ============================================================
// TIMEZONE SETUP - Ensure proper time handling
// ============================================================
date_default_timezone_set(APP_TIMEZONE);

// Get content with proper date handling
$featuredPosts = Post::getFeatured(3);
$latestPosts = Post::getLatest(6);
$popularPosts = Post::getPopular(5);
$breakingNews = Post::getBreaking(1);
$categories = Category::getAll(true);

/**
 * Get relevant stories based on user behavior, categories, and trends
 */
function getRelevantStories($featuredPosts, $latestPosts, $limit = 3) {
    $relevantIds = [];
    $relevantPosts = [];
    
    // Try to get posts from the most viewed categories
    $categoryId = $featuredPosts[0]['category_id'] ?? 0;
    
    if ($categoryId) {
        // Build exclude lists
        $featuredIds = array_column($featuredPosts, 'id');
        $latestIds = array_column($latestPosts, 'id');
        $excludeIds = array_merge($featuredIds, $latestIds);
        $excludeClause = !empty($excludeIds) ? "AND p.id NOT IN (" . implode(',', $excludeIds) . ")" : "";
        
        $posts = Database::fetchAll(
            "SELECT p.*, u.display_name as author_name, c.name as category_name, c.slug as category_slug, c.color as category_color,
                    COUNT(DISTINCT pv.id) as view_count
             FROM posts p
             LEFT JOIN users u ON p.author_id = u.id
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN post_views pv ON p.id = pv.post_id
             WHERE p.status = 'published' 
               AND p.published_at <= NOW() 
               AND p.category_id = ?
               {$excludeClause}
             GROUP BY p.id
             ORDER BY p.is_breaking DESC, p.view_count DESC, p.published_at DESC
             LIMIT ?",
            [$categoryId, $limit]
        );
        $relevantPosts = $posts;
        $relevantIds = array_column($posts, 'id');
    }
    
    // If not enough posts from that category, get popular posts
    if (count($relevantPosts) < $limit) {
        $remaining = $limit - count($relevantPosts);
        $excludeIds = array_merge(
            array_column($featuredPosts, 'id'),
            array_column($latestPosts, 'id'),
            $relevantIds
        );
        $excludeClause = !empty($excludeIds) ? "AND p.id NOT IN (" . implode(',', $excludeIds) . ")" : "";
        
        $morePosts = Database::fetchAll(
            "SELECT p.*, u.display_name as author_name, c.name as category_name, c.slug as category_slug, c.color as category_color
             FROM posts p
             LEFT JOIN users u ON p.author_id = u.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.status = 'published' 
               AND p.published_at <= NOW() 
               {$excludeClause}
             ORDER BY p.is_featured DESC, p.view_count DESC, p.published_at DESC
             LIMIT ?",
            [$remaining]
        );
        $relevantPosts = array_merge($relevantPosts, $morePosts);
    }
    
    return $relevantPosts;
}

// Get relevant stories
$relevantStories = getRelevantStories($featuredPosts, $latestPosts, 3);

// SEO
$pageTitle = Setting::get('seo_default_title');
$pageDescription = Setting::get('seo_default_description');
$canonicalUrl = canonical_url();

$seoMeta = meta_tags($pageTitle, $pageDescription, Setting::get('seo_default_keywords'));
$ogTags = og_tags([
    'title' => $pageTitle,
    'description' => $pageDescription,
    'type' => 'website',
    'url' => $canonicalUrl
]);
$twitterTags = twitter_tags([
    'title' => $pageTitle,
    'description' => $pageDescription
]);

// Schema: WebSite + Organization with proper date formatting
$schema = [
    '@graph' => [
        [
            '@type' => 'WebSite',
            'name' => Setting::get('site_name', 'TrendyPost'),
            'url' => BASE_URL,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => BASE_URL . 'search?q={search_term_string}'],
                'query-input' => 'required name=search_term_string'
            ]
        ],
        [
            '@type' => 'Organization',
            'name' => Setting::get('site_name', 'TrendyPost'),
            'url' => BASE_URL,
            'logo' => image_url('logo.png'),
            'sameAs' => array_filter([
                Setting::get('social_facebook', ''),
                Setting::get('social_twitter', ''),
                Setting::get('social_instagram', ''),
                Setting::get('social_youtube', ''),
                Setting::get('social_linkedin', '')
            ]),
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => Setting::get('site_email', '')
            ]
        ]
    ]
];
$schemaMarkup = schema_jsonld($schema);

include INCLUDES_PATH . 'Header.php';
?>

<!-- Reading Progress Bar -->
<div id="readingProgress" style="position:fixed;top:0;left:0;height:3px;background:var(--tp-primary);z-index:9999;width:0;transition:width 0.1s"></div>

<main class="main-content py-4">
    <div class="container">
        
        <!-- Featured Section -->
        <?php if (!empty($featuredPosts)): ?>
        <section class="featured-section mb-4">
            <div class="row g-3">
                <!-- Main Featured Post (Left - Large) -->
                <div class="col-lg-8">
                    <?php $main = $featuredPosts[0]; 
                    $img = $main['featured_image'] ? upload_url($main['featured_image']) : image_url('placeholder.jpg');
                    $catColor = $main['category_color'] ?? '#00C853';
                    $publishedDate = !empty($main['published_at']) ? date('F j, Y', strtotime($main['published_at'])) : 'Just now';
                    ?>
                    <article class="featured-large">
                        <img src="<?php echo $img; ?>" alt="<?php echo esc_attr($main['title']); ?>" loading="lazy">
                        <span class="category-badge" style="background-color:<?php echo $catColor; ?>">
                            <?php echo $main['category_name'] ?? 'News'; ?>
                        </span>
                        <div class="overlay">
                            <h3><a href="<?php echo post_url($main['slug']); ?>"><?php echo $main['title']; ?></a></h3>
                            <div class="post-meta">
                                <span><i class="bi bi-person"></i> <?php echo $main['author_name'] ?? 'Admin'; ?></span>
                                <span><i class="bi bi-calendar3"></i> <?php echo $publishedDate; ?></span>
                                <?php if (isset($main['comment_count'])): ?>
                                <span><i class="bi bi-chat"></i> <?php echo $main['comment_count']; ?> Comments</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                </div>
                
                <!-- Side Featured Posts (Right - 2 stacked) -->
                <div class="col-lg-4">
                    <div class="row g-3">
                        <?php for ($i = 1; $i <= 2; $i++): if (!isset($featuredPosts[$i])) break; 
                        $side = $featuredPosts[$i];
                        $sImg = $side['featured_image'] ? upload_url($side['featured_image']) : image_url('placeholder.jpg');
                        $sCatColor = $side['category_color'] ?? '#00C853';
                        $sideDate = !empty($side['published_at']) ? date('M j, Y', strtotime($side['published_at'])) : 'Just now';
                        ?>
                        <div class="col-12">
                            <article class="featured-large" style="min-height: 195px;">
                                <img src="<?php echo $sImg; ?>" alt="<?php echo esc_attr($side['title']); ?>" loading="lazy">
                                <span class="category-badge" style="background-color:<?php echo $sCatColor; ?>">
                                    <?php echo $side['category_name'] ?? 'News'; ?>
                                </span>
                                <div class="overlay" style="padding: 40px 20px 20px;">
                                    <h3 style="font-size: 15px;"><a href="<?php echo post_url($side['slug']); ?>"><?php echo $side['title']; ?></a></h3>
                                    <div class="post-meta" style="font-size: 11px;">
                                        <span><i class="bi bi-person"></i> <?php echo $side['author_name'] ?? 'Admin'; ?></span>
                                        <span><i class="bi bi-calendar3"></i> <?php echo $sideDate; ?></span>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Main Layout: Content + Sidebar -->
        <div class="row">
            <!-- Left Column - Main Content -->
            <div class="col-lg-8">
                
                <!-- Latest News Section -->
                <section class="latest-news mb-4">
                    <h4 class="section-title mb-4">Latest News</h4>
                    
                    <?php foreach ($latestPosts as $post): 
                        $pImg = $post['featured_image'] ? upload_url($post['featured_image']) : image_url('placeholder.jpg');
                        $pCatColor = $post['category_color'] ?? '#00C853';
                        $postDate = !empty($post['published_at']) ? date('F j, Y', strtotime($post['published_at'])) : 'Just now';
                        $timeAgo = !empty($post['published_at']) ? time_ago($post['published_at']) : 'Just now';
                    ?>
                    <article class="post-card horizontal-post mb-4">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <div class="card-img-wrapper" style="padding-top: 75%;">
                                    <img src="<?php echo $pImg; ?>" alt="<?php echo esc_attr($post['title']); ?>" loading="lazy">
                                    <span class="category-badge" style="background-color:<?php echo $pCatColor; ?>">
                                        <?php echo $post['category_name'] ?? 'News'; ?>
                                    </span>
                                    <?php if ($post['is_breaking'] ?? 0): ?>
                                        <span class="breaking-badge">BREAKING</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="<?php echo post_url($post['slug']); ?>"><?php echo $post['title']; ?></a>
                                    </h5>
                                    <div class="post-meta mb-2">
                                        <span><i class="bi bi-person"></i> <?php echo $post['author_name'] ?? 'Admin'; ?></span>
                                        <span><i class="bi bi-calendar3"></i> <?php echo $postDate; ?> <span class="text-muted">(<?php echo $timeAgo; ?>)</span></span>
                                        <span><i class="bi bi-chat"></i> <?php echo $post['comment_count'] ?? 0; ?> Comments</span>
                                    </div>
                                    <p class="card-text"><?php echo excerpt($post['excerpt'] ?? $post['content'], 160); ?></p>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </section>
                
                <!-- Sidebar Ad (Mobile Only) -->
                <?php $sidebarAds = get_ads('sidebar_top', 1); if (!empty($sidebarAds)): ?>
                <div class="d-lg-none mb-4">
                    <div class="ad-container ad-sidebar">
                        <div class="ad-label">Advertisement</div>
                        <?php echo render_ad($sidebarAds[0]); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Popular Posts Section -->
                <section class="popular-posts-section mb-4">
                    <h4 class="section-title mb-4">Popular Posts</h4>
                    
                    <!-- First popular post - large style -->
                    <?php if (!empty($popularPosts)): $pop1 = $popularPosts[0]; 
                    $popImg = $pop1['featured_image'] ? upload_url($pop1['featured_image']) : image_url('placeholder.jpg');
                    $popCatColor = $pop1['category_color'] ?? '#00C853';
                    $popDate = !empty($pop1['published_at']) ? date('F j, Y', strtotime($pop1['published_at'])) : 'Just now';
                    ?>
                    <article class="post-card mb-4">
                        <div class="card-img-wrapper" style="padding-top: 45%;">
                            <img src="<?php echo $popImg; ?>" alt="<?php echo esc_attr($pop1['title']); ?>" loading="lazy">
                            <span class="category-badge" style="background-color:<?php echo $popCatColor; ?>">
                                <?php echo $pop1['category_name'] ?? 'News'; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><a href="<?php echo post_url($pop1['slug']); ?>"><?php echo $pop1['title']; ?></a></h5>
                            <div class="post-meta">
                                <span><i class="bi bi-person"></i> <?php echo $pop1['author_name'] ?? 'Admin'; ?></span>
                                <span><i class="bi bi-calendar3"></i> <?php echo $popDate; ?></span>
                                <span><i class="bi bi-chat"></i> <?php echo $pop1['comment_count'] ?? 0; ?> Comments</span>
                                <span><i class="bi bi-eye"></i> <?php echo format_number($pop1['view_count'] ?? 0); ?> views</span>
                            </div>
                        </div>
                    </article>
                    <?php endif; ?>
                    
                    <!-- 2-column grid for remaining popular posts -->
                    <div class="row">
                        <?php for ($i = 1; $i < min(3, count($popularPosts)); $i++): 
                        $pop = $popularPosts[$i];
                        $pImg = $pop['featured_image'] ? upload_url($pop['featured_image']) : image_url('placeholder.jpg');
                        $pCatColor = $pop['category_color'] ?? '#00C853';
                        $popDate = !empty($pop['published_at']) ? date('M j, Y', strtotime($pop['published_at'])) : 'Just now';
                        ?>
                        <div class="col-md-6">
                            <article class="post-card mb-4">
                                <div class="card-img-wrapper">
                                    <img src="<?php echo $pImg; ?>" alt="<?php echo esc_attr($pop['title']); ?>" loading="lazy">
                                    <span class="category-badge" style="background-color:<?php echo $pCatColor; ?>">
                                        <?php echo $pop['category_name'] ?? 'News'; ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title" style="font-size: 15px;">
                                        <a href="<?php echo post_url($pop['slug']); ?>"><?php echo $pop['title']; ?></a>
                                    </h5>
                                    <p class="card-text" style="font-size: 13px;"><?php echo excerpt($pop['excerpt'] ?? $pop['content'], 120); ?></p>
                                    <div class="post-meta">
                                        <span><i class="bi bi-person"></i> <?php echo $pop['author_name'] ?? 'Admin'; ?></span>
                                        <span><i class="bi bi-calendar3"></i> <?php echo $popDate; ?></span>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <?php endfor; ?>
                    </div>
                </section>
                
                <!-- Relevant Stories Section - NOW USING THE getRelevantStories() FUNCTION -->
                <section class="relevant-stories mb-4">
                    <h4 class="section-title mb-4">Relevant Stories</h4>
                    
                    <?php 
                    // Check if we have relevant stories
                    if (!empty($relevantStories)): 
                        foreach ($relevantStories as $post): 
                            $rImg = $post['featured_image'] ? upload_url($post['featured_image']) : image_url('placeholder.jpg');
                            $rCatColor = $post['category_color'] ?? '#00C853';
                            $rDate = !empty($post['published_at']) ? date('F j, Y', strtotime($post['published_at'])) : 'Just now';
                            $rTimeAgo = !empty($post['published_at']) ? time_ago($post['published_at']) : 'Just now';
                    ?>
                    <article class="post-card horizontal-post mb-4">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <div class="card-img-wrapper" style="padding-top: 75%;">
                                    <img src="<?php echo $rImg; ?>" alt="<?php echo esc_attr($post['title']); ?>" loading="lazy">
                                    <span class="category-badge" style="background-color:<?php echo $rCatColor; ?>">
                                        <?php echo $post['category_name'] ?? 'News'; ?>
                                    </span>
                                    <?php if ($post['is_breaking'] ?? 0): ?>
                                        <span class="breaking-badge">BREAKING</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="<?php echo post_url($post['slug']); ?>"><?php echo $post['title']; ?></a>
                                    </h5>
                                    <div class="post-meta mb-2">
                                        <span><i class="bi bi-person"></i> <?php echo $post['author_name'] ?? 'Admin'; ?></span>
                                        <span><i class="bi bi-calendar3"></i> <?php echo $rDate; ?> <span class="text-muted">(<?php echo $rTimeAgo; ?>)</span></span>
                                        <span><i class="bi bi-chat"></i> <?php echo $post['comment_count'] ?? 0; ?> Comments</span>
                                        <?php if (!empty($post['view_count'])): ?>
                                            <span><i class="bi bi-eye"></i> <?php echo format_number($post['view_count']); ?> views</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="card-text"><?php echo excerpt($post['excerpt'] ?? $post['content'], 160); ?></p>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; 
                    else: 
                        // Fallback: show latest posts if no relevant stories
                        $fallbackPosts = array_slice($latestPosts, 3, 3);
                        foreach ($fallbackPosts as $post): 
                            $rImg = $post['featured_image'] ? upload_url($post['featured_image']) : image_url('placeholder.jpg');
                            $rCatColor = $post['category_color'] ?? '#00C853';
                            $rDate = !empty($post['published_at']) ? date('F j, Y', strtotime($post['published_at'])) : 'Just now';
                            $rTimeAgo = !empty($post['published_at']) ? time_ago($post['published_at']) : 'Just now';
                    ?>
                    <article class="post-card horizontal-post mb-4">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <div class="card-img-wrapper" style="padding-top: 75%;">
                                    <img src="<?php echo $rImg; ?>" alt="<?php echo esc_attr($post['title']); ?>" loading="lazy">
                                    <span class="category-badge" style="background-color:<?php echo $rCatColor; ?>">
                                        <?php echo $post['category_name'] ?? 'News'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="<?php echo post_url($post['slug']); ?>"><?php echo $post['title']; ?></a>
                                    </h5>
                                    <div class="post-meta mb-2">
                                        <span><i class="bi bi-person"></i> <?php echo $post['author_name'] ?? 'Admin'; ?></span>
                                        <span><i class="bi bi-calendar3"></i> <?php echo $rDate; ?> <span class="text-muted">(<?php echo $rTimeAgo; ?>)</span></span>
                                        <span><i class="bi bi-chat"></i> <?php echo $post['comment_count'] ?? 0; ?> Comments</span>
                                    </div>
                                    <p class="card-text"><?php echo excerpt($post['excerpt'] ?? $post['content'], 160); ?></p>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; endif; ?>
                </section>
                
            </div>
            
            <!-- Right Column - Sidebar -->
            <aside class="col-lg-4">

                <!-- Editor's Pick Widget -->
                <div class="widget editors-pick-widget">
                    <h5 class="widget-title">Editor's Pick</h5>
                    
                    <?php $editorsPick = !empty($popularPosts[0]) ? $popularPosts[0] : ($latestPosts[0] ?? null); ?>
                    <?php if ($editorsPick): 
                        $edImg = $editorsPick['featured_image'] ? upload_url($editorsPick['featured_image']) : image_url('placeholder.jpg');
                        $edDate = !empty($editorsPick['published_at']) ? date('M j, Y', strtotime($editorsPick['published_at'])) : 'Just now';
                    ?>
                    <div class="editors-pick-featured">
                        <img src="<?php echo $edImg; ?>" alt="<?php echo esc_attr($editorsPick['title']); ?>" loading="lazy">
                        <div class="overlay">
                            <span class="category-badge" style="background-color:<?php echo $editorsPick['category_color'] ?? '#00C853'; ?>">
                                <?php echo $editorsPick['category_name'] ?? 'Featured'; ?>
                            </span>
                            <h6><a href="<?php echo post_url($editorsPick['slug']); ?>"><?php echo $editorsPick['title']; ?></a></h6>
                            <div class="post-meta">
                                <span><i class="bi bi-person"></i> <?php echo $editorsPick['author_name'] ?? 'Admin'; ?></span>
                                <span><i class="bi bi-calendar3"></i> <?php echo $edDate; ?></span>
                            </div>
                            <p style="font-size: 12px; color: rgba(255,255,255,0.8); margin-top: 5px;">
                                <?php echo excerpt($editorsPick['excerpt'] ?? $editorsPick['content'], 100); ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Small editor's picks -->
                    <?php foreach (array_slice($popularPosts, 1, 3) as $ep): 
                        $epImg = $ep['featured_image'] ? upload_url($ep['featured_image']) : image_url('placeholder.jpg');
                        $epDate = !empty($ep['published_at']) ? date('M j, Y', strtotime($ep['published_at'])) : 'Just now';
                    ?>
                    <div class="sidebar-post">
                        <div class="post-thumb">
                            <img src="<?php echo $epImg; ?>" alt="<?php echo esc_attr($ep['title']); ?>" loading="lazy">
                        </div>
                        <div class="post-info">
                            <h6><a href="<?php echo post_url($ep['slug']); ?>"><?php echo $ep['title']; ?></a></h6>
                            <div class="post-meta">
                                <span><i class="bi bi-calendar3"></i> <?php echo $epDate; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Most Popular Widget -->
                <div class="widget most-popular-widget">
                    <h5 class="widget-title">Most Popular</h5>
                    
                    <?php foreach (array_slice($popularPosts, 0, 4) as $mp): 
                        $mpImg = $mp['featured_image'] ? upload_url($mp['featured_image']) : image_url('placeholder.jpg');
                        $mpDate = !empty($mp['published_at']) ? date('M j, Y', strtotime($mp['published_at'])) : 'Just now';
                    ?>
                    <div class="sidebar-post">
                        <div class="post-thumb">
                            <img src="<?php echo $mpImg; ?>" alt="<?php echo esc_attr($mp['title']); ?>" loading="lazy">
                        </div>
                        <div class="post-info">
                            <h6><a href="<?php echo post_url($mp['slug']); ?>"><?php echo $mp['title']; ?></a></h6>
                            <div class="post-meta">
                                <span><i class="bi bi-calendar3"></i> <?php echo $mpDate; ?></span>
                                <span><i class="bi bi-eye"></i> <?php echo format_number($mp['view_count'] ?? 0); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Sticky Sidebar Ad -->
                <?php $stickyAds = get_ads('sticky_sidebar', 1); if (!empty($stickyAds)): ?>
                <div class="ad-container ad-sidebar sticky-top" style="top: 80px; z-index: 100;">
                    <div class="ad-label">Advertisement</div>
                    <?php echo render_ad($stickyAds[0]); ?>
                </div>
                <?php endif; ?>
                
                <!-- Social Networks Widget -->
                <div class="widget social-widget">
                    <h5 class="widget-title">Social Networks</h5>
                    
                    <?php if ($fb = Setting::get('social_facebook', '')): ?>
                    <a href="<?php echo $fb; ?>" class="social-btn facebook" target="_blank" rel="noopener">
                        <span><i class="bi bi-facebook"></i> 983 Likes</span>
                        <span>Like our page</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($tw = Setting::get('social_twitter', '')): ?>
                    <a href="<?php echo $tw; ?>" class="social-btn twitter" target="_blank" rel="noopener">
                        <span><i class="bi bi-twitter-x"></i> 983 Followers</span>
                        <span>Follow Us</span>
                    </a>
                    <?php endif; ?>
                    
                    <a href="#" class="social-btn youtube" target="_blank" rel="noopener">
                        <span><i class="bi bi-youtube"></i> 983 Subscriber</span>
                        <span>Subscribe</span>
                    </a>
                    
                    <a href="#" class="social-btn rss" target="_blank" rel="noopener">
                        <span><i class="bi bi-rss"></i> 983 Subscribe</span>
                        <span>Subscribe</span>
                    </a>
                </div>
                
                <!-- Categories Widget -->
                <div class="widget categories-widget">
                    <h5 class="widget-title">Categories</h5>
                    
                    <?php foreach ($categories as $cat): ?>
                    <div class="category-list-item">
                        <a href="<?php echo category_url($cat['slug']); ?>">
                            <i class="bi bi-folder" style="color:<?php echo $cat['color'] ?? '#00C853'; ?>"></i>
                            <?php echo $cat['name']; ?>
                        </a>
                        <span class="count"><?php echo $cat['post_count'] ?? 0; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
            </aside>
        </div>

        
    </div>
    
</main>

<style>
.section-title {
    font-family: var(--tp-font-heading);
    font-size: 1.3rem;
    font-weight: 700;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--tp-gray-200);
    position: relative;
}
.section-title::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 60px;
    height: 2px;
    background: var(--tp-primary);
}
.horizontal-post .card-img-wrapper {
    border-radius: var(--tp-border-radius) 0 0 var(--tp-border-radius);
}
.horizontal-post .card-body {
    padding: 20px;
}
.breaking-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #dc3545;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 4px;
    z-index: 5;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>

<?php include INCLUDES_PATH . 'Footer.php'; ?>