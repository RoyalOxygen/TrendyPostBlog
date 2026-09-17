<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');

$year = intval($_GET['year'] ?? date('Y'));
$month = intval($_GET['month'] ?? 0);
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = POSTS_PER_PAGE;

// Fix: Add table alias 'p.' to status and published_at
$where = "p.status='published' AND YEAR(p.published_at)=?";
$params = [$year];
if($month){ 
    $where .= " AND MONTH(p.published_at)=?"; 
    $params[] = $month; 
}

$posts = Database::fetchAll(
    "SELECT p.*, u.display_name as author_name, c.name as category_name, c.slug as category_slug, c.color as category_color 
     FROM posts p 
     LEFT JOIN users u ON p.author_id = u.id 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE {$where} 
     ORDER BY p.published_at DESC 
     LIMIT ? OFFSET ?", 
    array_merge($params, [$perPage, ($page - 1) * $perPage])
);

$total = Database::fetchColumn(
    "SELECT COUNT(*) FROM posts p WHERE {$where}", 
    $params
);

$title = $month ? date('F Y', mktime(0,0,0,$month,1,$year)) : $year;
$pageTitle = "Archive: $title | " . Setting::get('site_name');
$seoMeta = meta_tags($pageTitle, "Articles from $title", '');

include INCLUDES_PATH . 'Header.php';
?>

<section class="page-header">
    <div class="container">
        <h1><i class="bi bi-archive"></i> Archive: <?php echo $title; ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url(); ?>">Home</a></li>
                <li class="breadcrumb-item active"><?php echo $title; ?></li>
            </ol>
        </nav>
    </div>
</section>

<main class="main-content py-4">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h5 class="mb-4"><?php echo $total; ?> Article<?php echo $total!==1?'s':''; ?></h5>
                
                <?php if(empty($posts)): ?>
                    <div class="alert alert-info">No articles found for this period.</div>
                <?php else: 
                    foreach($posts as $post): 
                        $pImg = $post['featured_image'] ? upload_url($post['featured_image']) : image_url('placeholder.jpg'); 
                ?>
                    <article class="post-card horizontal-post mb-4">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <div class="card-img-wrapper" style="padding-top:75%">
                                    <img src="<?php echo $pImg; ?>" alt="<?php echo esc_attr($post['title']); ?>" loading="lazy">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="<?php echo post_url($post['slug']); ?>"><?php echo $post['title']; ?></a>
                                    </h5>
                                    <p class="card-text"><?php echo excerpt($post['excerpt'] ?? $post['content'], 160); ?></p>
                                    <div class="post-meta">
                                        <span><i class="bi bi-calendar3"></i> <?php echo format_date($post['published_at']); ?></span>
                                        <span><i class="bi bi-person"></i> <?php echo $post['author_name'] ?? 'Admin'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; 
                    echo pagination($total, $perPage, $page, archive_url($year, $month)); 
                endif; ?>
            </div>
            
            <aside class="col-lg-4">
                <div class="widget">
                    <h5 class="widget-title">Browse by Year</h5>
                    <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                        <a href="<?php echo archive_url($y); ?>" class="d-block py-2 <?php echo $y === $year ? 'fw-bold text-success' : ''; ?>">
                            <i class="bi bi-calendar"></i> <?php echo $y; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </aside>
        </div>
    </div>
</main>

<?php include INCLUDES_PATH . 'Footer.php'; ?>