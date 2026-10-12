<?php

if (!defined('TRENDYPOST')) {
    die('Direct access not permitted.');
}

class PublishSeo {

    public static function enhancePost(int $postId, bool $force = false): array {
        $post = Post::getById($postId);
        if (!$post) {
            throw new RuntimeException('Post not found for AI rewrite.');
        }

        $meta = Post::getMeta($postId);
        $already = !empty($meta['seo_enhanced_at']);
        if ($already && !$force) {
            $update = self::fillSocialDefaults($post);
            if ($update) {
                Database::update('posts', $update, 'id = ?', [$postId]);
                $post = array_merge($post, $update);
            }
            return $post;
        }

        if (!class_exists('GeminiClient') || !GeminiClient::isConfigured()) {
            throw new RuntimeException('Gemini is not configured. Add at least one API key under AI Sources before using AI rewrite.');
        }

        $ai = self::requestAi($post);
        if (!is_array($ai) || empty($ai['title']) || empty($ai['content'])) {
            $err = class_exists('GeminiClient') ? GeminiClient::lastError() : '';
            throw new RuntimeException(
                'AI rewrite failed' . ($err !== '' ? ': ' . $err : '. Gemini returned an empty or invalid response. Check your API keys and try again.')
            );
        }

        $pack = self::buildPack($post, $ai);

        $content = self::stripFaqBlocks($pack['content']);
        $content = self::injectInternalLinks(
            $content,
            $postId,
            (int) ($post['category_id'] ?? 0),
            $pack['focus_keyword']
        );

        $slugSource = $pack['slug'] !== '' ? $pack['slug'] : $pack['title'];
        $slug = unique_slug('posts', create_slug($slugSource), $postId);

        $update = [
            'title' => substr($pack['title'], 0, 255),
            'slug' => $slug,
            'excerpt' => substr($pack['excerpt'], 0, 500),
            'content' => $content,
            'featured_image_alt' => substr($pack['featured_image_alt'], 0, 255),
            'featured_image_caption' => substr($pack['featured_image_caption'], 0, 255),
            'seo_title' => substr($pack['seo_title'], 0, 70),
            'seo_description' => substr($pack['seo_description'], 0, 160),
            'seo_keywords' => substr($pack['seo_keywords'], 0, 255),
            'focus_keyword' => substr($pack['focus_keyword'], 0, 100),
            'secondary_keywords' => substr($pack['secondary_keywords'], 0, 255),
            'meta_robots' => 'index,follow',
            'schema_type' => 'NewsArticle',
            'canonical_url' => '',
            'og_title' => substr($pack['og_title'], 0, 200),
            'og_description' => substr($pack['og_description'], 0, 300),
            'og_image' => $pack['og_image'],
            'twitter_card' => 'summary_large_image',
            'twitter_title' => substr($pack['twitter_title'], 0, 200),
            'twitter_description' => substr($pack['twitter_description'], 0, 300),
            'twitter_image' => $pack['twitter_image'],
            'reading_time' => reading_time($content),
            'word_count' => str_word_count(strip_tags($content)),
            'article_section' => substr($pack['news_category'], 0, 100),
            'seo_score' => 85,
            'keyword_density' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        Database::update('posts', $update, 'id = ?', [$postId]);
        self::upsertMeta($postId, 'seo_enhanced_at', date('Y-m-d H:i:s'));
        self::upsertMeta($postId, 'seo_enhanced_ai', '1');
        self::syncCustomMeta($postId, $pack);
        self::syncTags($postId, $pack['seo_keywords'] . ',' . $pack['secondary_keywords'], $pack['focus_keyword']);

        return array_merge($post, $update, ['id' => $postId]);
    }

    private static function requestAi(array $post): ?array {
        if (!class_exists('GeminiClient') || !GeminiClient::isConfigured()) {
            return null;
        }
        $body = substr(trim(preg_replace('/\s+/', ' ', strip_tags((string) $post['content']))), 0, 7000);
        $prompt = "Below is a fully rewritten, SEO-optimized version for TrendyPost.\n"
            . "Rewrite this news article into original wording. Keep facts, names, dates, and numbers accurate.\n"
            . "Return ONLY JSON with these keys:\n"
            . "title (news headline, 50-90 chars),\n"
            . "slug (lowercase hyphenated URL slug),\n"
            . "excerpt (1-2 sentences),\n"
            . "featured_image_alt,\n"
            . "featured_image_caption,\n"
            . "content (HTML using <p> and <h2> only; 5-8 short sections; no FAQ block),\n"
            . "seo_title (50-60 chars),\n"
            . "focus_keyword,\n"
            . "seo_description (140-160 chars),\n"
            . "seo_keywords (comma-separated names and topics),\n"
            . "secondary_keywords (comma-separated related phrases),\n"
            . "og_title,\n"
            . "og_description,\n"
            . "twitter_title,\n"
            . "twitter_description,\n"
            . "article_type (e.g. Politics),\n"
            . "news_category (e.g. National Politics),\n"
            . "country,\n"
            . "content_type (News),\n"
            . "trending_topic.\n"
            . "Do not copy the source wording. Do not include a preamble. Do not add FAQ, Frequently Asked Questions, or Q&A sections.\n\n"
            . "Current title: {$post['title']}\n"
            . "Current excerpt: " . ($post['excerpt'] ?? '') . "\n"
            . "Source body:\n{$body}";
        return GeminiClient::generateJson($prompt, ['max_tokens' => 8192]);
    }

    private static function buildPack(array $post, array $ai): array {
        $title = self::str($ai['title'] ?? '', 255) ?: (string) $post['title'];
        $excerpt = self::str($ai['excerpt'] ?? '', 500);
        if ($excerpt === '') {
            $excerpt = excerpt((string) ($post['excerpt'] ?? $post['content'] ?? $title), 220);
        }
        $content = trim((string) ($ai['content'] ?? ''));
        if ($content === '' || stripos($content, '<p') === false) {
            $content = (string) $post['content'];
        }
        $content = self::sanitizeContent($content);

        $focus = self::str($ai['focus_keyword'] ?? '', 100);
        if ($focus === '') {
            $focus = self::guessFocus($title);
        }
        $seoTitle = self::str($ai['seo_title'] ?? '', 70) ?: substr($title, 0, 70);
        $seoDesc = self::str($ai['seo_description'] ?? '', 160);
        if ($seoDesc === '') {
            $seoDesc = substr(excerpt($excerpt, 160), 0, 160);
        }
        $keywords = self::listToCsv($ai['seo_keywords'] ?? '');
        if ($keywords === '') {
            $keywords = $focus;
        }
        $secondary = self::listToCsv($ai['secondary_keywords'] ?? '');
        $featured = (string) ($post['featured_image'] ?? '');
        $ogImage = (string) ($post['og_image'] ?: $featured);
        $twImage = (string) ($post['twitter_image'] ?: $ogImage);

        $alt = self::str($ai['featured_image_alt'] ?? '', 255);
        if ($alt === '') {
            $alt = $title;
        }
        $caption = self::str($ai['featured_image_caption'] ?? '', 255);
        if ($caption === '') {
            $caption = $title;
        }

        return [
            'title' => $title,
            'slug' => self::str($ai['slug'] ?? '', 180),
            'excerpt' => $excerpt,
            'content' => $content,
            'featured_image_alt' => $alt,
            'featured_image_caption' => $caption,
            'seo_title' => $seoTitle,
            'seo_description' => $seoDesc,
            'seo_keywords' => $keywords,
            'focus_keyword' => $focus,
            'secondary_keywords' => $secondary,
            'og_title' => self::str($ai['og_title'] ?? '', 200) ?: $title,
            'og_description' => self::str($ai['og_description'] ?? '', 300) ?: $seoDesc,
            'og_image' => $ogImage,
            'twitter_title' => self::str($ai['twitter_title'] ?? '', 200) ?: $title,
            'twitter_description' => self::str($ai['twitter_description'] ?? '', 300) ?: $seoDesc,
            'twitter_image' => $twImage,
            'article_type' => self::str($ai['article_type'] ?? '', 80) ?: 'News',
            'news_category' => self::str($ai['news_category'] ?? '', 100) ?: ((string) ($post['article_section'] ?? 'News')),
            'country' => self::str($ai['country'] ?? '', 80) ?: 'Nigeria',
            'content_type' => self::str($ai['content_type'] ?? '', 40) ?: 'News',
            'trending_topic' => self::str($ai['trending_topic'] ?? '', 120) ?: $focus,
        ];
    }

    private static function fillSocialDefaults(array $post): array {
        $update = [];
        $title = (string) $post['title'];
        $desc = (string) ($post['seo_description'] ?: excerpt($post['excerpt'] ?? $post['content'] ?? '', 160));
        $featured = (string) ($post['featured_image'] ?? '');
        if (trim((string) ($post['og_title'] ?? '')) === '') {
            $update['og_title'] = substr($title, 0, 200);
        }
        if (trim((string) ($post['og_description'] ?? '')) === '') {
            $update['og_description'] = substr($desc, 0, 300);
        }
        if (trim((string) ($post['og_image'] ?? '')) === '' && $featured !== '') {
            $update['og_image'] = $featured;
        }
        if (trim((string) ($post['twitter_title'] ?? '')) === '') {
            $update['twitter_title'] = substr($title, 0, 200);
        }
        if (trim((string) ($post['twitter_description'] ?? '')) === '') {
            $update['twitter_description'] = substr($desc, 0, 300);
        }
        if (trim((string) ($post['twitter_image'] ?? '')) === '') {
            $update['twitter_image'] = (string) ($post['og_image'] ?: $featured);
        }
        if (trim((string) ($post['twitter_card'] ?? '')) === '') {
            $update['twitter_card'] = 'summary_large_image';
        }
        if (trim((string) ($post['schema_type'] ?? '')) === '') {
            $update['schema_type'] = 'NewsArticle';
        }
        if (trim((string) ($post['meta_robots'] ?? '')) === '') {
            $update['meta_robots'] = 'index,follow';
        }
        return $update;
    }

    public static function stripFaqBlocks(string $html): string {
        $html = preg_replace('#<div[^>]*class="[^"]*tp-faq-block[^"]*"[^>]*>.*?</div>#is', '', $html) ?? $html;
        $html = preg_replace('#<(h2|h3)[^>]*>\s*Frequently Asked Questions\s*</\1>.*?(?=<h2\b|$)#is', '', $html) ?? $html;
        $html = preg_replace('#<(h2|h3)[^>]*>\s*(What is this story about\?|Why does this matter\?|Where can I read the full report\?)\s*</\1>\s*<p[^>]*>.*?</p>#is', '', $html) ?? $html;
        return trim($html);
    }

    private static function sanitizeContent(string $html): string {
        $html = self::stripFaqBlocks($html);
        $html = preg_replace('#<(script|style|iframe)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $allowed = '<p><h2><h3><ul><ol><li><strong><em><a><blockquote><br>';
        $html = strip_tags($html, $allowed);
        $html = preg_replace('/\s+/', ' ', $html) ?? $html;
        $html = preg_replace('#<(h2|h3|p|li|blockquote)>\s*</\1>#i', '', $html) ?? $html;
        return trim($html);
    }

    private static function str($value, int $max): string {
        if (is_array($value)) {
            $value = implode(', ', $value);
        }
        $value = trim(html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8'));
        if ($value === '') {
            return '';
        }
        return mb_substr($value, 0, $max);
    }

    private static function listToCsv($value): string {
        if (is_array($value)) {
            $value = implode(', ', array_filter(array_map('trim', $value)));
        }
        return self::str($value, 255);
    }

    private static function guessFocus(string $title): string {
        $words = preg_split('/\s+/', trim($title)) ?: [];
        $words = array_values(array_filter($words, fn($w) => mb_strlen($w) > 2));
        return substr(implode(' ', array_slice($words, 0, 4)), 0, 80);
    }

    private static function injectInternalLinks(string $content, int $postId, int $categoryId, string $focus): string {
        if (substr_count(strtolower($content), '<a ') >= 4) {
            return $content;
        }
        $related = [];
        if ($categoryId > 0) {
            $related = Post::getRelated($postId, $categoryId, 6);
        }
        if (count($related) < 2) {
            $related = array_merge($related, Post::getLatest(8));
        }
        $seen = [$postId => true];
        $injected = 0;
        foreach ($related as $item) {
            $id = (int) ($item['id'] ?? 0);
            if (!$id || isset($seen[$id]) || empty($item['slug']) || empty($item['title'])) {
                continue;
            }
            $seen[$id] = true;
            $anchor = trim((string) $item['title']);
            if (mb_strlen($anchor) < 8) {
                continue;
            }
            $url = post_url($item['slug']);
            $escaped = preg_quote($anchor, '/');
            $pattern = '/(?![^<]*>)(' . $escaped . ')/iu';
            $replacement = '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">$1</a>';
            $next = preg_replace($pattern, $replacement, $content, 1, $count);
            if ($count) {
                $content = $next;
                $injected++;
            } elseif ($injected === 0 && $focus !== '' && stripos(strip_tags($content), $focus) !== false) {
                $fesc = preg_quote($focus, '/');
                $content = preg_replace(
                    '/(?![^<]*>)(' . $fesc . ')/iu',
                    '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">$1</a>',
                    $content,
                    1
                );
                $injected++;
            }
            if ($injected >= 3) {
                break;
            }
        }
        if ($injected === 0) {
            $items = [];
            foreach ($related as $item) {
                $id = (int) ($item['id'] ?? 0);
                if (!$id || $id === $postId || empty($item['slug']) || empty($item['title'])) {
                    continue;
                }
                $items[] = '<li><a href="' . htmlspecialchars(post_url($item['slug']), ENT_QUOTES, 'UTF-8') . '">'
                    . htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') . '</a></li>';
                if (count($items) >= 3) {
                    break;
                }
            }
            if ($items) {
                $content .= '<div class="tp-related-reads"><h2>Related reading</h2><ul>' . implode('', $items) . '</ul></div>';
            }
        }
        return $content;
    }

    private static function upsertMeta(int $postId, string $key, string $value): void {
        $exists = Database::fetchOne("SELECT id FROM post_meta WHERE post_id = ? AND meta_key = ?", [$postId, $key]);
        if ($exists) {
            Database::update('post_meta', ['meta_value' => $value], 'post_id = ? AND meta_key = ?', [$postId, $key]);
        } else {
            Database::insert('post_meta', ['post_id' => $postId, 'meta_key' => $key, 'meta_value' => $value]);
        }
    }

    private static function syncCustomMeta(int $postId, array $pack): void {
        $minutes = max(1, (int) reading_time($pack['content'] ?? ''));
        $fields = [
            'article_type' => $pack['article_type'],
            'news_category' => $pack['news_category'],
            'country' => $pack['country'],
            'reading_time' => $minutes . ' min',
            'content_type' => $pack['content_type'],
            'trending_topic' => $pack['trending_topic'],
            'seo_score' => 'High',
            'keyword_density' => 'Optimized',
        ];
        foreach ($fields as $key => $value) {
            self::upsertMeta($postId, $key, (string) $value);
        }
    }

    private static function syncTags(int $postId, string $keywords, string $focus): void {
        $names = [];
        foreach (explode(',', $keywords . ',' . $focus) as $name) {
            $name = trim($name);
            if ($name === '' || mb_strlen($name) < 3) {
                continue;
            }
            $names[mb_strtolower($name)] = substr($name, 0, 80);
            if (count($names) >= 8) {
                break;
            }
        }
        foreach ($names as $name) {
            $slug = create_slug($name);
            $tag = Database::fetchOne("SELECT id FROM tags WHERE slug = ? OR name = ? LIMIT 1", [$slug, $name]);
            if ($tag) {
                $tagId = (int) $tag['id'];
            } else {
                $tagId = Database::insert('tags', [
                    'name' => $name,
                    'slug' => unique_slug('tags', $slug),
                    'status' => 'active',
                ]);
            }
            $linked = Database::fetchOne("SELECT post_id FROM post_tags WHERE post_id = ? AND tag_id = ?", [$postId, $tagId]);
            if (!$linked) {
                Database::insert('post_tags', ['post_id' => $postId, 'tag_id' => $tagId]);
            }
        }
    }
}
