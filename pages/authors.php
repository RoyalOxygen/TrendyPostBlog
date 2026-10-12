<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
$authors = Database::fetchAll(
    "SELECT u.username, u.display_name, u.bio, u.avatar, COUNT(p.id) as post_count
     FROM users u JOIN roles r ON u.role_id = r.id
     LEFT JOIN posts p ON p.author_id = u.id AND p.status = 'published'
     WHERE r.name IN ('super_admin','admin','editor','author','contributor') AND u.status = 'active'
     GROUP BY u.id, u.username, u.display_name, u.bio, u.avatar HAVING post_count > 0
     ORDER BY post_count DESC"
);
$pageTitle = 'Authors | ' . Setting::get('site_name', 'TrendyPost');
$seoMeta = meta_tags($pageTitle, 'Meet the authors at ' . Setting::get('site_name', 'TrendyPost'));
include INCLUDES_PATH . 'Header.php';
?>
<section class="page-header"><div class="container"><h1>Authors</h1></div></section>
<main class="main-content py-5"><div class="container">
    <div class="row g-4">
        <?php foreach ($authors as $a): ?>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5><a href="<?php echo author_url($a['username']); ?>"><?php echo esc($a['display_name'] ?: $a['username']); ?></a></h5>
                    <p class="text-muted small"><?php echo number_format((int)$a['post_count']); ?> posts</p>
                    <?php if (!empty($a['bio'])): ?><p><?php echo esc(excerpt($a['bio'], 140)); ?></p><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div></main>
<?php include INCLUDES_PATH . 'Footer.php'; ?>
