<?php
/**
 * TRENDYPOST - Helper Functions
 * =============================
 * Centralized URL generation, asset loading, and utility functions.
 * NO HARDCODED URLS - All URLs use BASE_URL.
 */

if (!defined('TRENDYPOST')) {
    define('TRENDYPOST', true);
}

require_once CONFIG_PATH . 'config.php';

// ============================================================
// URL HELPERS
// ============================================================

/**
 * Generate full URL from path
 */
function url(string $path = ''): string {
    return BASE_URL . ltrim($path, '/');
}

/**
 * Generate asset URL
 */
function asset(string $path): string {
    return ASSETS_URL . ltrim($path, '/');
}

/**
 * Generate CSS file URL
 */
function css_url(string $filename): string {
    return CSS_URL . $filename;
}

/**
 * Generate JS file URL
 */
function js_url(string $filename): string {
    return JS_URL . $filename;
}

/**
 * Generate image URL
 */
function image_url(string $filename): string {
    return IMAGES_URL . $filename;
}

/**
 * Generate upload URL
 */
function upload_url(string $filename): string {
    return UPLOADS_URL . $filename;
}

/**
 * Generate admin URL - FIXED for root domain
 * Uses query parameter routing: admin/?route=dashboard&action=...
 */
function admin_url(string $path = ''): string {
    // Remove any leading/trailing slashes
    $path = trim($path, '/');
    
    // Remove any existing admin prefix from the path
    $path = preg_replace('#^admin/?|^/admin/?#', '', $path);
    
    // Get the base admin URL
    $base = rtrim(ADMIN_URL, '/');
    
    // If no path, return just the admin base with trailing slash
    if (empty($path)) {
        return $base . '/';
    }
    
    // Check if path already contains query parameters
    if (str_contains($path, '&') || str_contains($path, '=')) {
        return $base . '/?route=' . $path;
    }
    
    // Simple route
    return $base . '/?route=' . $path;
}

/**
 * Generate API URL
 */
function api_url(string $path = ''): string {
    return API_URL . ltrim($path, '/');
}

/**
 * Generate post URL (SEO-friendly)
 */
function post_url(string $slug): string {
    return url('post/' . $slug);
}

/**
 * Generate category URL
 */
function category_url(string $slug): string {
    return url('category/' . $slug);
}

/**
 * Generate tag URL
 */
function tag_url(string $slug): string {
    return url('tag/' . $slug);
}

/**
 * Generate author URL
 */
function author_url(string $username): string {
    return url('author/' . $username);
}

/**
 * Generate page URL
 */
function page_url(string $slug): string {
    return url('page/' . $slug);
}

/**
 * Generate search URL
 */
function search_url(string $query = ''): string {
    return url('search' . ($query ? '?q=' . urlencode($query) : ''));
}

/**
 * Generate archive URL
 */
function archive_url(int $year, int $month = 0): string {
    return url('archive/' . $year . ($month ? '/' . str_pad($month, 2, '0', STR_PAD_LEFT) : ''));
}

// ============================================================
// ASSET HELPERS
// ============================================================

/**
 * Output CSS link tag
 */
function css_link(string $filename, array $attributes = []): string {
    $attrs = '';
    foreach ($attributes as $key => $val) {
        $attrs .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
    }
    return '<link rel="stylesheet" href="' . css_url($filename) . '"' . $attrs . '>';
}

/**
 * Output JS script tag
 */
function js_script(string $filename, array $attributes = []): string {
    $attrs = '';
    foreach ($attributes as $key => $val) {
        $attrs .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
    }
    return '<script src="' . js_url($filename) . '"' . $attrs . '></script>';
}

/**
 * Output image tag
 */
function img_tag(string $src, string $alt = '', array $attributes = []): string {
    $attrs = '';
    foreach ($attributes as $key => $val) {
        $attrs .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($val) . '"';
    }
    return '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($alt) . '"' . $attrs . ' loading="lazy">';
}

// ============================================================
// STRING HELPERS
// ============================================================

/**
 * Create URL-friendly slug
 */
function create_slug(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text) ?: 'untitled';
}

/**
 * Generate unique slug
 */
function unique_slug(string $table, string $slug, ?int $excludeId = null): string {
    $originalSlug = $slug;
    $counter = 1;
    
    while (true) {
        $sql = "SELECT id FROM `{$table}` WHERE slug = ?";
        $params = [$slug];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $existing = Database::fetchOne($sql, $params);
        if (!$existing) break;
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

/**
 * Create excerpt from content
 */
function excerpt(string $content, int $length = EXCERPT_LENGTH): string {
    $text = strip_tags($content);
    if (strlen($text) <= $length) return $text;
    $text = substr($text, 0, $length);
    $lastSpace = strrpos($text, ' ');
    if ($lastSpace !== false) {
        $text = substr($text, 0, $lastSpace);
    }
    return $text . '...';
}

/**
 * Reading time estimation
 */
function reading_time(string $content): int {
    $wordCount = str_word_count(strip_tags($content));
    return max(1, ceil($wordCount / 200)); // 200 WPM average
}

/**
 * Format date
 */
function format_date(string $date, string $format = 'F j, Y'): string {
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function format_datetime(string $datetime): string {
    return date('F j, Y g:i A', strtotime($datetime));
}

/**
 * Time ago
 */
function time_ago(string $datetime): string {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    if ($diff < 2592000) return floor($diff / 604800) . ' weeks ago';
    return date('F j, Y', $time);
}

// ============================================================
// SECURITY HELPERS
// ============================================================

/**
 * Generate CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * CSRF field HTML
 */
function csrf_field(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Verify CSRF token
 */
function verify_csrf(string $token): bool {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Sanitize input
 */
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Deep sanitize array
 */
function sanitize_array(array $data): array {
    $clean = [];
    foreach ($data as $key => $value) {
        $clean[$key] = is_array($value) ? sanitize_array($value) : sanitize($value);
    }
    return $clean;
}

/**
 * Validate email
 */
function is_valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hash password
 */
function hash_password(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verify_password(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Generate random string
 */
function random_string(int $length = 16): string {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get client IP
 */
function client_ip(): string {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            return trim($ips[0]);
        }
    }
    return '0.0.0.0';
}

// ============================================================
// SEO HELPERS
// ============================================================

/**
 * Escape HTML attribute
 */
function esc_attr(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate Open Graph tags
 */
function og_tags(array $data = []): string {
    $defaults = [
        'title' => SEO_DEFAULT_TITLE,
        'description' => SEO_DEFAULT_DESCRIPTION,
        'image' => SEO_DEFAULT_IMAGE,
        'url' => current_url(),
        'type' => 'website',
        'site_name' => APP_NAME,
        'locale' => APP_LOCALE
    ];
    $og = array_merge($defaults, $data);
    
    $html = '';
    foreach ($og as $key => $value) {
        if ($value) {
            $html .= '<meta property="og:' . esc_attr($key) . '" content="' . esc_attr($value) . '">' . PHP_EOL;
        }
    }
    return $html;
}

/**
 * Generate Twitter Card tags
 */
function twitter_tags(array $data = []): string {
    $defaults = [
        'card' => 'summary_large_image',
        'title' => SEO_DEFAULT_TITLE,
        'description' => SEO_DEFAULT_DESCRIPTION,
        'image' => SEO_DEFAULT_IMAGE,
        'site' => '@trendypost'
    ];
    $tw = array_merge($defaults, $data);
    
    $html = '';
    foreach ($tw as $key => $value) {
        if ($value) {
            $html .= '<meta name="twitter:' . esc_attr($key) . '" content="' . esc_attr($value) . '">' . PHP_EOL;
        }
    }
    return $html;
}

/**
 * Generate meta tags
 */
function meta_tags(string $title = '', string $description = '', string $keywords = '', string $robots = ''): string {
    $html = '';
    if ($title) {
        $html .= '<title>' . esc_attr($title) . '</title>' . PHP_EOL;
    }
    if ($description) {
        $html .= '<meta name="description" content="' . esc_attr($description) . '">' . PHP_EOL;
    }
    if ($keywords) {
        $html .= '<meta name="keywords" content="' . esc_attr($keywords) . '">' . PHP_EOL;
    }
    if ($robots) {
        $html .= '<meta name="robots" content="' . esc_attr($robots) . '">' . PHP_EOL;
    }
    return $html;
}

/**
 * Get current URL
 */
function current_url(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Generate canonical URL
 */
function canonical_url(string $path = ''): string {
    return $path ? url($path) : current_url();
}

/**
 * Generate JSON-LD schema
 */
function schema_jsonld(array $schema): string {
    $schema['@context'] = 'https://schema.org';
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

// ============================================================
// PAGINATION HELPER
// ============================================================

/**
 * Generate pagination HTML
 */
function pagination(int $total, int $perPage, int $currentPage, string $baseUrl): string {
    $totalPages = max(1, ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    
    if ($totalPages <= 1) return '';
    
    // Parse base URL to handle query parameters properly
    $baseUrl = rtrim($baseUrl, '?&');
    $separator = strpos($baseUrl, '?') !== false ? '&' : '?';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . $separator . 'page=' . ($currentPage - 1) . '">&laquo;</a></li>';
    }
    
    // Pages
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . $separator . 'page=1">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . $separator . 'page=' . $i . '">' . $i . '</a></li>';
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . $separator . 'page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }
    
    // Next
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . $separator . 'page=' . ($currentPage + 1) . '">&raquo;</a></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

// ============================================================
// FLASH MESSAGES
// ============================================================

/**
 * Set flash message
 */
function flash_set(string $type, string $message): void {
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash messages
 */
function flash_get(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Display flash messages as HTML
 */
function flash_display(): string {
    $messages = flash_get();
    if (empty($messages)) return '';
    
    $html = '';
    foreach ($messages as $msg) {
        $alertClass = match($msg['type']) {
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info',
            default => 'alert-info'
        };
        $html .= '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        $html .= esc_attr($msg['message']);
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    return $html;
}

// ============================================================
// DEVICE DETECTION
// ============================================================

/**
 * Detect device type
 */
function device_type(): string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $ua)) {
        return 'tablet';
    }
    if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $ua)) {
        return 'mobile';
    }
    return 'desktop';
}

/**
 * Check if mobile
 */
function is_mobile(): bool {
    return device_type() === 'mobile';
}

/**
 * Check if desktop
 */
function is_desktop(): bool {
    return device_type() === 'desktop';
}

// ============================================================
// FORMATTING HELPERS
// ============================================================

/**
 * Format number (e.g. 1.2K, 1.5M)
 */
function format_number(int $num): string {
    if ($num >= 1000000) return round($num / 1000000, 1) . 'M';
    if ($num >= 1000) return round($num / 1000, 1) . 'K';
    return (string) $num;
}

/**
 * Format file size
 */
function format_filesize(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
        $bytes /= 1024;
        $unitIndex++;
    }
    return round($bytes, 2) . ' ' . $units[$unitIndex];
}

/**
 * Truncate text
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . $suffix;
}

// ============================================================
// AD HELPER
// ============================================================

/**
 * Get advertisements for a placement
 */
function get_ads(string $placement, int $limit = 1): array {
    $device = device_type();
    $sql = "SELECT a.* FROM advertisements a 
            JOIN ad_placements ap ON a.placement_id = ap.id 
            WHERE ap.slug = ? AND a.status = 'active' 
            AND (ap.device_target = 'all' OR ap.device_target = ?)
            AND (a.start_date IS NULL OR a.start_date <= CURDATE())
            AND (a.end_date IS NULL OR a.end_date >= CURDATE())
            ORDER BY a.created_at DESC LIMIT ?";
    return Database::fetchAll($sql, [$placement, $device, $limit]);
}

/**
 * Render ad HTML
 */
function render_ad(array $ad): string {
    if (empty($ad)) return '';
    
    // Track impression via AJAX
    $trackUrl = api_url('track-impression/' . $ad['id']);
    
    $html = '<div class="ad-container ad-' . esc_attr($ad['ad_type']) . '" data-ad-id="' . $ad['id'] . '">';
    
    if ($ad['ad_type'] === 'custom_html' && $ad['custom_html']) {
        $html .= $ad['custom_html'];
    } elseif ($ad['image'] && $ad['link_url']) {
        $target = $ad['target_blank'] ? ' target="_blank"' : '';
        $rel = $ad['nofollow'] ? ' rel="nofollow noopener"' : ' rel="noopener"';
        $html .= '<a href="' . esc_attr($ad['link_url']) . '"' . $target . $rel . '>';
        // Check if image is URL or file path
        if (filter_var($ad['image'], FILTER_VALIDATE_URL)) {
            $html .= '<img src="' . esc_attr($ad['image']) . '" alt="' . esc_attr($ad['title']) . '" class="img-fluid">';
        } else {
            $html .= '<img src="' . upload_url($ad['image']) . '" alt="' . esc_attr($ad['title']) . '" class="img-fluid">';
        }
        $html .= '</a>';
    } elseif ($ad['ad_code']) {
        $html .= $ad['ad_code'];
    }
    
    $html .= '</div>';
    
    // Track impression
    $html .= "<script>
        if (typeof fetch !== 'undefined') {
            fetch('{$trackUrl}', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': '" . csrf_token() . "',
                    'Content-Type': 'application/json'
                }
            }).catch(function(e) { console.log('Ad tracking error:', e); });
        }
    </script>";
    
    return $html;
}

// ============================================================
// CACHE HELPER
// ============================================================

/**
 * Cache key generator
 */
function cache_key(string $type, string $identifier): string {
    return $type . '_' . md5($identifier);
}

/**
 * Get cached data
 */
function cache_get(string $key) {
    $file = CACHE_PATH . $key . '.cache';
    if (!file_exists($file)) return null;
    $data = unserialize(file_get_contents($file));
    if ($data['expires'] < time()) {
        unlink($file);
        return null;
    }
    return $data['value'];
}

/**
 * Set cached data
 */
function cache_set(string $key, $value, int $ttl = 3600): void {
    if (!is_dir(CACHE_PATH)) mkdir(CACHE_PATH, 0755, true);
    $file = CACHE_PATH . $key . '.cache';
    file_put_contents($file, serialize(['expires' => time() + $ttl, 'value' => $value]));
}

/**
 * Clear cache
 */
function cache_clear(string $pattern = '*'): void {
    foreach (glob(CACHE_PATH . $pattern . '.cache') as $file) {
        unlink($file);
    }
}