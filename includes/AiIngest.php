<?php
/**
 * TRENDYPOST - AI Source Ingest
 * =============================
 * Visits admin-configured source websites, extracts recent articles,
 * rewrites them for SEO, and saves them as drafts in the existing posts table.
 */

if (!defined('TRENDYPOST')) {
    die('Direct access not permitted.');
}

class AiIngest {

    private const USER_AGENT = 'Mozilla/5.0 (compatible; TrendyPostBot/1.0) AppleWebKit/537.36';

    public static function log(?int $sourceId, string $message, string $level = 'info'): void {
        Database::insert('ai_source_logs', [
            'source_id' => $sourceId,
            'message' => $message,
            'level' => $level,
        ]);
    }

    public static function getSources(): array {
        return Database::fetchAll(
            "SELECT s.*,
                    (SELECT COUNT(*) FROM posts WHERE source_id = s.id) AS post_count,
                    (SELECT COUNT(*) FROM posts WHERE source_id = s.id AND status = 'draft') AS draft_count
             FROM ai_sources s
             ORDER BY s.created_at DESC"
        );
    }

    public static function getById(int $id): ?array {
        return Database::fetchOne("SELECT * FROM ai_sources WHERE id = ? LIMIT 1", [$id]);
    }

    public static function getLogs(int $sourceId, int $limit = 40): array {
        return Database::fetchAll(
            "SELECT * FROM ai_source_logs WHERE source_id = ? ORDER BY id DESC LIMIT ?",
            [$sourceId, $limit]
        );
    }

    public static function ingestDueSources(): array {
        $due = Database::fetchAll(
            "SELECT * FROM ai_sources
             WHERE enabled = 1
               AND (
                    last_checked_at IS NULL
                    OR DATE_ADD(last_checked_at, INTERVAL frequency_minutes MINUTE) <= NOW()
               )"
        );
        $results = [];
        foreach ($due as $source) {
            try {
                $results[] = array_merge(['id' => $source['id']], self::ingestSource((int) $source['id']));
            } catch (Throwable $e) {
                $results[] = ['id' => $source['id'], 'error' => $e->getMessage()];
            }
        }
        return $results;
    }

    /**
     * Ingest exactly one article from a pasted URL. No crawling, no source
     * schedule: the supplied post is extracted and saved as a draft.
     */
    public static function ingestSingleUrl(string $url, ?int $authorId = null): array {
        $url = trim($url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Enter a valid post URL (for example https://example.com/2026/10/18/story/).');
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = (string) parse_url($url, PHP_URL_HOST);
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new RuntimeException('Only public http(s) post URLs can be ingested.');
        }

        $existing = Database::fetchOne(
            "SELECT id, status FROM posts WHERE original_url = ? LIMIT 1",
            [$url]
        );
        if ($existing) {
            return [
                'created' => 0,
                'skipped' => 1,
                'errors' => 0,
                'post_id' => (int) $existing['id'],
                'message' => 'That URL was already ingested (post #' . (int) $existing['id'] . ', ' . $existing['status'] . ').',
            ];
        }

        $extracted = self::extractArticle($url);
        $draft = self::draftFromExtract($extracted);
        $extracted['cover_image'] = self::storeCoverImage($extracted['cover_image'] ?? null, $extracted['title'] ?? '');
        $draft['content'] = class_exists('Media')
            ? Media::localizeHtml((string) ($draft['content'] ?? ''), 'posts')
            : ($draft['content'] ?? '');

        $postId = self::createDraft(
            ['id' => null, 'name' => 'Single post URL'],
            $extracted,
            $draft,
            $url,
            $authorId
        );
        self::log(null, 'Single post draft created: ' . $draft['title']);

        return [
            'created' => 1,
            'skipped' => 0,
            'errors' => 0,
            'post_id' => $postId,
            'message' => 'Draft created: ' . $draft['title'],
        ];
    }

    public static function ingestSource(int $sourceId, bool $force = false): array {
        $source = self::getById($sourceId);
        if (!$source) {
            throw new RuntimeException('Source not found');
        }
        if (!$source['enabled'] && !$force) {
            return ['created' => 0, 'skipped' => 0, 'errors' => 0, 'message' => 'Source is disabled'];
        }

        $created = 0;
        $skipped = 0;
        $errors = 0;

        try {
            $links = self::discoverArticleUrls($source['url'], (int) ($source['max_posts'] ?: 5));
            self::log($sourceId, 'Found ' . count($links) . ' candidate articles on ' . $source['url']);

            foreach ($links as $link) {
                $exists = Database::fetchOne(
                    "SELECT id FROM posts WHERE original_url = ? LIMIT 1",
                    [$link['url']]
                );
                if ($exists) {
                    $skipped++;
                    continue;
                }

                try {
                    $extracted = self::extractArticle($link['url']);
                    $draft = self::draftFromExtract($extracted);
                    $extracted['cover_image'] = self::storeCoverImage($extracted['cover_image'] ?? null, $extracted['title'] ?? '');
                    $draft['content'] = class_exists('Media')
                        ? Media::localizeHtml((string) ($draft['content'] ?? ''), 'posts')
                        : ($draft['content'] ?? '');
                    self::createDraft($source, $extracted, $draft, $link['url']);
                    $created++;
                    self::log($sourceId, 'Draft created: ' . $draft['title']);
                } catch (Throwable $e) {
                    $errors++;
                    self::log($sourceId, 'Skip ' . $link['url'] . ': ' . $e->getMessage(), 'warn');
                }
            }

            $status = "ok created={$created} skipped={$skipped} errors={$errors}";
            Database::update('ai_sources', [
                'last_checked_at' => date('Y-m-d H:i:s'),
                'last_status' => $status,
            ], 'id = ?', [$sourceId]);

            return compact('created', 'skipped', 'errors') + ['message' => $status];
        } catch (Throwable $e) {
            $status = 'error: ' . $e->getMessage();
            Database::update('ai_sources', [
                'last_checked_at' => date('Y-m-d H:i:s'),
                'last_status' => $status,
            ], 'id = ?', [$sourceId]);
            self::log($sourceId, $status, 'error');
            throw $e;
        }
    }

    private static function fetchHtml(string $url): string {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml'],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $html = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($html === false || $code >= 400) {
            throw new RuntimeException('Fetch failed ' . $code . ($err ? " {$err}" : '') . ' for ' . $url);
        }
        return $html;
    }

    private static function absoluteUrl(string $href, string $base): ?string {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, 'javascript:') || str_starts_with($href, '#')) {
            return null;
        }
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }
        $baseParts = parse_url($base);
        if (!$baseParts || empty($baseParts['scheme']) || empty($baseParts['host'])) {
            return null;
        }
        $origin = $baseParts['scheme'] . '://' . $baseParts['host'] . (isset($baseParts['port']) ? ':' . $baseParts['port'] : '');
        if (str_starts_with($href, '//')) {
            return $baseParts['scheme'] . ':' . $href;
        }
        if (str_starts_with($href, '/')) {
            return $origin . $href;
        }
        $path = $baseParts['path'] ?? '/';
        $dir = preg_replace('#/[^/]*$#', '/', $path);
        return $origin . $dir . $href;
    }

    private static function sameHost(string $a, string $b): bool {
        $ha = parse_url($a, PHP_URL_HOST);
        $hb = parse_url($b, PHP_URL_HOST);
        if (!$ha || !$hb) {
            return false;
        }
        return preg_replace('/^www\./i', '', strtolower($ha)) === preg_replace('/^www\./i', '', strtolower($hb));
    }

    private static function looksLikeArticle(string $url, string $base): bool {
        if (!self::sameHost($url, $base)) {
            return false;
        }
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');
        if ($path === '/' || strlen($path) < 8) {
            return false;
        }
        $skip = [
            '/tag/', '/tags/', '/topics/', '/topic/', '/category/', '/author/',
            '/page/', '/search', '/login', '/signup', '/contact', '/about',
            '/privacy', '/terms', '/advertise', '/wp-admin', '/feed',
            '.jpg', '.png', '.gif', '.pdf', '.css', '.js',
        ];
        foreach ($skip as $needle) {
            if (str_contains($path, $needle)) {
                return false;
            }
        }
        return true;
    }

    private static function discoverArticleUrls(string $sourceUrl, int $limit = 8): array {
        $html = self::fetchHtml($sourceUrl);
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);
        $found = [];
        $seen = [];

        foreach ($xpath->query('//a[@href]') as $a) {
            $href = $a->getAttribute('href');
            $url = self::absoluteUrl($href, $sourceUrl);
            if (!$url || isset($seen[$url])) {
                continue;
            }
            if (!self::looksLikeArticle($url, $sourceUrl)) {
                continue;
            }
            $text = trim(preg_replace('/\s+/', ' ', $a->textContent ?? ''));
            if (strlen($text) < 18) {
                continue;
            }
            $seen[$url] = true;
            $found[] = ['url' => $url, 'title_hint' => substr($text, 0, 180)];
            if (count($found) >= $limit) {
                break;
            }
        }

        return $found;
    }

    private static function metaContent(DOMXPath $xpath, array $names): string {
        foreach ($names as $name) {
            $nodes = $xpath->query(
                "//meta[@property='{$name}']/@content | //meta[@name='{$name}']/@content"
            );
            if ($nodes && $nodes->length) {
                $val = decode_entities(trim($nodes->item(0)->nodeValue ?? ''));
                if ($val !== '') {
                    return $val;
                }
            }
        }
        return '';
    }

    private static function extractArticle(string $articleUrl): array {
        $html = self::fetchHtml($articleUrl);
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        foreach (['script', 'style', 'noscript', 'iframe', 'nav', 'footer', 'header', 'form', 'aside', 'svg'] as $tag) {
            $nodes = $dom->getElementsByTagName($tag);
            for ($i = $nodes->length - 1; $i >= 0; $i--) {
                $nodes->item($i)->parentNode?->removeChild($nodes->item($i));
            }
        }

        $title = self::metaContent($xpath, ['og:title', 'twitter:title']);
        if ($title === '') {
            $h1 = $xpath->query('//h1')->item(0);
            $title = $h1 ? trim($h1->textContent) : '';
        }
        if ($title === '') {
            $t = $xpath->query('//title')->item(0);
            $title = $t ? trim($t->textContent) : '';
        }

        $cover = self::metaContent($xpath, ['og:image', 'twitter:image']);
        if ($cover !== '') {
            $cover = self::absoluteUrl($cover, $articleUrl) ?: $cover;
        }
        if ($cover === '') {
            $img = $xpath->query('//article//img[@src] | //*[@itemprop="articleBody"]//img[@src] | //main//img[@src]')->item(0);
            if ($img) {
                $cover = self::absoluteUrl($img->getAttribute('src'), $articleUrl) ?: '';
            }
        }

        $containers = [
            "//*[@itemprop='articleBody']",
            "//article",
            "//*[contains(@class,'entry-content')]",
            "//*[contains(@class,'post-content')]",
            "//*[contains(@class,'article-content')]",
            "//*[contains(@class,'story-content')]",
            "//main",
        ];

        $paragraphs = [];
        foreach ($containers as $query) {
            $root = $xpath->query($query)->item(0);
            if (!$root) {
                continue;
            }
            $ps = $xpath->query('.//p', $root);
            $tmp = [];
            foreach ($ps as $p) {
                $text = trim(preg_replace('/\s+/', ' ', $p->textContent ?? ''));
                if (strlen($text) >= 40) {
                    $tmp[] = $text;
                }
            }
            if (strlen(implode("\n\n", $tmp)) > 200) {
                $paragraphs = $tmp;
                break;
            }
        }

        if (!$paragraphs) {
            foreach ($xpath->query('//p') as $p) {
                $text = trim(preg_replace('/\s+/', ' ', $p->textContent ?? ''));
                if (strlen($text) >= 40) {
                    $paragraphs[] = $text;
                }
            }
        }

        $rawText = implode("\n\n", $paragraphs);
        $title = decode_entities($title);
        $rawText = decode_entities($rawText);
        if ($title === '' || strlen($rawText) < 120) {
            throw new RuntimeException('Could not extract enough article content');
        }

        return [
            'title' => substr($title, 0, 220),
            'text' => substr($rawText, 0, 12000),
            'cover_image' => $cover ?: null,
        ];
    }

    private static function draftFromExtract(array $article): array {
        $title = decode_entities((string) ($article['title'] ?? ''));
        $paras = array_values(array_filter(array_map('trim', preg_split('/\n+/', decode_entities((string) ($article['text'] ?? ''))))));
        $html = '';
        foreach ($paras as $p) {
            $html .= '<p>' . htmlspecialchars($p, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        $excerpt = $paras[0] ?? $title;
        return [
            'title' => $title,
            'excerpt' => substr($excerpt, 0, 200),
            'category' => self::guessCategory($title . ' ' . $excerpt),
            'content' => $html,
            'seo_title' => substr($title, 0, 70),
            'seo_description' => substr($excerpt, 0, 160),
            'focus_keyword' => '',
            'seo_keywords' => '',
            'used_ai' => false,
        ];
    }

    private static function guessCategory(string $text): string {
        $t = strtolower($text);
        if (preg_match('/politic|elect|govern|senate|president/', $t)) return 'Politics';
        if (preg_match('/sport|football|premier|match|coach/', $t)) return 'Sports';
        if (preg_match('/business|market|bank|naira|economy|oil/', $t)) return 'Business';
        if (preg_match('/tech|ai |startup|app |phone|internet/', $t)) return 'Tech';
        if (preg_match('/entertain|movie|music|celebrity|nollywood/', $t)) return 'Entertainment';
        if (preg_match('/health|hospital|doctor|disease/', $t)) return 'Health';
        return 'News';
    }

    private static function storeCoverImage(?string $url, string $alt = ''): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (!class_exists('Media')) {
            return null;
        }
        $local = Media::downloadRemote($url, 'posts', $alt);
        return $local ?: null;
    }

    private static function resolveCategoryId(string $name): ?int {
        $row = Database::fetchOne(
            "SELECT id FROM categories WHERE name = ? OR slug = ? LIMIT 1",
            [$name, create_slug($name)]
        );
        return $row ? (int) $row['id'] : null;
    }

    private static function defaultAuthorId(): int {
        $row = Database::fetchOne("SELECT id FROM users WHERE role_id = 1 ORDER BY id ASC LIMIT 1");
        return $row ? (int) $row['id'] : 1;
    }

    private static function createDraft(array $source, array $extracted, array $draft, string $originalUrl, ?int $authorId = null): int {
        $title = decode_entities((string) $draft['title']);
        $content = $draft['content'];
        $excerpt = decode_entities((string) ($draft['excerpt'] ?: excerpt($content)));
        $slug = unique_slug('posts', create_slug($title));
        $categoryId = self::resolveCategoryId($draft['category'] ?? 'News');

        $postId = Database::insert('posts', [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'content' => $content,
            'featured_image' => $extracted['cover_image'] ?? null,
            'author_id' => $authorId && $authorId > 0 ? $authorId : self::defaultAuthorId(),
            'source_id' => !empty($source['id']) ? (int) $source['id'] : null,
            'original_url' => $originalUrl,
            'original_title' => $extracted['title'] ?? null,
            'category_id' => $categoryId,
            'status' => 'draft',
            'post_type' => 'standard',
            'visibility' => 'public',
            'allow_comments' => 1,
            'reading_time' => reading_time($content),
            'word_count' => str_word_count(strip_tags($content)),
            'seo_title' => $draft['seo_title'] ?: substr($title, 0, 70),
            'seo_description' => $draft['seo_description'] ?: substr($excerpt, 0, 160),
            'seo_keywords' => $draft['seo_keywords'] ?: null,
            'focus_keyword' => $draft['focus_keyword'] ?: null,
            'schema_type' => 'NewsArticle',
            'og_title' => substr($title, 0, 200),
            'og_description' => substr($excerpt, 0, 300),
            'og_image' => $extracted['cover_image'] ?? null,
            'twitter_card' => 'summary_large_image',
            'twitter_title' => substr($title, 0, 200),
            'twitter_description' => substr($excerpt, 0, 300),
            'twitter_image' => $extracted['cover_image'] ?? null,
            'article_section' => $draft['category'] ?? 'News',
        ]);

        Database::insert('post_meta', [
            'post_id' => $postId,
            'meta_key' => 'ai_source_name',
            'meta_value' => $source['name'] ?? 'Single post URL',
        ]);

        return $postId;
    }
}
