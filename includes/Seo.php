<?php
/**
 * TRENDYPOST - Central SEO engine
 * ==============================
 * Builds a consistent, high-standard <head> for every public page:
 * titles, descriptions, canonical URLs, pagination links, Open Graph,
 * Twitter Cards and structured data (schema.org @graph).
 *
 * Every public page should call Seo::build() and use the returned values,
 * so titles/canonicals/schema can never drift apart again.
 */

if (!defined('TRENDYPOST')) {
    die('Direct access not permitted.');
}

class Seo {

    private static array $imageSizeCache = [];

    /**
     * Build all head values for a page.
     *
     * Supported keys:
     *  - title, description, keywords, robots (default index,follow)
     *  - canonical (absolute URL), page (current page number), base_url
     *  - image, image_alt, image_caption
     *  - type (website|article), published, modified, author, section, tags
     *  - schema_type (WebPage|CollectionPage|AboutPage|ContactPage|SearchResultsPage)
     *  - breadcrumbs [[name, url|null], ...]
     *  - related [[title, url, image|null], ...]
     *  - noindex (bool)
     *
     * @return array{pageTitle:string,pageDescription:string,canonicalUrl:string,seoMeta:string,ogTags:string,twitterTags:string,schemaMarkup:string}
     */
    public static function build(array $o): array {
        $siteName = (string) Setting::get('site_name', 'TrendyPost');
        $title = trim((string) ($o['title'] ?? ''));
        if ($title === '') {
            $title = $siteName;
        }
        $description = self::clip(preg_replace('/\s+/', ' ', strip_tags((string) ($o['description'] ?? ''))) ?? '', 160);
        $keywords = trim((string) ($o['keywords'] ?? ''));
        $robots = strtolower(trim((string) ($o['robots'] ?? 'index,follow')));
        if (!empty($o['noindex'])) {
            $robots = 'noindex,follow';
        }
        if (str_starts_with($robots, 'index') && !str_contains($robots, 'max-image-preview')) {
            $robots .= ',max-image-preview:large,max-snippet:-1,max-video-preview:-1';
        }
        $type = (string) ($o['type'] ?? 'website');
        $page = max(1, (int) ($o['page'] ?? 1));
        $canonical = trim((string) ($o['canonical'] ?? ''));
        if ($canonical === '') {
            $canonical = canonical_url();
        }
        if ($page > 1 && !empty($o['base_url'])) {
            $canonical = self::absolute($o['base_url']) . (str_contains($o['base_url'], '?') ? '&' : '?') . 'page=' . $page;
        }
        $canonical = self::stripTracking($canonical);

        $image = self::absolute((string) ($o['image'] ?? SEO_DEFAULT_IMAGE));
        $imageAlt = trim((string) ($o['image_alt'] ?? $title));
        $imageCaption = trim((string) ($o['image_caption'] ?? ''));
        $imageMeta = self::imageMeta($image);
        $logoSetting = (string) Setting::get('site_logo', defined('IMAGES_URL') ? 'assets/images/logo.png' : '');
        $logoUrl = function_exists('site_asset_url')
            ? site_asset_url($logoSetting, defined('IMAGES_URL') ? IMAGES_URL . 'logo.png' : SEO_DEFAULT_IMAGE)
            : (defined('IMAGES_URL') ? IMAGES_URL . 'logo.png' : SEO_DEFAULT_IMAGE);
        $logo = self::absolute((string) $logoUrl);
        if (!is_file(self::localPath($logo))) {
            $logo = self::absolute(SEO_DEFAULT_IMAGE);
        }

        // ---------------- meta ----------------
        $seoMeta = '<title>' . esc_attr($title) . '</title>' . PHP_EOL;
        $seoMeta .= '<meta name="description" content="' . esc_attr($description) . '">' . PHP_EOL;
        if ($keywords !== '') {
            $seoMeta .= '<meta name="keywords" content="' . esc_attr($keywords) . '">' . PHP_EOL;
        }
        $seoMeta .= '<meta name="robots" content="' . esc_attr($robots) . '">' . PHP_EOL;
        $seoMeta .= '<meta name="author" content="' . esc_attr($o['author'] ?? $siteName) . '">' . PHP_EOL;
        $seoMeta .= '<link rel="canonical" href="' . esc_attr($canonical) . '">' . PHP_EOL;
        if ($page > 1 && !empty($o['base_url'])) {
            $seoMeta .= self::pageLink('prev', self::pageUrl($o['base_url'], $page - 1)) . PHP_EOL;
        }
        if (!empty($o['has_next'])) {
            $seoMeta .= self::pageLink('next', self::pageUrl($o['base_url'] ?? $canonical, $page + 1)) . PHP_EOL;
        }

        // ---------------- Open Graph ----------------
        $og = [
            'og:site_name' => $siteName,
            'og:locale' => defined('APP_LOCALE') ? APP_LOCALE : 'en',
            'og:type' => $type,
            'og:title' => (string) ($o['og_title'] ?? $title),
            'og:description' => self::clip((string) ($o['og_description'] ?? $description), 300),
            'og:url' => $canonical,
            'og:image' => $image,
            'og:image:secure_url' => str_starts_with($canonical, 'https://') ? preg_replace('#^http://#i', 'https://', $image) : '',
            'og:image:type' => $imageMeta['mime'],
            'og:image:width' => (string) $imageMeta['width'],
            'og:image:height' => (string) $imageMeta['height'],
            'og:image:alt' => $imageAlt,
        ];
        if ($type === 'article') {
            if (!empty($o['published'])) {
                $og['article:published_time'] = self::iso($o['published']);
            }
            if (!empty($o['modified'])) {
                $og['article:modified_time'] = self::iso($o['modified']);
            }
            if (!empty($o['author'])) {
                $og['article:author'] = (string) $o['author'];
            }
            if (!empty($o['section'])) {
                $og['article:section'] = (string) $o['section'];
            }
        }
        $ogTags = '';
        foreach ($og as $k => $v) {
            if ($v !== '' && $v !== null) {
                $ogTags .= '<meta property="' . esc_attr($k) . '" content="' . esc_attr((string) $v) . '">' . PHP_EOL;
            }
        }
        if ($type === 'article' && !empty($o['tags']) && is_array($o['tags'])) {
            foreach (array_slice($o['tags'], 0, 10) as $tag) {
                if (trim((string) $tag) !== '') {
                    $ogTags .= '<meta property="article:tag" content="' . esc_attr((string) $tag) . '">' . PHP_EOL;
                }
            }
        }

        // ---------------- Twitter ----------------
        $twitter = [
            'twitter:card' => (string) ($o['twitter_card'] ?? 'summary_large_image'),
            'twitter:site' => (string) (SEOSetting::get('twitter_site', '@trendypost') ?: '@trendypost'),
            'twitter:title' => (string) ($o['twitter_title'] ?? $title),
            'twitter:description' => self::clip((string) ($o['twitter_description'] ?? $description), 300),
            'twitter:image' => self::absolute((string) ($o['twitter_image'] ?? $image)),
            'twitter:image:alt' => $imageAlt,
            'twitter:url' => $canonical,
        ];
        $twitterTags = '';
        foreach ($twitter as $k => $v) {
            if ($v !== '' && $v !== null) {
                $twitterTags .= '<meta name="' . esc_attr($k) . '" content="' . esc_attr((string) $v) . '">' . PHP_EOL;
            }
        }

        // ---------------- schema.org @graph ----------------
        $graph = self::siteGraph($siteName, $logo);

        $pageNode = [
            '@type' => (string) ($o['schema_type'] ?? 'WebPage'),
            '@id' => $canonical . '#webpage',
            'url' => $canonical,
            'name' => $title,
            'description' => $description,
            'isPartOf' => ['@id' => self::home() . '#website'],
            'inLanguage' => defined('APP_LOCALE') ? APP_LOCALE : 'en',
        ];
        if (!empty($o['breadcrumbs']) && is_array($o['breadcrumbs'])) {
            $breadcrumb = self::breadcrumbs($o['breadcrumbs'], $canonical);
            $pageNode['breadcrumb'] = ['@id' => $canonical . '#breadcrumb'];
            $graph[] = $breadcrumb;
        }
        $graph[] = $pageNode;

        if ($type === 'article') {
            $article = [
                '@type' => (string) ($o['article_schema_type'] ?? 'NewsArticle'),
                '@id' => $canonical . '#article',
                'headline' => self::clip($title, 110),
                'description' => $description,
                'image' => self::imageNodes($image, $imageAlt, $imageCaption, !empty($o['extra_images']) ? $o['extra_images'] : []),
                'datePublished' => !empty($o['published']) ? self::iso($o['published']) : null,
                'dateModified' => !empty($o['modified']) ? self::iso($o['modified']) : (!empty($o['published']) ? self::iso($o['published']) : null),
                'author' => [
                    '@type' => 'Person',
                    'name' => (string) ($o['author'] ?? $siteName),
                ],
                'publisher' => ['@id' => self::home() . '#organization'],
                'mainEntityOfPage' => ['@id' => $canonical . '#webpage'],
                'isAccessibleForFree' => true,
                'inLanguage' => defined('APP_LOCALE') ? APP_LOCALE : 'en',
            ];
            if (!empty($o['section'])) {
                $article['articleSection'] = (string) $o['section'];
            }
            if (!empty($o['tags']) && is_array($o['tags'])) {
                $article['keywords'] = implode(', ', array_slice(array_filter($o['tags']), 0, 15));
            }
            if (!empty($o['word_count'])) {
                $article['wordCount'] = (int) $o['word_count'];
            }
            $article = array_filter($article, static fn($v) => $v !== null && $v !== '');
            $graph[] = $article;
        }

        if (!empty($o['related']) && is_array($o['related'])) {
            $items = [];
            $pos = 1;
            foreach (array_slice($o['related'], 0, 6) as $item) {
                $url = (string) ($item['url'] ?? '');
                if ($url === '') {
                    continue;
                }
                $node = [
                    '@type' => 'ListItem',
                    'position' => $pos++,
                    'url' => $url,
                    'name' => (string) ($item['title'] ?? ''),
                ];
                if (!empty($item['image'])) {
                    $node['image'] = self::absolute((string) $item['image']);
                }
                $items[] = $node;
            }
            if ($items) {
                $graph[] = [
                    '@type' => 'ItemList',
                    '@id' => $canonical . '#related',
                    'name' => 'Related articles',
                    'itemListElement' => $items,
                ];
            }
        }

        $schemaMarkup = schema_jsonld(['@graph' => $graph]);

        return [
            'pageTitle' => $title,
            'pageDescription' => $description,
            'canonicalUrl' => $canonical,
            'seoMeta' => $seoMeta,
            'ogTags' => $ogTags,
            'twitterTags' => $twitterTags,
            'schemaMarkup' => $schemaMarkup,
        ];
    }

    /**
     * Site-wide WebSite + Organization nodes, shared by every page.
     */
    public static function siteGraph(string $siteName, string $logo): array {
        $home = self::home();
        $social = [];
        foreach (['social_facebook', 'social_twitter', 'social_instagram', 'social_youtube', 'social_linkedin'] as $key) {
            $val = trim((string) Setting::get($key, ''));
            if ($val !== '') {
                $social[] = $val;
            }
        }
        $org = [
            '@type' => 'Organization',
            '@id' => $home . '#organization',
            'name' => $siteName,
            'url' => $home,
            'logo' => ['@type' => 'ImageObject', 'url' => $logo],
        ];
        if ($social) {
            $org['sameAs'] = array_values(array_unique($social));
        }
        $email = trim((string) Setting::get('site_email', ''));
        if ($email !== '' && is_valid_email($email)) {
            $org['contactPoint'] = [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => $email,
                'url' => url('contact'),
            ];
        }
        $website = [
            '@type' => 'WebSite',
            '@id' => $home . '#website',
            'url' => $home,
            'name' => $siteName,
            'publisher' => ['@id' => $home . '#organization'],
            'inLanguage' => defined('APP_LOCALE') ? APP_LOCALE : 'en',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => search_url() . '?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ];
        return [$website, $org];
    }

    private static function breadcrumbs(array $items, string $canonical): array {
        $list = [];
        $pos = 1;
        foreach ($items as $item) {
            $name = trim((string) ($item[0] ?? ''));
            if ($name === '') {
                continue;
            }
            $entry = ['@type' => 'ListItem', 'position' => $pos++, 'name' => $name];
            if (!empty($item[1])) {
                $entry['item'] = self::absolute((string) $item[1]);
            }
            $list[] = $entry;
        }
        return [
            '@type' => 'BreadcrumbList',
            '@id' => $canonical . '#breadcrumb',
            'itemListElement' => $list,
        ];
    }

    /**
     * Build ImageObject nodes, including any inline content images.
     */
    private static function imageNodes(string $url, string $alt, string $caption, array $extra = []): array {
        $nodes = [];
        $main = self::imageMeta($url);
        $node = [
            '@type' => 'ImageObject',
            'url' => $url,
            'width' => $main['width'],
            'height' => $main['height'],
        ];
        if ($alt !== '') {
            $node['name'] = $alt;
            $node['caption'] = $caption !== '' ? $caption : $alt;
        }
        $nodes[] = $node;
        foreach (array_slice($extra, 0, 5) as $img) {
            $src = self::absolute((string) ($img['url'] ?? ''));
            if ($src === '' || $src === $url) {
                continue;
            }
            $meta = self::imageMeta($src);
            $n = ['@type' => 'ImageObject', 'url' => $src, 'width' => $meta['width'], 'height' => $meta['height']];
            $label = trim((string) ($img['alt'] ?? ''));
            if ($label !== '') {
                $n['caption'] = $label;
            }
            $nodes[] = $n;
        }
        return count($nodes) === 1 ? $nodes[0] : $nodes;
    }

    /**
     * Read the real dimensions of a local image, falling back to 1200x630.
     */
    public static function imageMeta(string $url): array {
        $cached = self::$imageSizeCache[$url] ?? null;
        if ($cached !== null) {
            return $cached;
        }
        $result = ['width' => 1200, 'height' => 630, 'mime' => 'image/jpeg'];
        $local = self::localPath($url);
        if ($local !== '' && is_file($local)) {
            $size = @getimagesize($local);
            if (is_array($size) && $size[0] > 0 && $size[1] > 0) {
                $result = [
                    'width' => (int) $size[0],
                    'height' => (int) $size[1],
                    'mime' => !empty($size['mime']) ? (string) $size['mime'] : 'image/jpeg',
                ];
            }
        }
        self::$imageSizeCache[$url] = $result;
        return $result;
    }

    public static function localPath(string $url): string {
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '';
        }
        $root = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);
        if ($root === '' || $root === '/' || $root === '.') {
            $root = dirname(__DIR__);
        }
        $candidate = rtrim($root, '/') . '/' . ltrim($path, '/');
        return is_file($candidate) ? $candidate : '';
    }

    public static function absolute(string $url): string {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^(https?:)?//#i', $url)) {
            return str_starts_with($url, '//') ? 'https:' . $url : $url;
        }
        return rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
    }

    public static function home(): string {
        return rtrim(BASE_URL, '/') . '/';
    }

    public static function stripTracking(string $url): string {
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return $url;
        }
        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            foreach (array_keys($query) as $key) {
                if (preg_match('/^(utm_|fbclid|gclid|mc_|ref$|source$)/i', (string) $key)) {
                    unset($query[$key]);
                }
            }
        }
        $rebuilt = ($parts['scheme'] ?? 'https') . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $rebuilt .= ':' . $parts['port'];
        }
        $rebuilt .= $parts['path'] ?? '/';
        if ($query) {
            $rebuilt .= '?' . http_build_query($query);
        }
        return $rebuilt;
    }

    private static function pageLink(string $rel, string $url): string {
        return '<link rel="' . $rel . '" href="' . esc_attr(self::stripTracking($url)) . '">';
    }

    private static function pageUrl(string $base, int $page): string {
        $base = rtrim($base, '?&');
        if ($page <= 1) {
            return $base;
        }
        return $base . (str_contains($base, '?') ? '&' : '?') . 'page=' . $page;
    }

    private static function iso(string $date): string {
        $ts = strtotime($date);
        return $ts ? date('c', $ts) : $date;
    }

    private static function clip(string $text, int $max): string {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max);
        $space = mb_strrpos($cut, ' ');
        if ($space !== false && $space > $max * 0.6) {
            $cut = mb_substr($cut, 0, $space);
        }
        return rtrim($cut, " \t\n\r\0\x0B.,;:-") . '…';
    }

    /**
     * Extract inline <img> sources/alt text from post content for image schema.
     */
    public static function contentImages(string $html, int $limit = 5): array {
        if ($html === '' || stripos($html, '<img') === false) {
            return [];
        }
        $images = [];
        if (preg_match_all('#<img\b[^>]*>#i', $html, $matches)) {
            foreach ($matches[0] as $tag) {
                if (count($images) >= $limit) {
                    break;
                }
                if (!preg_match('#\bsrc\s*=\s*["\']([^"\']+)["\']#i', $tag, $src)) {
                    continue;
                }
                $alt = '';
                if (preg_match('#\balt\s*=\s*["\']([^"\']*)["\']#i', $tag, $a)) {
                    $alt = html_entity_decode($a[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
                $images[] = ['url' => html_entity_decode($src[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'alt' => $alt];
            }
        }
        return $images;
    }

    /**
     * Turn a list of posts into ListItem data for ItemList schema.
     */
    public static function itemsFromPosts(array $posts, int $limit = 10): array {
        $items = [];
        foreach (array_slice($posts, 0, $limit) as $post) {
            if (empty($post['slug'])) {
                continue;
            }
            $items[] = [
                'title' => (string) ($post['title'] ?? ''),
                'url' => self::absolute(post_url((string) $post['slug'])),
                'image' => !empty($post['featured_image']) ? self::absolute(media_url($post['featured_image'], '')) : null,
            ];
        }
        return $items;
    }

    /**
     * Collect every indexable image URL used by a post (featured + inline).
     */
    public static function postImages(array $post): array {
        $out = [];
        $featured = trim((string) ($post['featured_image'] ?? ''));
        if ($featured !== '') {
            $out[] = [
                'url' => self::absolute(media_url($featured, '')),
                'alt' => trim((string) ($post['featured_image_alt'] ?? '')) ?: (string) ($post['title'] ?? ''),
                'caption' => trim((string) ($post['featured_image_caption'] ?? '')),
            ];
        }
        foreach (self::contentImages((string) ($post['content'] ?? '')) as $img) {
            $img['url'] = self::absolute($img['url']);
            if ($img['url'] !== '') {
                $out[] = $img;
            }
        }
        $unique = [];
        foreach ($out as $img) {
            $unique[$img['url']] = $img;
        }
        return array_values($unique);
    }
}
