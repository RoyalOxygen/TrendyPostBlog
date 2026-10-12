<?php
/**
 * TRENDYPOST - Data Models (FULLY UPDATED WITH PROPER TIME HANDLING)
 * All database query models with proper table aliases and timezone support
 */

if (!defined('TRENDYPOST')) {
    die('Direct access not permitted.');
}

// ============================================================
// POST MODEL - COMPLETE FIXED VERSION WITH TIME HANDLING
// ============================================================
class Post {
    
    /**
     * Check if we're in admin context
     */
    private static function isAdmin(): bool {
        return defined('ADMIN') && ADMIN === true;
    }
    
    /**
     * Get current time in proper format
     */
    private static function now(): string {
        return date('Y-m-d H:i:s');
    }
    
    /**
     * Get current UNIX timestamp
     */
    private static function unixNow(): int {
        return time();
    }

    private static function textFields(): array {
        return [
            'title', 'excerpt', 'content', 'featured_image_alt', 'featured_image_caption',
            'seo_title', 'seo_description', 'seo_keywords', 'focus_keyword', 'secondary_keywords',
            'og_title', 'og_description', 'twitter_title', 'twitter_description',
            'original_title', 'article_section',
        ];
    }

    private static function decodePost(?array $post): ?array {
        if (!$post) {
            return $post;
        }
        return decode_text_fields($post, self::textFields());
    }

    private static function decodePosts(array $posts): array {
        foreach ($posts as $i => $post) {
            $posts[$i] = self::decodePost($post);
        }
        return $posts;
    }
    
    public static function getBySlug(string $slug): ?array {
        $post = Database::fetchOne(
            "SELECT p.*, u.username, u.display_name as author_name, u.avatar as author_avatar, u.bio as author_bio,
                    c.name as category_name, c.slug as category_slug, c.color as category_color,
                    COUNT(DISTINCT com.id) as total_comments
             FROM posts p
             LEFT JOIN users u ON p.author_id = u.id
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN comments com ON p.id = com.post_id AND com.status = 'approved'
             WHERE p.slug = ? AND p.status = 'published' 
               AND UNIX_TIMESTAMP(p.published_at) <= UNIX_TIMESTAMP(NOW())
               AND p.published_at IS NOT NULL
             GROUP BY p.id LIMIT 1",
            [$slug]
        );
        
        if ($post) {
            $post = self::decodePost($post);
            $post['tags'] = self::getTags($post['id']);
            $post['meta'] = self::getMeta($post['id']);
        }
        
        return $post;
    }
    
    public static function getById(int $id): ?array {
        return self::decodePost(Database::fetchOne(
            "SELECT p.*, u.username, u.display_name as author_name, u.avatar as author_avatar,
                    c.name as category_name, c.slug as category_slug, c.color as category_color
             FROM posts p
             LEFT JOIN users u ON p.author_id = u.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.id = ? LIMIT 1",
            [$id]
        ));
    }
    
    public static function getList(array $filters = [], int $page = 1, int $perPage = 10): array {
        $where = [];
        $params = [];
        
        // Check if we're in admin context
        $isAdmin = self::isAdmin();
        
        if ($isAdmin) {
            // ADMIN: Show all posts based on status filter
            if (!empty($filters['status'])) {
                $where[] = 'p.status = ?';
                $params[] = $filters['status'];
            } else {
                // If no status filter, show all except trash
                $where[] = "p.status != 'trash'";
            }
            // In admin, we don't add published_at restriction
        } else {
            // FRONTEND: Only show published posts with proper time comparison
            $where[] = 'p.status = ?';
            $params[] = 'published';
            // Use UNIX_TIMESTAMP for reliable time comparison across timezones
            $where[] = "UNIX_TIMESTAMP(p.published_at) <= UNIX_TIMESTAMP(NOW())";
            $where[] = 'p.published_at IS NOT NULL';
        }
        
        // Category filter
        if (!empty($filters['category'])) {
            $where[] = 'c.slug = ?';
            $params[] = $filters['category'];
        }
        
        // Tag filter
        if (!empty($filters['tag'])) {
            $where[] = 't.slug = ?';
            $params[] = $filters['tag'];
        }
        
        // Author filter
        if (!empty($filters['author'])) {
            $where[] = 'u.username = ?';
            $params[] = $filters['author'];
        }
        
        // Post type filter
        if (!empty($filters['type'])) {
            $where[] = 'p.post_type = ?';
            $params[] = $filters['type'];
        }
        
        // Featured filter
        if (!empty($filters['featured'])) {
            $where[] = 'p.is_featured = 1';
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $where[] = '(p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        
        $tagJoin = !empty($filters['tag']) ? "LEFT JOIN post_tags pt ON p.id = pt.post_id LEFT JOIN tags t ON pt.tag_id = t.id" : "";
        
        $sql = "SELECT DISTINCT p.*, u.username, u.display_name as author_name, u.avatar as author_avatar,
                       c.name as category_name, c.slug as category_slug, c.color as category_color,
                       COUNT(DISTINCT com.id) as comment_count
                FROM posts p
                LEFT JOIN users u ON p.author_id = u.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN comments com ON p.id = com.post_id AND com.status = 'approved'
                {$tagJoin}
                WHERE {$whereClause}
                GROUP BY p.id
                ORDER BY p.is_sticky DESC, p.published_at DESC, p.id DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;
        
        return self::decodePosts(Database::fetchAll($sql, $params));
    }
    
    public static function count(array $filters = []): int {
        $where = [];
        $params = [];
        
        // Check if we're in admin context
        $isAdmin = self::isAdmin();
        
        if ($isAdmin) {
            // ADMIN: Show all posts based on status filter
            if (!empty($filters['status'])) {
                $where[] = 'p.status = ?';
                $params[] = $filters['status'];
            } else {
                // If no status filter, show all except trash
                $where[] = "p.status != 'trash'";
            }
        } else {
            // FRONTEND: Only show published posts
            $where[] = 'p.status = ?';
            $params[] = 'published';
            $where[] = "UNIX_TIMESTAMP(p.published_at) <= UNIX_TIMESTAMP(NOW())";
            $where[] = 'p.published_at IS NOT NULL';
        }
        
        // Category filter
        if (!empty($filters['category'])) {
            $where[] = 'c.slug = ?';
            $params[] = $filters['category'];
        }
        
        // Tag filter
        if (!empty($filters['tag'])) {
            $where[] = 't.slug = ?';
            $params[] = $filters['tag'];
        }
        
        // Author filter
        if (!empty($filters['author'])) {
            $where[] = 'u.username = ?';
            $params[] = $filters['author'];
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $where[] = '(p.title LIKE ? OR p.content LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = implode(' AND ', $where);
        $tagJoin = !empty($filters['tag']) ? "LEFT JOIN post_tags pt ON p.id = pt.post_id LEFT JOIN tags t ON pt.tag_id = t.id" : "";
        
        $sql = "SELECT COUNT(DISTINCT p.id) FROM posts p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN users u ON p.author_id = u.id
                {$tagJoin}
                WHERE {$whereClause}";
        
        return (int) Database::fetchColumn($sql, $params);
    }
    
    public static function getFeatured(int $limit = 5): array {
        return self::decodePosts(Database::fetchAll(
            "SELECT p.*, u.username, u.display_name as author_name, u.avatar as author_avatar,
                    c.name as category_name, c.slug as category_slug, c.color as category_color
             FROM posts p
             LEFT JOIN users u ON p.author_id = u.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.status = 'published' 
               AND UNIX_TIMESTAMP(p.published_at) <= UNIX_TIMESTAMP(NOW())
               AND p.published_at IS NOT NULL
               AND p.is_featured = 1
              ORDER BY p.published_at DESC LIMIT ?",
            [$limit]
        ));
    }
    
    public static function getBreaking(int $limit = 3): array {
        return self::decodePosts(Database::fetchAll(
            "SELECT p.*, c.name as category_name, c.slug as category_slug, c.color as category_color
             FROM posts p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.status = 'published' 
               AND UNIX_TIMESTAMP(p.published_at) <= UNIX_TIMESTAMP(NOW())
               AND p.published_at IS NOT NULL
               AND p.is_breaking = 1
             ORDER BY p.published_at DESC LIMIT ?",
            [$limit]
        ));
    }
    
    public static function getPopular(int $limit = 5): array {
        return self::decodePosts(Database::fetchAll(
            "SELECT p.*, u.username, u.display_name as author_name,
                    c.name as category_name, c.slug as category_slug, c.color as category_color
             FROM posts p
             LEFT JOIN users u ON p.author_id = u.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.status = 'published' 
               AND UNIX_TIMESTAMP(p.published_at) <= UNIX_TIMESTAMP(NOW())
               AND p.published_at IS NOT NULL
             ORDER BY p.view_count DESC LIMIT ?",
            [$limit]
        ));
    }
    
    public static function getRelated(int $postId, int $categoryId, int $limit = 4): array {
        return self::decodePosts(Database::fetchAll(
            "SELECT p.*, u.display_name as author_name, c.name as category_name, c.slug as category_slug
             FROM posts p
             LEFT JOIN users u ON p.author_id = u.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.id != ? 
               AND p.category_id = ? 
               AND p.status = 'published' 
               AND UNIX_TIMESTAMP(p.published_at) <= UNIX_TIMESTAMP(NOW())
               AND p.published_at IS NOT NULL
             ORDER BY p.published_at DESC LIMIT ?",
            [$postId, $categoryId, $limit]
        ));
    }
    
    public static function getLatest(int $limit = 6): array {
        return self::decodePosts(Database::fetchAll(
            "SELECT p.*, u.username, u.display_name as author_name, u.avatar as author_avatar,
                    c.name as category_name, c.slug as category_slug, c.color as category_color
             FROM posts p
             LEFT JOIN users u ON p.author_id = u.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.status = 'published' 
               AND UNIX_TIMESTAMP(p.published_at) <= UNIX_TIMESTAMP(NOW())
               AND p.published_at IS NOT NULL
             ORDER BY p.published_at DESC LIMIT ?",
            [$limit]
        ));
    }
    
    public static function incrementViews(int $postId): void {
        Database::query("UPDATE posts SET view_count = view_count + 1 WHERE id = ?", [$postId]);
        
        Database::insert('post_views', [
            'post_id' => $postId,
            'ip_address' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'device_type' => device_type()
        ]);
    }
    
    public static function getTags(int $postId): array {
        return Database::fetchAll(
            "SELECT t.* FROM tags t
             JOIN post_tags pt ON t.id = pt.tag_id
             WHERE pt.post_id = ? AND t.status = 'active'
             ORDER BY t.name",
            [$postId]
        );
    }
    
    public static function getMeta(int $postId): array {
        $meta = Database::fetchAll("SELECT meta_key, meta_value FROM post_meta WHERE post_id = ?", [$postId]);
        $result = [];
        foreach ($meta as $m) {
            $result[$m['meta_key']] = $m['meta_value'];
        }
        return $result;
    }
    
    public static function getAdjacent(int $postId, string $direction = 'next', int $categoryId = 0): ?array {
        $operator = $direction === 'next' ? '>' : '<';
        $order = $direction === 'next' ? 'ASC' : 'DESC';
        
        $where = "p.status = 'published' 
                  AND UNIX_TIMESTAMP(p.published_at) <= UNIX_TIMESTAMP(NOW()) 
                  AND p.published_at IS NOT NULL 
                  AND p.id {$operator} ?";
        $params = [$postId];
        
        if ($categoryId) {
            $where .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }
        
        return self::decodePost(Database::fetchOne(
            "SELECT p.id, p.title, p.slug, p.featured_image 
             FROM posts p 
             WHERE {$where} 
             ORDER BY p.published_at {$order}, p.id {$order} 
             LIMIT 1",
            $params
        ));
    }
    
    /**
     * Fix posts with missing or future published_at dates
     */
    public static function fixPublishedDates(): int {
        // Fix posts with NULL published_at
        $result1 = Database::query(
            "UPDATE posts SET published_at = created_at WHERE published_at IS NULL AND status = 'published'"
        );
        
        // Fix posts with future published_at (set to now)
        $result2 = Database::query(
            "UPDATE posts SET published_at = NOW() WHERE UNIX_TIMESTAMP(published_at) > UNIX_TIMESTAMP(NOW()) AND status = 'published'"
        );
        
        return ($result1->rowCount() + $result2->rowCount());
    }
    
    /**
     * Debug time issues - returns info about posts
     */
    public static function debugTimeIssues(): array {
        $issues = [];
        
        // Check posts with NULL published_at
        $nullPosts = Database::fetchAll(
            "SELECT id, title, status FROM posts WHERE published_at IS NULL AND status = 'published'"
        );
        if (!empty($nullPosts)) {
            $issues['null_dates'] = $nullPosts;
        }
        
        // Check posts with future published_at
        $futurePosts = Database::fetchAll(
            "SELECT id, title, status, published_at FROM posts 
             WHERE UNIX_TIMESTAMP(published_at) > UNIX_TIMESTAMP(NOW()) 
             AND status = 'published'"
        );
        if (!empty($futurePosts)) {
            $issues['future_dates'] = $futurePosts;
        }
        
        // Check timezone settings
        $issues['php_timezone'] = date_default_timezone_get();
        $issues['php_time'] = date('Y-m-d H:i:s');
        $issues['mysql_time'] = Database::fetchColumn("SELECT NOW()");
        $issues['mysql_unix'] = Database::fetchColumn("SELECT UNIX_TIMESTAMP(NOW())");
        
        return $issues;
    }
}

// ============================================================
// CATEGORY MODEL
// ============================================================
class Category {
    
    public static function getBySlug(string $slug): ?array {
        return Database::fetchOne(
            "SELECT c.*, COUNT(p.id) as post_count 
             FROM categories c 
             LEFT JOIN posts p ON c.id = p.category_id 
                AND p.status = 'published' 
                AND UNIX_TIMESTAMP(p.published_at) <= UNIX_TIMESTAMP(NOW())
                AND p.published_at IS NOT NULL
             WHERE c.slug = ? AND c.status = 'active' 
             GROUP BY c.id LIMIT 1",
            [$slug]
        );
    }
    
    public static function getById(int $id): ?array {
        return Database::fetchOne(
            "SELECT c.*, COUNT(p.id) as post_count 
             FROM categories c 
             LEFT JOIN posts p ON c.id = p.category_id 
                AND p.status = 'published' 
                AND UNIX_TIMESTAMP(p.published_at) <= UNIX_TIMESTAMP(NOW())
                AND p.published_at IS NOT NULL
             WHERE c.id = ? GROUP BY c.id LIMIT 1",
            [$id]
        );
    }
    
    public static function getAll(bool $onlyActive = true): array {
        $where = $onlyActive ? "WHERE c.status = 'active'" : "";
        return Database::fetchAll(
            "SELECT c.*, COUNT(p.id) as post_count 
             FROM categories c 
             LEFT JOIN posts p ON c.id = p.category_id 
                AND p.status = 'published' 
                AND UNIX_TIMESTAMP(p.published_at) <= UNIX_TIMESTAMP(NOW())
                AND p.published_at IS NOT NULL
             {$where}
             GROUP BY c.id 
             ORDER BY c.sort_order ASC, c.name ASC"
        );
    }
    
    public static function getParentCategories(): array {
        return Database::fetchAll(
            "SELECT * FROM categories WHERE parent_id IS NULL AND status = 'active' ORDER BY sort_order, name"
        );
    }
    
    public static function count(): int {
        return Database::count('categories', "status = 'active'");
    }
}

// ============================================================
// TAG MODEL
// ============================================================
class Tag {
    
    public static function getBySlug(string $slug): ?array {
        return Database::fetchOne(
            "SELECT * FROM tags WHERE slug = ? AND status = 'active' LIMIT 1", 
            [$slug]
        );
    }
    
    public static function getById(int $id): ?array {
        return Database::fetchOne("SELECT * FROM tags WHERE id = ? LIMIT 1", [$id]);
    }
    
    public static function getAll(bool $popular = false, int $limit = 50): array {
        $order = $popular ? "post_count DESC" : "t.name ASC";
        return Database::fetchAll(
            "SELECT t.*, COUNT(pt.post_id) as post_count 
             FROM tags t 
             LEFT JOIN post_tags pt ON t.id = pt.tag_id
             LEFT JOIN posts p ON pt.post_id = p.id 
                AND p.status = 'published' 
                AND UNIX_TIMESTAMP(p.published_at) <= UNIX_TIMESTAMP(NOW())
                AND p.published_at IS NOT NULL
             WHERE t.status = 'active'
             GROUP BY t.id 
             ORDER BY {$order} LIMIT ?",
            [$limit]
        );
    }
    
    public static function getPopular(int $limit = 20): array {
        return self::getAll(true, $limit);
    }
    
    public static function count(): int {
        return Database::count('tags', "status = 'active'");
    }
}

// ============================================================
// PAGE MODEL
// ============================================================
class Page {
    
    public static function getBySlug(string $slug): ?array {
        return Database::fetchOne(
            "SELECT p.*, u.display_name as author_name 
             FROM pages p 
             LEFT JOIN users u ON p.author_id = u.id 
             WHERE p.slug = ? AND p.status = 'published' LIMIT 1",
            [$slug]
        );
    }
    
    public static function getById(int $id): ?array {
        return Database::fetchOne("SELECT * FROM pages WHERE id = ? LIMIT 1", [$id]);
    }
    
    public static function getMenuPages(): array {
        return Database::fetchAll(
            "SELECT * FROM pages WHERE status = 'published' AND show_in_menu = 1 ORDER BY menu_order, title"
        );
    }

    public static function getPublished(): array {
        return Database::fetchAll(
            "SELECT * FROM pages WHERE status = 'published' ORDER BY updated_at DESC, title"
        );
    }
    
    public static function count(): int {
        return Database::count('pages', "status = 'published'");
    }
}

// ============================================================
// COMMENT MODEL
// ============================================================
class Comment {
    
    public static function getByPost(int $postId, string $status = 'approved'): array {
        return Database::fetchAll(
            "SELECT c.*, u.username, u.display_name, u.avatar
             FROM comments c
             LEFT JOIN users u ON c.user_id = u.id
             WHERE c.post_id = ? AND c.status = ? AND c.parent_id IS NULL
             ORDER BY c.is_pinned DESC, c.created_at DESC",
            [$postId, $status]
        );
    }
    
    public static function getReplies(int $parentId): array {
        return Database::fetchAll(
            "SELECT c.*, u.username, u.display_name, u.avatar
             FROM comments c
             LEFT JOIN users u ON c.user_id = u.id
             WHERE c.parent_id = ? AND c.status = 'approved'
             ORDER BY c.created_at ASC",
            [$parentId]
        );
    }
    
    public static function countByPost(int $postId, string $status = 'approved'): int {
        return Database::count('comments', "post_id = ? AND status = ?", [$postId, $status]);
    }
    
    public static function add(array $data): int {
        return Database::insert('comments', [
            'post_id' => $data['post_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'author_name' => sanitize($data['author_name'] ?? ''),
            'author_email' => sanitize($data['author_email'] ?? ''),
            'author_website' => sanitize($data['author_website'] ?? ''),
            'content' => $data['content'],
            'author_ip' => client_ip(),
            'status' => $data['status'] ?? 'pending',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
}

// ============================================================
// USER MODEL
// ============================================================
class User {
    
    public static function getByUsername(string $username): ?array {
        return Database::fetchOne(
            "SELECT u.*, r.name as role_name, r.display_name as role_display,
                    COUNT(DISTINCT p.id) as post_count
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             LEFT JOIN posts p ON u.id = p.author_id 
                AND p.status = 'published' 
                AND UNIX_TIMESTAMP(p.published_at) <= UNIX_TIMESTAMP(NOW())
                AND p.published_at IS NOT NULL
             WHERE u.username = ? AND u.status = 'active'
             GROUP BY u.id LIMIT 1",
            [$username]
        );
    }
    
    public static function getById(int $id): ?array {
        return Database::fetchOne(
            "SELECT u.*, r.name as role_name, r.display_name as role_display
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE u.id = ? LIMIT 1",
            [$id]
        );
    }
    
    public static function getAuthors(int $limit = 20): array {
        return Database::fetchAll(
            "SELECT u.*, r.name as role_name, r.display_name as role_display,
                    COUNT(DISTINCT p.id) as post_count
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             LEFT JOIN posts p ON u.id = p.author_id 
                AND p.status = 'published' 
                AND UNIX_TIMESTAMP(p.published_at) <= UNIX_TIMESTAMP(NOW())
                AND p.published_at IS NOT NULL
             WHERE u.role_id IN (1,2,3,4) AND u.status = 'active'
             GROUP BY u.id
             ORDER BY post_count DESC
             LIMIT ?",
            [$limit]
        );
    }
    
    public static function getRecent(int $limit = 10): array {
        return Database::fetchAll(
            "SELECT u.id, u.username, u.display_name, u.avatar, u.created_at, r.name as role_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             WHERE u.status = 'active'
             ORDER BY u.created_at DESC LIMIT ?",
            [$limit]
        );
    }
    
    public static function count(): int {
        return Database::count('users', "status = 'active'");
    }
}

// ============================================================
// SETTING MODEL
// ============================================================
class Setting {
    
    private static array $cache = [];
    
    public static function get(string $key, $default = null) {
        if (isset(self::$cache[$key])) return self::$cache[$key];
        
        $row = Database::fetchOne("SELECT setting_value, is_json FROM settings WHERE setting_key = ? LIMIT 1", [$key]);
        if (!$row) return $default;
        
        $value = $row['is_json'] ? json_decode($row['setting_value'], true) : $row['setting_value'];
        self::$cache[$key] = $value;
        return $value ?? $default;
    }
    
    public static function set(string $key, $value, string $group = 'general'): void {
        $isJson = is_array($value) || is_object($value);
        $storedValue = $isJson ? json_encode($value) : $value;
        
        $exists = Database::fetchOne("SELECT id FROM settings WHERE setting_key = ? LIMIT 1", [$key]);
        if ($exists) {
            Database::update('settings', [
                'setting_value' => $storedValue,
                'setting_group' => $group,
                'is_json' => $isJson ? 1 : 0
            ], 'setting_key = ?', [$key]);
        } else {
            Database::insert('settings', [
                'setting_key' => $key,
                'setting_value' => $storedValue,
                'setting_group' => $group,
                'is_json' => $isJson ? 1 : 0
            ]);
        }
        self::$cache[$key] = $value;
    }
    
    public static function getGroup(string $group): array {
        $rows = Database::fetchAll("SELECT setting_key, setting_value, is_json FROM settings WHERE setting_group = ?", [$group]);
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['is_json'] ? json_decode($row['setting_value'], true) : $row['setting_value'];
        }
        return $result;
    }
    
    public static function getPublic(): array {
        $rows = Database::fetchAll("SELECT setting_key, setting_value, is_json FROM settings WHERE is_public = 1");
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['is_json'] ? json_decode($row['setting_value'], true) : $row['setting_value'];
        }
        return $result;
    }
}

// ============================================================
// SEO SETTING MODEL
// ============================================================
class SEOSetting {
    
    public static function get(string $key, $default = null) {
        $row = Database::fetchOne("SELECT setting_value FROM seo_settings WHERE setting_key = ? LIMIT 1", [$key]);
        return $row['setting_value'] ?? $default;
    }
    
    public static function set(string $key, string $value, string $group = 'general'): void {
        $exists = Database::fetchOne("SELECT id FROM seo_settings WHERE setting_key = ? LIMIT 1", [$key]);
        if ($exists) {
            Database::update('seo_settings', ['setting_value' => $value, 'setting_group' => $group], 'setting_key = ?', [$key]);
        } else {
            Database::insert('seo_settings', ['setting_key' => $key, 'setting_value' => $value, 'setting_group' => $group]);
        }
    }
    
    public static function getRobotsTxt(): string {
        return self::get('robots_txt', "User-agent: *\nDisallow: /admin/\nDisallow: /api/\nAllow: /\n\nSitemap: /sitemap.xml");
    }
}

// ============================================================
// NEWSLETTER MODEL
// ============================================================
class NewsletterModel {
    
    public static function subscribe(string $email, string $firstName = '', string $lastName = ''): array {
        $email = strtolower(trim($email));
        if (!is_valid_email($email)) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }
        $existing = Database::fetchOne("SELECT id, status FROM subscribers WHERE email = ? LIMIT 1", [$email]);
        
        if ($existing) {
            if ($existing['status'] === 'active') {
                return ['success' => true, 'already' => true, 'message' => 'You are already subscribed to our newsletter.'];
            }
            $token = random_string(32);
            Database::update('subscribers', [
                'status' => 'active',
                'first_name' => sanitize($firstName),
                'last_name' => sanitize($lastName),
                'unsubscribed_at' => null,
                'unsubscribe_token' => hash('sha256', $token),
                'ip_address' => client_ip(),
            ], 'id = ?', [$existing['id']]);
            self::sendConfirmEmail($email, $token, $firstName);
            return ['success' => true, 'message' => 'Welcome back! You are subscribed again.'];
        }
        
        $token = random_string(32);
        Database::insert('subscribers', [
            'email' => sanitize($email),
            'first_name' => sanitize($firstName),
            'last_name' => sanitize($lastName),
            'source' => 'website',
            'ip_address' => client_ip(),
            'unsubscribe_token' => hash('sha256', $token),
            'status' => 'active',
        ]);
        self::sendConfirmEmail($email, $token, $firstName);
        
        return ['success' => true, 'message' => 'Thank you for subscribing! Check your inbox for confirmation.'];
    }

    private static function sendConfirmEmail(string $email, string $token, string $firstName = ''): void {
        if (!class_exists('Mailer')) {
            return;
        }
        try {
            $site = Setting::get('site_name', 'TrendyPost');
            $unsub = url('newsletter/unsubscribe?token=' . urlencode($token));
            $html = '<p>You are subscribed to <strong>' . esc($site) . '</strong>.</p>'
                . '<p>We will send the latest stories to this address.</p>'
                . '<p style="font-size:13px;color:#666;">To unsubscribe anytime: <a href="' . esc($unsub) . '">' . esc($unsub) . '</a></p>';
            Mailer::send($email, 'You are subscribed to ' . $site, $html, $firstName);
        } catch (Throwable $e) {
            error_log('Newsletter confirm email failed: ' . $e->getMessage());
        }
    }
    
    public static function unsubscribe(string $token): bool {
        $token = trim($token);
        if ($token === '') {
            return false;
        }
        $hash = hash('sha256', $token);
        $sub = Database::fetchOne(
            "SELECT id FROM subscribers WHERE unsubscribe_token = ? OR unsubscribe_token = ? LIMIT 1",
            [$hash, $token]
        );
        if (!$sub) return false;
        
        Database::update('subscribers', [
            'status' => 'unsubscribed',
            'unsubscribed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$sub['id']]);
        
        return true;
    }

    public static function unsubscribeByEmail(string $email): bool {
        $email = strtolower(trim($email));
        if (!is_valid_email($email)) {
            return false;
        }
        $sub = Database::fetchOne("SELECT id FROM subscribers WHERE email = ? LIMIT 1", [$email]);
        if (!$sub) {
            return false;
        }
        Database::update('subscribers', [
            'status' => 'unsubscribed',
            'unsubscribed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$sub['id']]);
        return true;
    }
    
    public static function getSubscriberCount(): int {
        return Database::count('subscribers', "status = 'active'");
    }
}

// ============================================================
// ANALYTICS MODEL
// ============================================================
class Analytics {
    
    public static function getDashboardStats(): array {
        $totalPosts = Database::count('posts', "status = 'published'");
        $draftPosts = Database::count('posts', "status = 'draft'");
        $scheduledPosts = Database::count('posts', "status = 'scheduled'");
        
        $totalUsers = Database::count('users', "status = 'active'");
        $totalAuthors = Database::count('users', "status = 'active' AND role_id IN (3,4)");
        
        $totalComments = Database::count('comments');
        $pendingComments = Database::count('comments', "status = 'pending'");
        
        $totalCategories = Database::count('categories', "status = 'active'");
        $totalTags = Database::count('tags', "status = 'active'");
        
        $today = Database::fetchOne("SELECT * FROM site_analytics WHERE date = CURDATE()");
        $subscribers = NewsletterModel::getSubscriberCount();
        
        return [
            'posts' => [
                'total' => $totalPosts,
                'draft' => $draftPosts,
                'scheduled' => $scheduledPosts,
                'published' => $totalPosts
            ],
            'users' => [
                'total' => $totalUsers,
                'authors' => $totalAuthors
            ],
            'comments' => [
                'total' => $totalComments,
                'pending' => $pendingComments
            ],
            'categories' => $totalCategories,
            'tags' => $totalTags,
            'today' => $today,
            'subscribers' => $subscribers
        ];
    }
    
    public static function getRecentViews(int $days = 7): array {
        return Database::fetchAll(
            "SELECT date, page_views, unique_visitors FROM site_analytics 
             WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) 
             ORDER BY date ASC",
            [$days]
        );
    }
    
    public static function getPopularPosts(int $limit = 10): array {
        return Database::fetchAll(
            "SELECT p.id, p.title, p.slug, p.view_count, p.comment_count
             FROM posts p
             WHERE p.status = 'published'
             ORDER BY p.view_count DESC
             LIMIT ?",
            [$limit]
        );
    }
}

// ============================================================
// MENU MODEL
// ============================================================
class Menu {
    
    public static function getByLocation(string $location): ?array {
        return Database::fetchOne(
            "SELECT * FROM menus WHERE location = ? AND is_active = 1 LIMIT 1",
            [$location]
        );
    }
    
    public static function getItems(int $menuId, ?int $parentId = null): array {
        $sql = "SELECT * FROM menu_items WHERE menu_id = ? AND parent_id ";
        $sql .= $parentId === null ? "IS NULL" : "= ?";
        $sql .= " AND is_active = 1 ORDER BY sort_order, title";
        $params = $parentId === null ? [$menuId] : [$menuId, $parentId];
        
        return Database::fetchAll($sql, $params);
    }
}