<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$slug = $_GET['slug'] ?? '';
$tag = Tag::getBySlug($slug);
if (!$tag) { http_response_code(404); include PAGES_PATH . 'errors/404.php'; exit; }
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = POSTS_PER_PAGE;
$posts = Post::getList(['tag' => $slug], $page, $perPage);
$total = Post::count(['tag' => $slug]);
$pageTitle = $tag['name'] . ' | ' . Setting::get('site_name');
$pageDescription = $tag['seo_description'] ?? 'Articles tagged with ' . $tag['name'];
$seoMeta = meta_tags($pageTitle, $pageDescription, $tag['seo_keywords'] ?? '');
$canonicalUrl = canonical_url('tag/' . $slug);
include INCLUDES_PATH . 'Header.php';
?>
<section class="page-header">
    <div class="container"><h1><i class="bi bi-tag"></i> <?php echo $tag['name']; ?></h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?php echo url(); ?>">Home</a></li><li class="breadcrumb-item active"><?php echo $tag['name']; ?></li></ol></nav></div>
</section>
<main class="main-content py-4"><div class="container"><div class="row"><div class="col-lg-8">
    <div class="d-flex justify-content-between align-items-center mb-4"><h5 class="mb-0"><?php echo $total; ?> Article<?php echo $total!==1?'s':''; ?></h5></div>
    <?php if(empty($posts)): ?><div class="alert alert-info">No articles found.</div>
    <?php else: foreach($posts as $post): $pImg = $post['featured_image']?upload_url($post['featured_image']):image_url('placeholder.jpg'); ?>
    <article class="post-card horizontal-post mb-4"><div class="row g-0"><div class="col-md-4"><div class="card-img-wrapper" style="padding-top:75%"><img src="<?php echo $pImg; ?>" alt="" loading="lazy"></div></div><div class="col-md-8"><div class="card-body"><h5 class="card-title"><a href="<?php echo post_url($post['slug']); ?>"><?php echo $post['title']; ?></a></h5><p class="card-text"><?php echo excerpt($post['excerpt']??$post['content'],160); ?></p><div class="post-meta"><i class="bi bi-calendar3"></i> <?php echo format_date($post['published_at']); ?></div></div></div></div></article>
    <?php endforeach; echo pagination($total,$perPage,$page,tag_url($slug)); endif; ?>
</div><aside class="col-lg-4">
    <div class="widget"><h5 class="widget-title">Popular Tags</h5><div class="tag-cloud">
        <?php foreach(Tag::getPopular(20) as $t): ?><a href="<?php echo tag_url($t['slug']); ?>" class="tag-item"><?php echo $t['name']; ?></a><?php endforeach; ?>
    </div></div>
</aside></div></div></main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>