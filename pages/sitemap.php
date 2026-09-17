<?php
if (!defined('TRENDYPOST')) { define('TRENDYPOST', true); require_once __DIR__.'/../config/config.php'; require_once INCLUDES_PATH.'Database.php'; require_once INCLUDES_PATH.'Helpers.php'; require_once INCLUDES_PATH.'Models.php'; }
header('Content-Type: application/xml; charset=UTF-8');
$type = $_GET['type'] ?? '';
echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<?php if ($type === 'posts'): ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php foreach(Post::getList([], 1, 1000) as $post): ?>
<url><loc><?php echo post_url($post['slug']); ?></loc><lastmod><?php echo date('c', strtotime($post['updated_at'])); ?></lastmod><changefreq>weekly</changefreq><priority>0.8</priority><?php if($post['featured_image']): ?><image:image><image:loc><?php echo upload_url($post['featured_image']); ?></image:loc><image:title><?php echo esc_attr($post['title']); ?></image:title></image:image><?php endif; ?></url>
<?php endforeach; ?></urlset>
<?php elseif ($type === 'categories'): ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach(Category::getAll() as $cat): ?><url><loc><?php echo category_url($cat['slug']); ?></loc><changefreq>weekly</changefreq><priority>0.6</priority></url><?php endforeach; ?></urlset>
<?php elseif ($type === 'tags'): ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach(Tag::getAll() as $tag): ?><url><loc><?php echo tag_url($tag['slug']); ?></loc><changefreq>weekly</changefreq><priority>0.5</priority></url><?php endforeach; ?></urlset>
<?php elseif ($type === 'authors'): ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach(User::getAuthors() as $author): ?><url><loc><?php echo author_url($author['username']); ?></loc><changefreq>weekly</changefreq><priority>0.5</priority></url><?php endforeach; ?></urlset>
<?php elseif ($type === 'pages'): ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach(Page::getMenuPages() as $page): ?><url><loc><?php echo page_url($page['slug']); ?></loc><changefreq>monthly</changefreq><priority>0.7</priority></url><?php endforeach; ?></urlset>
<?php else: ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<sitemap><loc><?php echo url('sitemap-posts.xml'); ?></loc><lastmod><?php echo date('c'); ?></lastmod></sitemap>
<sitemap><loc><?php echo url('sitemap-categories.xml'); ?></loc><lastmod><?php echo date('c'); ?></lastmod></sitemap>
<sitemap><loc><?php echo url('sitemap-tags.xml'); ?></loc><lastmod><?php echo date('c'); ?></lastmod></sitemap>
<sitemap><loc><?php echo url('sitemap-authors.xml'); ?></loc><lastmod><?php echo date('c'); ?></lastmod></sitemap>
<sitemap><loc><?php echo url('sitemap-pages.xml'); ?></loc><lastmod><?php echo date('c'); ?></lastmod></sitemap>
</sitemapindex>
<?php endif; ?>