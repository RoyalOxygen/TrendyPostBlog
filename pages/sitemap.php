<?php
if (!defined('TRENDYPOST')) {
    define('TRENDYPOST', true);
    require_once __DIR__ . '/../config/config.php';
    require_once INCLUDES_PATH . 'Database.php';
    require_once INCLUDES_PATH . 'Helpers.php';
    require_once INCLUDES_PATH . 'Seo.php';
    require_once INCLUDES_PATH . 'Media.php';
    require_once INCLUDES_PATH . 'Models.php';
}
header('Content-Type: application/xml; charset=UTF-8');

$type = $_GET['type'] ?? '';
$siteName = (string) Setting::get('site_name', 'TrendyPost');

/**
 * Render one <image:image> block. The post title is always the image title
 * so every image is associated with the article ranking for that title.
 */
$imageBlock = static function (array $image, string $postTitle): string {
    $loc = trim((string) ($image['url'] ?? ''));
    if ($loc === '') {
        return '';
    }
    $caption = trim((string) ($image['caption'] ?? ''));
    if ($caption === '') {
        $caption = trim((string) ($image['alt'] ?? ''));
    }
    $xml = '<image:image><image:loc>' . esc_attr($loc) . '</image:loc>';
    $xml .= '<image:title>' . esc_attr($postTitle) . '</image:title>';
    if ($caption !== '' && strcasecmp($caption, $postTitle) !== 0) {
        $xml .= '<image:caption>' . esc_attr($caption) . '</image:caption>';
    }
    return $xml . '</image:image>';
};

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<?php if ($type === 'posts'): ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<url><loc><?php echo esc_attr(rtrim(BASE_URL, '/')); ?>/</loc><lastmod><?php echo date('c'); ?></lastmod><changefreq>hourly</changefreq><priority>1.0</priority></url>
<?php foreach (Post::getList([], 1, 5000) as $post): ?>
<url><loc><?php echo esc_attr(post_url($post['slug'])); ?></loc><lastmod><?php echo date('c', strtotime($post['updated_at'] ?? $post['published_at'] ?? 'now')); ?></lastmod><changefreq>daily</changefreq><priority>0.9</priority><?php foreach (array_slice(Seo::postImages($post), 0, 5) as $img) { echo $imageBlock($img, (string) $post['title']); } ?></url>
<?php endforeach; ?></urlset>
<?php elseif ($type === 'images'): ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php foreach (Post::getList([], 1, 5000) as $post): $images = Seo::postImages($post); if (empty($images)) { continue; } ?>
<url><loc><?php echo esc_attr(post_url($post['slug'])); ?></loc><?php foreach (array_slice($images, 0, 10) as $img) { echo $imageBlock($img, (string) $post['title']); } ?></url>
<?php endforeach; ?></urlset>
<?php elseif ($type === 'news'): ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php
$newsPosts = Database::fetchAll(
    "SELECT id, title, slug, featured_image, featured_image_alt, featured_image_caption, content, published_at
     FROM posts
     WHERE status = 'published'
       AND published_at IS NOT NULL
       AND published_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)
       AND UNIX_TIMESTAMP(published_at) <= UNIX_TIMESTAMP(NOW())
     ORDER BY published_at DESC LIMIT 1000"
);
foreach ($newsPosts as $post): ?>
<url><loc><?php echo esc_attr(post_url($post['slug'])); ?></loc>
<news:news>
<news:publication><news:name><?php echo esc_attr($siteName); ?></news:name><news:language><?php echo esc_attr(APP_LOCALE); ?></news:language></news:publication>
<news:publication_date><?php echo date('c', strtotime($post['published_at'])); ?></news:publication_date>
<news:title><?php echo esc_attr($post['title']); ?></news:title>
</news:news>
<?php foreach (array_slice(Seo::postImages($post), 0, 5) as $img) { echo $imageBlock($img, (string) $post['title']); } ?>
</url>
<?php endforeach; ?></urlset>
<?php elseif ($type === 'categories'): ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach (Category::getAll() as $cat): ?><url><loc><?php echo esc_attr(category_url($cat['slug'])); ?></loc><lastmod><?php echo date('c'); ?></lastmod><changefreq>weekly</changefreq><priority>0.6</priority></url><?php endforeach; ?></urlset>
<?php elseif ($type === 'tags'): ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach (Tag::getAll() as $tag): ?><url><loc><?php echo esc_attr(tag_url($tag['slug'])); ?></loc><lastmod><?php echo date('c'); ?></lastmod><changefreq>weekly</changefreq><priority>0.5</priority></url><?php endforeach; ?></urlset>
<?php elseif ($type === 'authors'): ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach (User::getAuthors() as $author): ?><url><loc><?php echo esc_attr(author_url($author['username'])); ?></loc><lastmod><?php echo date('c'); ?></lastmod><changefreq>weekly</changefreq><priority>0.5</priority></url><?php endforeach; ?></urlset>
<?php elseif ($type === 'pages'): ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php foreach (Page::getPublished() as $page): ?><url><loc><?php echo esc_attr(page_url($page['slug'])); ?></loc><lastmod><?php echo date('c', strtotime($page['updated_at'] ?? $page['created_at'] ?? 'now')); ?></lastmod><changefreq>monthly</changefreq><priority>0.7</priority><?php if (!empty($page['featured_image'])) { echo $imageBlock(['url' => Seo::absolute(media_url($page['featured_image'], ''))], (string) $page['title']); } ?></url><?php endforeach; ?></urlset>
<?php else: ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<sitemap><loc><?php echo esc_attr(url('sitemap-posts.xml')); ?></loc><lastmod><?php echo date('c'); ?></lastmod></sitemap>
<sitemap><loc><?php echo esc_attr(url('sitemap-images.xml')); ?></loc><lastmod><?php echo date('c'); ?></lastmod></sitemap>
<sitemap><loc><?php echo esc_attr(url('sitemap-news.xml')); ?></loc><lastmod><?php echo date('c'); ?></lastmod></sitemap>
<sitemap><loc><?php echo esc_attr(url('sitemap-categories.xml')); ?></loc><lastmod><?php echo date('c'); ?></lastmod></sitemap>
<sitemap><loc><?php echo esc_attr(url('sitemap-tags.xml')); ?></loc><lastmod><?php echo date('c'); ?></lastmod></sitemap>
<sitemap><loc><?php echo esc_attr(url('sitemap-authors.xml')); ?></loc><lastmod><?php echo date('c'); ?></lastmod></sitemap>
<sitemap><loc><?php echo esc_attr(url('sitemap-pages.xml')); ?></loc><lastmod><?php echo date('c'); ?></lastmod></sitemap>
</sitemapindex>
<?php endif; ?>
