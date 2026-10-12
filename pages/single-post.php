<?php
/**
 * TRENDYPOST - Single Post Page
 * =============================
 * Article display with comments, related posts, sidebar.
 */

if (!defined('TRENDYPOST')) die('Direct access not permitted.');

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header('Location: ' . url());
    exit;
}

$post = Post::getBySlug($slug);
if (!$post) {
    http_response_code(404);
    include PAGES_PATH . 'errors/404.php';
    exit;
}

// Increment view count
Post::incrementViews($post['id']);

// Get related data
$relatedPosts = Post::getRelated($post['id'], $post['category_id'] ?? 0, 3);
$comments = Comment::getByPost($post['id']);
$commentCount = Comment::countByPost($post['id']);
$prevPost = Post::getAdjacent($post['id'], 'prev', $post['category_id'] ?? 0);
$nextPost = Post::getAdjacent($post['id'], 'next', $post['category_id'] ?? 0);

// Ad injection data
$adAfter2 = get_ads('after_para_2', 1);
$adAfter5 = get_ads('after_para_5', 1);
$adInAuto = get_ads('in_content_auto', 1);

// SEO setup
$pageTitle = !empty($post['seo_title']) ? $post['seo_title'] : $post['title'] . ' | ' . Setting::get('site_name', 'TrendyPost');
$pageDescription = !empty($post['seo_description']) ? $post['seo_description'] : excerpt($post['excerpt'] ?? $post['content'], 160);

// ============================================================
// POST URL - Use the actual post URL
// ============================================================
$postUrl = post_url($post['slug']);

// ============================================================
// POST IMAGE - Get the image for sharing
// ============================================================
$shareImage = '';

if (!empty($post['og_image'])) {
    $shareImage = media_url($post['og_image'], SEO_DEFAULT_IMAGE);
} elseif (!empty($post['featured_image'])) {
    $shareImage = media_url($post['featured_image'], SEO_DEFAULT_IMAGE);
} else {
    $shareImage = SEO_DEFAULT_IMAGE;
}

// Ensure absolute URL for the image
if (!filter_var($shareImage, FILTER_VALIDATE_URL)) {
    $shareImage = rtrim(BASE_URL, '/') . '/' . ltrim($shareImage, '/');
}

// Force HTTPS if site uses HTTPS
if (strpos(BASE_URL, 'https://') === 0) {
    $shareImage = str_replace('http://', 'https://', $shareImage);
}

// Get share title and description
$shareTitle = !empty($post['og_title']) ? $post['og_title'] : $post['title'];
$shareDescription = !empty($post['og_description']) ? $post['og_description'] : $pageDescription;

// Twitter image
$twitterImage = !empty($post['twitter_image']) ? media_url($post['twitter_image'], $shareImage) : $shareImage;

if (!filter_var($twitterImage, FILTER_VALIDATE_URL)) {
    $twitterImage = rtrim(BASE_URL, '/') . '/' . ltrim($twitterImage, '/');
}

if (strpos(BASE_URL, 'https://') === 0) {
    $twitterImage = str_replace('http://', 'https://', $twitterImage);
}

// ============================================================
// META TAGS
// ============================================================
$canonicalUrl = $postUrl;
$seoMeta = meta_tags($pageTitle, $pageDescription, $post['seo_keywords'] ?? '', $post['meta_robots'] ?? 'index,follow');

// Open Graph tags - using post URL and post image
$ogTags = og_tags([
    'title' => $shareTitle,
    'description' => $shareDescription,
    'type' => 'article',
    'url' => $postUrl,
    'image' => $shareImage,
    'image:secure_url' => $shareImage,
    'image:width' => '1200',
    'image:height' => '630',
    'image:type' => 'image/jpeg',
    'site_name' => Setting::get('site_name', 'TrendyPost'),
    'locale' => APP_LOCALE,
    'article:published_time' => $post['published_at'],
    'article:modified_time' => $post['updated_at'],
    'article:author' => $post['author_name'] ?? Setting::get('site_name', 'TrendyPost'),
    'article:section' => $post['category_name'] ?? 'News'
]);

// Twitter Card tags
$twitterTags = twitter_tags([
    'card' => $post['twitter_card'] ?? 'summary_large_image',
    'title' => !empty($post['twitter_title']) ? $post['twitter_title'] : $shareTitle,
    'description' => !empty($post['twitter_description']) ? $post['twitter_description'] : $shareDescription,
    'image' => $twitterImage,
    'site' => '@trendypost',
    'image:width' => '1200',
    'image:height' => '600'
]);

// ============================================================
// SCHEMA MARKUP
// ============================================================
$tagsSchema = array_map(fn($t) => $t['name'], $post['tags'] ?? []);
$articleSchema = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => $post['schema_type'] ?? 'NewsArticle',
            'headline' => $post['title'],
            'description' => $pageDescription,
            'image' => $shareImage,
            'datePublished' => $post['published_at'],
            'dateModified' => $post['updated_at'],
            'author' => [
                '@type' => 'Person',
                'name' => $post['author_name'] ?? 'TrendyPost'
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => Setting::get('site_name', 'TrendyPost'),
                'logo' => ['@type' => 'ImageObject', 'url' => image_url('logo.png')]
            ],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $postUrl],
            'articleSection' => $post['category_name'] ?? 'News',
            'keywords' => implode(', ', array_merge($tagsSchema, explode(',', $post['seo_keywords'] ?? ''))),
            'wordCount' => $post['word_count'] ?? str_word_count(strip_tags($post['content']))
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => ['@id' => BASE_URL]],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $post['category_name'] ?? 'News', 'item' => ['@id' => category_url($post['category_slug'] ?? '')]],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title']]
            ]
        ]
    ]
];
$schemaMarkup = schema_jsonld($articleSchema);
if (class_exists('PublishSeo')) {
    $post['content'] = PublishSeo::stripFaqBlocks((string) $post['content']);
}

$featuredImg = !empty($post['featured_image']) ? media_url($post['featured_image'], '') : null;
if ($featuredImg === '') { $featuredImg = null; }
$readingTime = reading_time($post['content']);

// Include header
include INCLUDES_PATH . 'Header.php';
?>

<!-- Reading Progress -->
<div id="readingProgress" style="position:fixed;top:0;left:0;height:3px;background:var(--tp-primary);z-index:9999;width:0;transition:width 0.1s"></div>

<main class="main-content py-4">
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                
                <article class="single-post">
                    <!-- Category Badge -->
                    <?php if ($post['category_name']): ?>
                    <a href="<?php echo category_url($post['category_slug']); ?>" class="category-badge-inline" style="background-color:<?php echo $post['category_color'] ?? '#00C853'; ?>">
                        <?php echo $post['category_name']; ?>
                    </a>
                    <?php endif; ?>
                    
                    <h1><?php echo $post['title']; ?></h1>
                    
                    <!-- Meta Bar -->
                    <div class="post-meta-bar">
                        <?php if ($post['author_avatar']): ?>
                        <img src="<?php echo upload_url($post['author_avatar']); ?>" alt="" class="author-avatar">
                        <?php endif; ?>
                        <span><i class="bi bi-person"></i> <a href="<?php echo author_url($post['username'] ?? ''); ?>"><?php echo $post['author_name'] ?? 'Admin'; ?></a></span>
                        <span><i class="bi bi-calendar3"></i> <?php echo format_date($post['published_at']); ?></span>
                        <span><i class="bi bi-chat"></i> <?php echo $commentCount; ?> Comments</span>
                        <span><i class="bi bi-clock"></i> <?php echo $readingTime; ?> min read</span>
                        <span><i class="bi bi-eye"></i> <?php echo format_number($post['view_count']); ?> views</span>
                    </div>
                    
                    <!-- Featured Image -->
                    <?php if ($featuredImg): ?>
                    <div class="featured-image">
                        <img src="<?php echo $featuredImg; ?>" alt="<?php echo esc_attr($post['featured_image_alt'] ?? $post['title']); ?>" class="img-fluid">
                        <?php if ($post['featured_image_caption']): ?>
                        <figcaption class="text-center text-muted mt-2" style="font-size:13px;"><?php echo $post['featured_image_caption']; ?></figcaption>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Social Share -->
                    <div class="d-flex gap-2 mb-4">
                        <!-- Facebook Share -->
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($postUrl); ?>&picture=<?php echo urlencode($shareImage); ?>&title=<?php echo urlencode($shareTitle); ?>&description=<?php echo urlencode($shareDescription); ?>" 
                           class="share-popup btn btn-sm" 
                           style="background:#3b5998;color:#fff" 
                           target="_blank" 
                           rel="noopener">
                            <i class="bi bi-facebook"></i> Share
                        </a>
                        
                        <!-- Twitter Share -->
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($postUrl); ?>&text=<?php echo urlencode($post['title']); ?>&via=trendypost" 
                           class="share-popup btn btn-sm" 
                           style="background:#1da1f2;color:#fff" 
                           target="_blank" 
                           rel="noopener">
                            <i class="bi bi-twitter-x"></i> Tweet
                        </a>
                        
                        <!-- LinkedIn Share -->
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode($postUrl); ?>&title=<?php echo urlencode($shareTitle); ?>&summary=<?php echo urlencode($shareDescription); ?>" 
                           class="share-popup btn btn-sm" 
                           style="background:#0077b5;color:#fff" 
                           target="_blank" 
                           rel="noopener">
                            <i class="bi bi-linkedin"></i> Share
                        </a>
                        
                        <!-- WhatsApp Share -->
                        <a href="https://wa.me/?text=<?php echo urlencode($shareTitle . ' - ' . $postUrl); ?>" 
                           class="share-popup btn btn-sm" 
                           style="background:#25d366;color:#fff" 
                           target="_blank" 
                           rel="noopener">
                            <i class="bi bi-whatsapp"></i> Share
                        </a>
                        
                        <!-- Copy Link -->
                        <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('<?php echo $postUrl; ?>')" title="Copy link to clipboard">
                            <i class="bi bi-link"></i>
                        </button>
                    </div>
                    
                    <!-- Table of Contents -->
                    <div id="tableOfContents" class="d-none mb-4 p-4 bg-light rounded"></div>
                    
                    <!-- Ad Before Content -->
                    <?php $beforeAds = get_ads('before_content', 1); if (!empty($beforeAds)): ?>
                    <div class="ad-in-content mb-4">
                        <div class="ad-label">Advertisement</div>
                        <?php echo render_ad($beforeAds[0]); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Hidden ad data for JS injection -->
                    <?php if (!empty($adAfter2)): ?>
                    <div data-ad-after-2 style="display:none"><?php echo render_ad($adAfter2[0]); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($adAfter5)): ?>
                    <div data-ad-after-5 style="display:none"><?php echo render_ad($adAfter5[0]); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($adInAuto)): ?>
                    <div data-ad-after-3 style="display:none"><?php echo render_ad($adInAuto[0]); ?></div>
                    <?php endif; ?>
                    
                    <!-- Post Content -->
                    <div class="post-content">
                        <?php echo $post['content']; ?>
                    </div>
                    
                    <!-- Ad After Content -->
                    <?php $afterAds = get_ads('after_content', 1); if (!empty($afterAds)): ?>
                    <div class="ad-in-content mt-4">
                        <div class="ad-label">Advertisement</div>
                        <?php echo render_ad($afterAds[0]); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Tags -->
                    <?php if (!empty($post['tags'])): ?>
                    <div class="post-tags mt-4 pt-3 border-top">
                        <h6 class="d-inline me-2"><i class="bi bi-tags"></i> Tags:</h6>
                        <?php foreach ($post['tags'] as $tag): ?>
                        <a href="<?php echo tag_url($tag['slug']); ?>" class="badge bg-light text-dark text-decoration-none me-1"><?php echo $tag['name']; ?></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Post Navigation -->
                    <div class="post-navigation">
                        <?php if ($prevPost): ?>
                        <a href="<?php echo post_url($prevPost['slug']); ?>">
                            <i class="bi bi-arrow-left"></i>
                            <span><small class="d-block text-muted">Previous</small><?php echo truncate($prevPost['title'], 50); ?></span>
                        </a>
                        <?php else: ?><span></span><?php endif; ?>
                        
                        <?php if ($nextPost): ?>
                        <a href="<?php echo post_url($nextPost['slug']); ?>" class="text-end">
                            <span><small class="d-block text-muted">Next</small><?php echo truncate($nextPost['title'], 50); ?></span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </article>
                
                <!-- Author Box -->
                <?php if (Setting::get('show_author_box', 1) && $post['author_name']): ?>
                <div class="card mt-4 border-0 shadow-sm">
                    <div class="card-body d-flex gap-4 align-items-start p-4">
                        <?php if ($post['author_avatar']): ?>
                        <img src="<?php echo upload_url($post['author_avatar']); ?>" alt="<?php echo esc_attr($post['author_name']); ?>" class="rounded-circle" width="80" height="80" style="object-fit:cover">
                        <?php else: ?>
                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:80px;height:80px;background-color:var(--tp-primary)!important;font-size:28px;">
                            <?php echo strtoupper(substr($post['author_name'], 0, 1)); ?>
                        </div>
                        <?php endif; ?>
                        <div>
                            <h5 class="mb-1">
                                <a href="<?php echo author_url($post['username'] ?? ''); ?>"><?php echo $post['author_name']; ?></a>
                            </h5>
                            <p class="text-muted mb-2" style="font-size:14px;"><?php echo $post['author_bio'] ?? 'Author at ' . Setting::get('site_name', 'TrendyPost'); ?></p>
                            <div class="social-links">
                                <a href="#" class="text-muted me-2"><i class="bi bi-facebook"></i></a>
                                <a href="#" class="text-muted me-2"><i class="bi bi-twitter-x"></i></a>
                                <a href="#" class="text-muted"><i class="bi bi-linkedin"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Related Posts -->
                <?php if (Setting::get('show_related_posts', 1) && !empty($relatedPosts)): ?>
                <section class="related-posts">
                    <h3>Related Posts</h3>
                    <div class="row">
                        <?php foreach ($relatedPosts as $rp): 
                            $rpImg = media_url($rp['featured_image']);
                        ?>
                        <div class="col-md-4">
                            <article class="post-card">
                                <div class="card-img-wrapper" style="padding-top: 60%;">
                                    <img src="<?php echo $rpImg; ?>" alt="<?php echo esc_attr($rp['title']); ?>" loading="lazy">
                                </div>
                                <div class="card-body" style="padding: 15px;">
                                    <h6 class="card-title" style="font-size: 14px;">
                                        <a href="<?php echo post_url($rp['slug']); ?>"><?php echo truncate($rp['title'], 60); ?></a>
                                    </h6>
                                    <div class="post-meta" style="font-size: 11px;">
                                        <span><i class="bi bi-calendar3"></i> <?php echo format_date($rp['published_at']); ?></span>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Comments Section -->
                <section class="comments-section">
                    <h3><i class="bi bi-chat-square-text"></i> <?php echo $commentCount; ?> Comments</h3>
                    
                    <div id="commentsList">
                        <?php if (empty($comments)): ?>
                        <div class="text-muted py-3">No comments yet. Be the first to share your thoughts!</div>
                        <?php else: ?>
                        <?php foreach ($comments as $comment): 
                            $avatar = $comment['avatar'] ?? image_url('default-avatar.png');
                        ?>
                        <div class="comment-item" id="comment-<?php echo $comment['id']; ?>">
                            <img src="<?php echo $avatar; ?>" alt="" class="comment-avatar" onerror="this.src='<?php echo image_url('default-avatar.png'); ?>'">
                            <div class="comment-body">
                                <div class="comment-header">
                                    <span class="comment-author"><?php echo esc($comment['display_name'] ?? $comment['author_name'] ?? 'Guest'); ?></span>
                                    <span class="comment-date"><?php echo format_date($comment['created_at'], 'M j, Y'); ?></span>
                                    <?php if ($comment['is_pinned']): ?>
                                        <span class="badge bg-warning text-dark ms-2"><i class="bi bi-pin"></i> Pinned</span>
                                    <?php endif; ?>
                                </div>
                                <p class="comment-text"><?php echo nl2br(esc($comment['content'])); ?></p>
                                <?php if (!empty($post['allow_comments'])): ?>
                                <button class="btn btn-link btn-reply p-0" data-comment-id="<?php echo $comment['id']; ?>">
                                    <i class="bi bi-reply"></i> Reply
                                </button>
                                <?php endif; ?>
                                
                                <!-- Reply Form (hidden) -->
                                <div id="reply-form-<?php echo $comment['id']; ?>" class="d-none mt-3">
                                    <form method="post" action="<?php echo api_url('comment'); ?>" class="js-reply-form" style="padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                                        <?php if (!Auth::check()): ?>
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <input type="text" name="author_name" class="form-control form-control-sm" placeholder="Your Name *" required>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <input type="email" name="author_email" class="form-control form-control-sm" placeholder="Your Email *" required>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <div class="mb-2">
                                            <textarea name="content" class="form-control" rows="3" placeholder="Write a reply..." required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-submit btn-sm">Post Reply</button>
                                    </form>
                                </div>
                                
                                <!-- Replies -->
                                <?php $replies = Comment::getReplies($comment['id']); if (!empty($replies)): ?>
                                <div class="replies mt-3 ps-4 border-start">
                                    <?php foreach ($replies as $reply): ?>
                                    <div class="comment-item mb-3 pb-3" style="border-bottom:none;">
                                        <div class="d-flex gap-3">
                                            <img src="<?php echo $reply['avatar'] ?? image_url('default-avatar.png'); ?>" alt="" class="comment-avatar" style="width:40px;height:40px;" onerror="this.src='<?php echo image_url('default-avatar.png'); ?>'">
                                            <div class="comment-body">
                                                <div class="comment-header">
                                                    <span class="comment-author"><?php echo esc($reply['display_name'] ?? $reply['author_name'] ?? 'Guest'); ?></span>
                                                    <span class="comment-date"><?php echo format_date($reply['created_at'], 'M j, Y'); ?></span>
                                                </div>
                                                <p class="comment-text"><?php echo nl2br(esc($reply['content'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Comment Form -->
                    <div class="comment-form mt-4">
                        <?php if (empty($post['allow_comments'])): ?>
                        <div class="alert alert-secondary mb-0">Comments are closed for this post.</div>
                        <?php elseif (!Auth::check() && !(string) Setting::get('allow_guest_comments', '1')): ?>
                        <div class="alert alert-info mb-0">Please <a href="<?php echo url('login'); ?>">log in</a> to leave a comment.</div>
                        <?php else: ?>
                        <h4>Leave a Comment</h4>
                        <div id="commentFeedback"></div>
                        <form id="ajaxCommentForm" method="post" action="<?php echo api_url('comment'); ?>">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <input type="hidden" name="parent_id" id="commentParentId" value="0">
                            
                            <?php if (!Auth::check()): ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Your Name <span class="text-danger">*</span></label>
                                    <input type="text" name="author_name" class="form-control" placeholder="Enter your name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Your Email <span class="text-danger">*</span></label>
                                    <input type="email" name="author_email" class="form-control" placeholder="Enter your email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Website</label>
                                <input type="url" name="author_website" class="form-control" placeholder="https://yourwebsite.com">
                            </div>
                            <?php else: ?>
                            <p class="text-muted mb-3">Commenting as <strong><?php echo Auth::user()['display_name']; ?></strong></p>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Comment <span class="text-danger">*</span></label>
                                <textarea name="content" class="form-control" rows="5" placeholder="Write your comment..." required></textarea>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-submit" style="background-color:var(--tp-primary, #00C853);color:#fff;border:none;padding:10px 30px;border-radius:8px;font-weight:600;">
                                    <i class="bi bi-send me-2"></i>Post Comment
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </section>
                
            </div>
            
            <!-- Sidebar -->
            <aside class="col-lg-4">

                <!-- Editor's Pick Widget -->
                <div class="widget editors-pick-widget">
                    <h5 class="widget-title">Editor's Pick</h5>
                    <?php 
                    $ep = Post::getPopular(1)[0] ?? null;
                    if ($ep):
                        $epImg = media_url($ep['featured_image']);
                    ?>
                    <div class="editors-pick-featured">
                        <img src="<?php echo $epImg; ?>" alt="<?php echo esc_attr($ep['title']); ?>" loading="lazy">
                        <div class="overlay">
                            <span class="category-badge"><?php echo $ep['category_name'] ?? 'Featured'; ?></span>
                            <h6><a href="<?php echo post_url($ep['slug']); ?>"><?php echo $ep['title']; ?></a></h6>
                            <div class="post-meta">
                                <span><i class="bi bi-person"></i> <?php echo $ep['author_name'] ?? 'Admin'; ?></span>
                                <span><i class="bi bi-calendar3"></i> <?php echo format_date($ep['published_at']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php foreach (array_slice(Post::getPopular(4), 1, 3) as $sPost): 
                        $spImg = media_url($sPost['featured_image']);
                    ?>
                    <div class="sidebar-post">
                        <div class="post-thumb">
                            <img src="<?php echo $spImg; ?>" alt="<?php echo esc_attr($sPost['title']); ?>" loading="lazy">
                        </div>
                        <div class="post-info">
                            <h6><a href="<?php echo post_url($sPost['slug']); ?>"><?php echo $sPost['title']; ?></a></h6>
                            <div class="post-meta"><i class="bi bi-calendar3"></i> <?php echo format_date($sPost['published_at']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Sidebar Ad -->
                <?php $sAds = get_ads('sidebar_top', 1); if (!empty($sAds)): ?>
                <div class="ad-container ad-sidebar">
                    <div class="ad-label">Advertisement</div>
                    <?php echo render_ad($sAds[0]); ?>
                </div>
                <?php endif; ?>
                <?php $sbAds = get_ads('sidebar_bottom', 1); if (!empty($sbAds)): ?>
                <div class="ad-container ad-sidebar mt-3">
                    <div class="ad-label">Advertisement</div>
                    <?php echo render_ad($sbAds[0]); ?>
                </div>
                <?php endif; ?>
                
                <!-- Most Popular Widget -->
                <div class="widget most-popular-widget">
                    <h5 class="widget-title">Most Popular</h5>
                    <?php foreach (Post::getPopular(4) as $mp): 
                        $mpImg = media_url($mp['featured_image']);
                    ?>
                    <div class="sidebar-post">
                        <div class="post-thumb">
                            <img src="<?php echo $mpImg; ?>" alt="<?php echo esc_attr($mp['title']); ?>" loading="lazy">
                        </div>
                        <div class="post-info">
                            <h6><a href="<?php echo post_url($mp['slug']); ?>"><?php echo $mp['title']; ?></a></h6>
                            <div class="post-meta"><i class="bi bi-calendar3"></i> <?php echo format_date($mp['published_at']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Newsletter Widget -->
                <div class="widget newsletter-widget">
                    <h5 class="widget-title">Newsletter</h5>
                    <p>Subscribe to our newsletter for the latest updates.</p>
                    <form id="newsletterFormSidebar">
                        <div class="input-group">
                            <input type="email" id="newsletterEmailSidebar" class="form-control" placeholder="Email Address" required>
                            <button type="submit" class="btn" style="background-color:var(--tp-primary, #00C853);color:#fff;">Subscribe</button>
                        </div>
                    </form>
                    <div id="newsletterMsgSidebar" class="mt-2"></div>
                </div>
                
                <!-- Social Networks Widget -->
                <div class="widget social-widget">
                    <h5 class="widget-title">Social Networks</h5>
                    <a href="<?php echo Setting::get('social_facebook', '#'); ?>" class="social-btn facebook" target="_blank" rel="noopener">
                        <span><i class="bi bi-facebook"></i> 983 Likes</span><span>Like our page</span>
                    </a>
                    <a href="<?php echo Setting::get('social_twitter', '#'); ?>" class="social-btn twitter" target="_blank" rel="noopener">
                        <span><i class="bi bi-twitter-x"></i> 983 Followers</span><span>Follow Us</span>
                    </a>
                    <a href="#" class="social-btn youtube" target="_blank" rel="noopener">
                        <span><i class="bi bi-youtube"></i> 983 Subscriber</span><span>Subscribe</span>
                    </a>
                    <a href="#" class="social-btn rss" target="_blank" rel="noopener">
                        <span><i class="bi bi-rss"></i> 983 Subscribe</span><span>Subscribe</span>
                    </a>
                </div>
                
                <!-- Sticky Sidebar Ad -->
                <?php $stickyAds = get_ads('sticky_sidebar', 1); if (!empty($stickyAds)): ?>
                <div class="ad-container ad-sidebar sticky-top" style="top:80px;z-index:100">
                    <div class="ad-label">Advertisement</div>
                    <?php echo render_ad($stickyAds[0]); ?>
                </div>
                <?php endif; ?>
                
            </aside>
        </div>
    </div>
</main>

<script>
// Copy to clipboard function
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            showToast('Link copied to clipboard!', 'success');
        }).catch(function() {
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    var textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    try {
        document.execCommand('copy');
        showToast('Link copied to clipboard!', 'success');
    } catch (err) {
        showToast('Failed to copy link.', 'error');
    }
    document.body.removeChild(textarea);
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger') + ' position-fixed bottom-0 end-0 m-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = message + '<button type="button" class="btn-close ms-2" data-bs-dismiss="alert"></button>';
    document.body.appendChild(toast);
    setTimeout(function() {
        toast.remove();
    }, 3000);
}

// ============================================================
// COMMENT HANDLING JAVASCRIPT
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================================
    // MAIN COMMENT FORM
    // ============================================================
    const commentForm = document.getElementById('ajaxCommentForm');
    
    if (commentForm) {
        commentForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(this);
            const data = {};
            
            // Convert FormData to JSON
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            // Get the submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Posting...';
            
            try {
                const response = await fetch('<?php echo api_url('comment'); ?>', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = JSON.parse(await response.text());
                
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                if (result.success) {
                    showAlert(commentForm, 'success', result.message);
                    commentForm.querySelector('textarea[name="content"]').value = '';
                    if (!result.moderation && result.comment) {
                        addCommentToList(result.comment);
                    }
                } else {
                    showAlert(commentForm, 'danger', result.message || 'Error posting comment. Please try again.');
                }
            } catch (error) {
                console.error('Comment Error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                showAlert(commentForm, 'danger', 'Network error. Please check your connection and try again.');
                setTimeout(() => {
                    clearAlerts(commentForm);
                }, 5000);
            }
        });
    }
    
    // ============================================================
    // REPLY FORM HANDLING
    // ============================================================
    document.querySelectorAll('.btn-reply').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const commentId = this.dataset.commentId;
            const replyForm = document.getElementById('reply-form-' + commentId);
            if (replyForm) {
                replyForm.classList.toggle('d-none');
                const textarea = replyForm.querySelector('textarea');
                if (textarea) {
                    setTimeout(() => textarea.focus(), 100);
                }
            }
        });
    });
    
    document.querySelectorAll('.js-reply-form').forEach(function(form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Posting...';
            
            try {
                const response = await fetch('<?php echo api_url('comment'); ?>', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = JSON.parse(await response.text());
                
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                if (result.success) {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show mt-2';
                    alertDiv.textContent = result.message;
                    this.parentNode.insertBefore(alertDiv, this);
                    this.querySelector('textarea[name="content"]').value = '';
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-2';
                    alertDiv.textContent = result.message || 'Error posting reply. Please try again.';
                    this.parentNode.insertBefore(alertDiv, this);
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 5000);
                }
            } catch (error) {
                console.error('Reply Error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger mt-2';
                alertDiv.textContent = 'Network error. Please try again.';
                this.parentNode.insertBefore(alertDiv, this);
            }
        });
    });
    
    // ============================================================
    // NEWSLETTER FORM
    // ============================================================
    document.getElementById('newsletterFormSidebar')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const email = document.getElementById('newsletterEmailSidebar').value;
        const msg = document.getElementById('newsletterMsgSidebar');
        try {
            const res = await fetch('<?php echo api_url('newsletter/subscribe'); ?>', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                body: JSON.stringify({email: email})
            });
            const data = JSON.parse(await res.text());
            const box = document.createElement('div');
            box.className = 'alert alert-' + (data.success ? 'success' : 'warning') + ' py-1';
            box.textContent = data.message || (data.success ? 'Subscribed.' : 'Could not subscribe.');
            msg.innerHTML = '';
            msg.appendChild(box);
            if (data.success) document.getElementById('newsletterEmailSidebar').value = '';
        } catch(err) { 
            msg.innerHTML = '<div class="alert alert-danger py-1">Error. Try again.</div>'; 
        }
    });
    
    // ============================================================
    // HELPER FUNCTIONS
    // ============================================================
    
    function showAlert(form, type, message) {
        clearAlerts(form);
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3 comment-alert`;
        const icon = document.createElement('i');
        icon.className = 'bi ' + (type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle') + ' me-2';
        alertDiv.appendChild(icon);
        alertDiv.appendChild(document.createTextNode(message));
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn-close';
        closeBtn.setAttribute('data-bs-dismiss', 'alert');
        alertDiv.appendChild(closeBtn);
        form.parentNode.insertBefore(alertDiv, form);
    }
    
    function clearAlerts(form) {
        const alerts = form.parentNode.querySelectorAll('.comment-alert');
        alerts.forEach(alert => alert.remove());
    }
    
    function addCommentToList(comment) {
        const commentsList = document.getElementById('commentsList');
        if (!commentsList) return;
        
        const noComments = commentsList.querySelector('.text-muted');
        if (noComments) noComments.remove();
        
        const commentHtml = `
            <div class="comment-item" id="comment-${comment.id}">
                <img src="<?php echo image_url('default-avatar.png'); ?>" alt="Avatar" class="comment-avatar" onerror="this.src='<?php echo image_url('default-avatar.png'); ?>'">
                <div class="comment-body">
                    <div class="comment-header">
                        <span class="comment-author">${comment.author_name}</span>
                        <span class="comment-date">Just now</span>
                    </div>
                    <p class="comment-text">${comment.content}</p>
                    <button class="btn btn-link btn-reply p-0" data-comment-id="${comment.id}">
                        <i class="bi bi-reply"></i> Reply
                    </button>
                </div>
            </div>
        `;
        
        commentsList.insertAdjacentHTML('afterbegin', commentHtml);
    }
    
    // ============================================================
    // READING PROGRESS
    // ============================================================
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const progress = (scrollTop / scrollHeight) * 100;
        const progressBar = document.getElementById('readingProgress');
        if (progressBar) {
            progressBar.style.width = progress + '%';
        }
    });
    
});
</script>

<!-- Facebook Share Meta Tags -->
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:type" content="image/jpeg">

<?php include INCLUDES_PATH . 'Footer.php'; ?>