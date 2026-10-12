<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$username = $_GET['username'] ?? '';
$author = User::getByUsername($username);
if (!$author) { http_response_code(404); include PAGES_PATH . 'errors/404.php'; exit; }
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = POSTS_PER_PAGE;
$posts = Post::getList(['author'=>$username], $page, $perPage);
$total = Post::count(['author'=>$username]);
$pageTitle = ($author['display_name'] ?? $author['username']) . ' | ' . Setting::get('site_name');
$seoMeta = meta_tags($pageTitle, $author['bio'] ?? '', '');
$canonicalUrl = canonical_url('author/' . $username);
include INCLUDES_PATH . 'Header.php';
?>
<section class="page-header"><div class="container">
    <div class="d-flex align-items-center gap-4">
        <?php if($author['avatar']): ?><img src="<?php echo upload_url($author['avatar']); ?>" alt="" class="rounded-circle" width="80" height="80" style="object-fit:cover;border:3px solid #fff">
        <?php else: ?><div class="bg-white text-success rounded-circle d-flex align-items-center justify-content-center" style="width:80px;height:80px;font-size:32px;font-weight:700"><?php echo strtoupper(substr($author['display_name']??$author['username'],0,1)); ?></div><?php endif; ?>
        <div><h1 class="mb-1"><?php echo $author['display_name'] ?? $author['username']; ?></h1>
        <p class="mb-0 text-white-75"><?php echo $author['bio'] ?? 'Author at TrendyPost'; ?></p>
        <small class="text-white-50"><i class="bi bi-file-text"></i> <?php echo $author['post_count']; ?> posts</small></div>
    </div>
</div></section>
<main class="main-content py-4"><div class="container"><div class="row"><div class="col-lg-8">
    <h5 class="mb-4">Articles by <?php echo $author['display_name'] ?? $author['username']; ?></h5>
    <?php if(empty($posts)): ?><div class="alert alert-info">No articles yet.</div>
    <?php else: foreach($posts as $post): $pImg = media_url($post['featured_image']); ?>
    <article class="post-card horizontal-post mb-4"><div class="row g-0"><div class="col-md-4"><div class="card-img-wrapper" style="padding-top:75%"><img src="<?php echo $pImg; ?>" alt="" loading="lazy"></div></div><div class="col-md-8"><div class="card-body"><h5 class="card-title"><a href="<?php echo post_url($post['slug']); ?>"><?php echo $post['title']; ?></a></h5><p class="card-text"><?php echo excerpt($post['excerpt']??$post['content'],160); ?></p><div class="post-meta"><i class="bi bi-calendar3"></i> <?php echo format_date($post['published_at']); ?></div></div></div></div></article>
    <?php endforeach; echo pagination($total,$perPage,$page,author_url($username)); endif; ?>
</div><aside class="col-lg-4">
    <div class="widget"><h5 class="widget-title">Other Authors</h5>
    <?php foreach(User::getAuthors(8) as $au): if($au['username']===$username) continue; ?>
    <div class="d-flex align-items-center gap-3 mb-3"><div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;font-size:16px;font-weight:600;flex-shrink:0;background-color:var(--tp-primary)!important"><?php echo strtoupper(substr($au['display_name']??$au['username'],0,1)); ?></div><div><a href="<?php echo author_url($au['username']); ?>" class="fw-semibold text-decoration-none"><?php echo $au['display_name']??$au['username']; ?></a><br><small class="text-muted"><?php echo $au['post_count']; ?> posts</small></div></div>
    <?php endforeach; ?></div>
</aside></div></div></main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>