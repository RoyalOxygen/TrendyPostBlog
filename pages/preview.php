<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$id = intval($_GET['id'] ?? 0);
$post = Post::getById($id);
if (!$post) {
    include PAGES_PATH . 'errors/404.php';
    return;
}
$canPreview = Auth::isEditor() || (Auth::check() && (int) (Auth::user()['id'] ?? 0) === (int) ($post['author_id'] ?? 0));
if (!$canPreview) {
    http_response_code(403);
    echo '<p>Preview requires an editor account.</p>';
    exit;
}
$pageTitle = 'Preview: ' . $post['title'];
$seoMeta = meta_tags($pageTitle, excerpt($post['excerpt'] ?? $post['content'], 160), '', 'noindex,nofollow');
include INCLUDES_PATH . 'Header.php';
?>
<main class="main-content py-4">
    <div class="container">
        <div class="alert alert-warning">Draft preview — not public.</div>
        <article class="single-post">
            <h1><?php echo esc($post['title']); ?></h1>
            <p class="text-muted"><?php echo esc($post['status']); ?> &middot; <?php echo esc($post['author_name'] ?? ''); ?></p>
            <?php $previewImg = media_url($post['featured_image'] ?? '', ''); if ($previewImg !== ''): ?>
            <p><img src="<?php echo $previewImg; ?>" alt="<?php echo esc_attr($post['title']); ?>" class="img-fluid"></p>
            <?php endif; ?>
            <div class="post-content"><?php echo $post['content']; ?></div>
        </article>
    </div>
</main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>
