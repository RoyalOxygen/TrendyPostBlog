<?php
if (!defined('TRENDYPOST')) { define('TRENDYPOST', true); require_once __DIR__.'/../config/config.php'; require_once INCLUDES_PATH.'Database.php'; require_once INCLUDES_PATH.'Helpers.php'; require_once INCLUDES_PATH.'Models.php'; }
header('Content-Type: application/rss+xml; charset=UTF-8');
$posts = Post::getList([], 1, 20);
$siteName = Setting::get('site_name', 'TrendyPost');
echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
<title><?php echo $siteName; ?></title>
<link><?php echo BASE_URL; ?></link>
<description><?php echo Setting::get('site_tagline', 'Trending News &amp; Stories'); ?></description>
<language><?php echo APP_LOCALE; ?></language>
<lastBuildDate><?php echo date('r'); ?></lastBuildDate>
<atom:link href="<?php echo url('feed'); ?>" rel="self" type="application/rss+xml"/>
<?php foreach($posts as $post): ?>
<item>
<title><?php echo htmlspecialchars($post['title']); ?></title>
<link><?php echo post_url($post['slug']); ?></link>
<guid isPermaLink="true"><?php echo post_url($post['slug']); ?></guid>
<pubDate><?php echo date('r', strtotime($post['published_at'])); ?></pubDate>
<author><?php echo $post['author_name'] ?? 'admin'; ?></author>
<description><![CDATA[<?php echo $post['excerpt'] ?? excerpt($post['content'], 300); ?>]]></description>
</item>
<?php endforeach; ?>
</channel>
</rss>