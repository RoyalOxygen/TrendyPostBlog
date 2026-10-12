-- ============================================================
-- TRENDYPOST - ENTERPRISE SEO BLOGGING PLATFORM
-- Complete Database Schema for M$2y$10$aNSuEINLtiUidHNF58EWSO/./EZFjMGkwvOe2JSD3scQFZij8mwTWSQL 8+
-- ============================================================

CREATE DATABASE IF NOT EXISTS trendypost CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE trendypost;

-- ============================================================
-- 1. USER & ROLE MANAGEMENT (RBAC)
-- ============================================================

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    permissions JSON,
    is_system TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100),
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    avatar VARCHAR(255),
    bio TEXT,
    role_id INT UNSIGNED NOT NULL DEFAULT 6,
    status ENUM('active','inactive','banned','pending') DEFAULT 'pending',
    email_verified TINYINT(1) DEFAULT 0,
    email_verified_at DATETIME,
    remember_token VARCHAR(100),
    reset_token VARCHAR(100),
    reset_expires DATETIME,
    last_login_at DATETIME,
    last_login_ip VARCHAR(45),
    login_attempts TINYINT UNSIGNED DEFAULT 0,
    locked_until DATETIME,
    google_id VARCHAR(100),
    facebook_id VARCHAR(100),
    twitter_id VARCHAR(100),
    seo_title VARCHAR(70),
    seo_description VARCHAR(160),
    social_links JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_role (role_id),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(200) NOT NULL,
    module VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_module (module),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. CONTENT MANAGEMENT
-- ============================================================

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    parent_id INT UNSIGNED DEFAULT NULL,
    image VARCHAR(255),
    color VARCHAR(7) DEFAULT '#00C853',
    seo_title VARCHAR(70),
    seo_description VARCHAR(160),
    seo_keywords VARCHAR(255),
    schema_type VARCHAR(50) DEFAULT 'CollectionPage',
    meta_robots VARCHAR(50) DEFAULT 'index,follow',
    canonical_url VARCHAR(255),
    og_image VARCHAR(255),
    sort_order INT UNSIGNED DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_parent (parent_id),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    seo_title VARCHAR(70),
    seo_description VARCHAR(160),
    seo_keywords VARCHAR(255),
    meta_robots VARCHAR(50) DEFAULT 'index,follow',
    og_image VARCHAR(255),
    post_count INT UNSIGNED DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt TEXT,
    content LONGTEXT,
    featured_image VARCHAR(255),
    featured_image_alt VARCHAR(255),
    featured_image_caption VARCHAR(255),
    author_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED,
    status ENUM('published','draft','scheduled','pending_review','trash') DEFAULT 'draft',
    post_type ENUM('standard','video','audio','gallery','quote') DEFAULT 'standard',
    visibility ENUM('public','password_protected','private') DEFAULT 'public',
    password VARCHAR(255),
    is_featured TINYINT(1) DEFAULT 0,
    is_breaking TINYINT(1) DEFAULT 0,
    is_sticky TINYINT(1) DEFAULT 0,
    allow_comments TINYINT(1) DEFAULT 1,
    comment_count INT UNSIGNED DEFAULT 0,
    view_count INT UNSIGNED DEFAULT 0,
    like_count INT UNSIGNED DEFAULT 0,
    reading_time TINYINT UNSIGNED,
    published_at DATETIME,
    scheduled_at DATETIME,
    password_expires DATETIME,
    -- SEO Fields
    seo_title VARCHAR(70),
    seo_description VARCHAR(160),
    seo_keywords VARCHAR(255),
    focus_keyword VARCHAR(100),
    secondary_keywords VARCHAR(255),
    canonical_url VARCHAR(255),
    meta_robots VARCHAR(50) DEFAULT 'index,follow',
    schema_type VARCHAR(50) DEFAULT 'NewsArticle',
    og_title VARCHAR(200),
    og_description VARCHAR(300),
    og_image VARCHAR(255),
    twitter_card VARCHAR(20) DEFAULT 'summary_large_image',
    twitter_title VARCHAR(200),
    twitter_description VARCHAR(300),
    twitter_image VARCHAR(255),
    -- SEO Scores
    seo_score TINYINT UNSIGNED DEFAULT 0,
    readability_score TINYINT UNSIGNED DEFAULT 0,
    keyword_density DECIMAL(5,2) DEFAULT 0.00,
    internal_links JSON,
    -- Article Rich Snippets
    article_modified_at DATETIME,
    article_section VARCHAR(100),
    article_tags JSON,
    word_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_author (author_id),
    INDEX idx_category (category_id),
    INDEX idx_featured (is_featured),
    INDEX idx_breaking (is_breaking),
    INDEX idx_published (published_at),
    INDEX idx_status_date (status, published_at),
    INDEX idx_type (post_type),
    INDEX idx_created (created_at),
    FULLTEXT INDEX ft_title_content (title, content, excerpt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE posts
    ADD COLUMN source_id INT UNSIGNED DEFAULT NULL AFTER author_id,
    ADD COLUMN original_url VARCHAR(500) DEFAULT NULL AFTER source_id,
    ADD COLUMN original_title VARCHAR(255) DEFAULT NULL AFTER original_url,
    ADD UNIQUE INDEX idx_original_url (original_url);

CREATE TABLE post_tags (
    post_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE post_meta (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    meta_key VARCHAR(100) NOT NULL,
    meta_value LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    INDEX idx_post_key (post_id, meta_key),
    INDEX idx_key (meta_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE post_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED,
    session_id VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    referrer VARCHAR(500),
    country VARCHAR(2),
    device_type ENUM('desktop','mobile','tablet') DEFAULT 'desktop',
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    INDEX idx_post (post_id),
    INDEX idx_ip (ip_address),
    INDEX idx_session (session_id),
    INDEX idx_date (viewed_at),
    INDEX idx_post_date (post_id, viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. PAGES SYSTEM
-- ============================================================

CREATE TABLE pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content LONGTEXT,
    excerpt TEXT,
    featured_image VARCHAR(255),
    template VARCHAR(50) DEFAULT 'default',
    author_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    status ENUM('published','draft') DEFAULT 'draft',
    menu_order INT UNSIGNED DEFAULT 0,
    show_in_menu TINYINT(1) DEFAULT 0,
    is_homepage TINYINT(1) DEFAULT 0,
    allow_comments TINYINT(1) DEFAULT 0,
    seo_title VARCHAR(70),
    seo_description VARCHAR(160),
    seo_keywords VARCHAR(255),
    canonical_url VARCHAR(255),
    meta_robots VARCHAR(50) DEFAULT 'index,follow',
    schema_type VARCHAR(50) DEFAULT 'WebPage',
    og_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (parent_id) REFERENCES pages(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_menu (show_in_menu, menu_order),
    INDEX idx_homepage (is_homepage),
    FULLTEXT INDEX ft_content (title, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3B. AI SOURCE INGEST
-- ============================================================

CREATE TABLE ai_sources (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    url VARCHAR(500) NOT NULL,
    frequency_minutes INT UNSIGNED NOT NULL DEFAULT 360,
    max_posts TINYINT UNSIGNED NOT NULL DEFAULT 5,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    last_checked_at DATETIME DEFAULT NULL,
    last_status VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_enabled (enabled),
    INDEX idx_last_checked (last_checked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ai_source_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_id INT UNSIGNED DEFAULT NULL,
    message TEXT NOT NULL,
    level ENUM('info','warn','error') DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (source_id) REFERENCES ai_sources(id) ON DELETE SET NULL,
    INDEX idx_source (source_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE posts
    ADD CONSTRAINT fk_posts_source FOREIGN KEY (source_id) REFERENCES ai_sources(id) ON DELETE SET NULL;

-- ============================================================
-- 4. COMMENTS SYSTEM
-- ============================================================

CREATE TABLE comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    user_id INT UNSIGNED,
    author_name VARCHAR(100),
    author_email VARCHAR(100),
    author_website VARCHAR(255),
    author_ip VARCHAR(45),
    content TEXT NOT NULL,
    status ENUM('approved','pending','spam','trash') DEFAULT 'pending',
    is_pinned TINYINT(1) DEFAULT 0,
    like_count INT UNSIGNED DEFAULT 0,
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_post (post_id),
    INDEX idx_status (status),
    INDEX idx_parent (parent_id),
    INDEX idx_created (created_at),
    INDEX idx_post_status (post_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. MEDIA LIBRARY
-- ============================================================

CREATE TABLE media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255),
    file_path VARCHAR(500) NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    mime_type VARCHAR(100),
    file_size INT UNSIGNED,
    width INT UNSIGNED,
    height INT UNSIGNED,
    alt_text VARCHAR(255),
    caption TEXT,
    description TEXT,
    uploaded_by INT UNSIGNED,
    is_featured TINYINT(1) DEFAULT 0,
    usage_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_type (file_type),
    INDEX idx_filename (filename),
    INDEX idx_uploaded (uploaded_by),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. ADVERTISEMENT MANAGEMENT
-- ============================================================

CREATE TABLE ad_networks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    code VARCHAR(50),
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ad_placements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    location ENUM('header','footer','sidebar','in_content','before_content','after_content','sticky_sidebar','floating','popunder','vignette','native','related_content','breaking_news','breadcrumb','after_paragraph_2','after_paragraph_5','mobile_only','desktop_only') NOT NULL,
    device_target ENUM('all','desktop','mobile','tablet') DEFAULT 'all',
    priority INT UNSIGNED DEFAULT 0,
    max_ads INT UNSIGNED DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_location (location),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE advertisements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    network_id INT UNSIGNED,
    placement_id INT UNSIGNED NOT NULL,
    ad_type ENUM('display','native','smartlink','in_page_push','push_notification','vignette','popunder','banner','video','multitag','custom_html','affiliate','google_adsense') DEFAULT 'display',
    ad_code TEXT,
    custom_html LONGTEXT,
    image VARCHAR(255),
    link_url VARCHAR(500),
    target_blank TINYINT(1) DEFAULT 1,
   nofollow TINYINT(1) DEFAULT 1,
    start_date DATE,
    end_date DATE,
    display_rules JSON,
    impression_count INT UNSIGNED DEFAULT 0,
    click_count INT UNSIGNED DEFAULT 0,
    revenue DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active','paused','scheduled','expired') DEFAULT 'active',
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (network_id) REFERENCES ad_networks(id) ON DELETE SET NULL,
    FOREIGN KEY (placement_id) REFERENCES ad_placements(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_placement (placement_id),
    INDEX idx_network (network_id),
    INDEX idx_type (ad_type),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ad_statistics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ad_id INT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    impressions INT UNSIGNED DEFAULT 0,
    clicks INT UNSIGNED DEFAULT 0,
    revenue DECIMAL(10,2) DEFAULT 0.00,
    ctr DECIMAL(5,2) DEFAULT 0.00,
    device_type ENUM('desktop','mobile','tablet') DEFAULT 'desktop',
    country VARCHAR(2),
    FOREIGN KEY (ad_id) REFERENCES advertisements(id) ON DELETE CASCADE,
    INDEX idx_ad_date (ad_id, date),
    INDEX idx_date (date),
    UNIQUE KEY uk_ad_date_device (ad_id, date, device_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. SEO SETTINGS & REDIRECTS
-- ============================================================

CREATE TABLE seo_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    is_json TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key),
    INDEX idx_group (setting_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE redirects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    old_url VARCHAR(500) NOT NULL,
    new_url VARCHAR(500) NOT NULL,
    type ENUM('301','302','307') DEFAULT '301',
    hits INT UNSIGNED DEFAULT 0,
    last_used DATETIME,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_old_url (old_url),
    INDEX idx_type (type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. NEWSLETTER SYSTEM
-- ============================================================

CREATE TABLE subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    status ENUM('active','unsubscribed','bounced','complained') DEFAULT 'active',
    source VARCHAR(50) DEFAULT 'website',
    ip_address VARCHAR(45),
    verified_at DATETIME,
    unsubscribe_token VARCHAR(100),
    unsubscribed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE newsletters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    content LONGTEXT,
    template VARCHAR(50) DEFAULT 'default',
    status ENUM('draft','scheduled','sending','sent','paused') DEFAULT 'draft',
    scheduled_at DATETIME,
    sent_at DATETIME,
    sent_count INT UNSIGNED DEFAULT 0,
    open_count INT UNSIGNED DEFAULT 0,
    click_count INT UNSIGNED DEFAULT 0,
    bounce_count INT UNSIGNED DEFAULT 0,
    unsubscribe_count INT UNSIGNED DEFAULT 0,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_scheduled (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. SITE SETTINGS
-- ============================================================

CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    is_public TINYINT(1) DEFAULT 0,
    is_json TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key),
    INDEX idx_group (setting_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. ANALYTICS & AUDIT LOGS
-- ============================================================

CREATE TABLE site_analytics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    page_views INT UNSIGNED DEFAULT 0,
    unique_visitors INT UNSIGNED DEFAULT 0,
    sessions INT UNSIGNED DEFAULT 0,
    bounce_rate DECIMAL(5,2) DEFAULT 0.00,
    avg_session_duration INT UNSIGNED DEFAULT 0,
    organic_traffic INT UNSIGNED DEFAULT 0,
    referral_traffic INT UNSIGNED DEFAULT 0,
    direct_traffic INT UNSIGNED DEFAULT 0,
    social_traffic INT UNSIGNED DEFAULT 0,
    new_visitors INT UNSIGNED DEFAULT 0,
    returning_visitors INT UNSIGNED DEFAULT 0,
    device_desktop INT UNSIGNED DEFAULT 0,
    device_mobile INT UNSIGNED DEFAULT 0,
    device_tablet INT UNSIGNED DEFAULT 0,
    UNIQUE KEY uk_date (date),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT UNSIGNED,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_module (module),
    INDEX idx_created (created_at),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. MENUS
-- ============================================================

CREATE TABLE menus (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    location VARCHAR(50),
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_location (location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(100) NOT NULL,
    url VARCHAR(500),
    target ENUM('_self','_blank') DEFAULT '_self',
    icon VARCHAR(50),
    css_class VARCHAR(100),
    sort_order INT UNSIGNED DEFAULT 0,
    item_type ENUM('custom','page','category','post','tag') DEFAULT 'custom',
    linked_id INT UNSIGNED,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    INDEX idx_menu (menu_id),
    INDEX idx_parent (parent_id),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. NOTIFICATIONS
-- ============================================================

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    link VARCHAR(500),
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. BACKUP & MAINTENANCE
-- ============================================================

CREATE TABLE backups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT UNSIGNED,
    backup_type ENUM('full','database','files') DEFAULT 'full',
    tables LONGTEXT,
    status ENUM('pending','in_progress','completed','failed') DEFAULT 'pending',
    created_by INT UNSIGNED,
    completed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_type (backup_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. CONTACT MESSAGES
-- ============================================================

CREATE TABLE contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    ip_address VARCHAR(45),
    status ENUM('new','read','replied','archived') DEFAULT 'new',
    replied_at DATETIME,
    replied_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (replied_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Insert default roles
INSERT INTO roles (name, display_name, description, is_system) VALUES
('super_admin', 'Super Admin', 'Complete control over the entire platform', 1),
('admin', 'Admin', 'Can manage most platform features', 1),
('editor', 'Editor', 'Can review, edit and publish content', 1),
('author', 'Author', 'Can create and manage own posts', 1),
('contributor', 'Contributor', 'Can create posts for review only', 1),
('subscriber', 'Subscriber', 'Registered user with commenting access', 1);

-- Insert default permissions
INSERT INTO permissions (name, display_name, module, description) VALUES
('dashboard.view', 'View Dashboard', 'dashboard', 'Access admin dashboard'),
('posts.view', 'View Posts', 'posts', 'View all posts'),
('posts.create', 'Create Posts', 'posts', 'Create new posts'),
('posts.edit', 'Edit Posts', 'posts', 'Edit any post'),
('posts.delete', 'Delete Posts', 'posts', 'Delete any post'),
('posts.publish', 'Publish Posts', 'posts', 'Publish posts'),
('categories.manage', 'Manage Categories', 'categories', 'Create and manage categories'),
('tags.manage', 'Manage Tags', 'tags', 'Create and manage tags'),
('pages.manage', 'Manage Pages', 'pages', 'Create and manage pages'),
('comments.moderate', 'Moderate Comments', 'comments', 'Approve, reject, delete comments'),
('media.manage', 'Manage Media', 'media', 'Upload and manage media files'),
('users.view', 'View Users', 'users', 'View user list'),
('users.create', 'Create Users', 'users', 'Create new users'),
('users.edit', 'Edit Users', 'users', 'Edit user profiles'),
('users.delete', 'Delete Users', 'users', 'Delete users'),
('roles.manage', 'Manage Roles', 'roles', 'Manage roles and permissions'),
('ads.manage', 'Manage Advertisements', 'ads', 'Create and manage ads'),
('seo.manage', 'Manage SEO', 'seo', 'Manage SEO settings'),
('analytics.view', 'View Analytics', 'analytics', 'View site analytics'),
('newsletters.manage', 'Manage Newsletters', 'newsletters', 'Create and send newsletters'),
('subscribers.manage', 'Manage Subscribers', 'subscribers', 'Manage email subscribers'),
('settings.manage', 'Manage Settings', 'settings', 'Manage site settings'),
('menus.manage', 'Manage Menus', 'menus', 'Manage navigation menus'),
('backups.manage', 'Manage Backups', 'backups', 'Create and restore backups'),
('audit.view', 'View Audit Logs', 'audit', 'View system audit logs'),
('contact.view', 'View Contact Messages', 'contact', 'View contact form submissions'),
('ai.sources', 'Manage AI Sources', 'ai', 'Add source websites and run AI ingest into drafts');

-- Assign all permissions to Super Admin
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Assign permissions to Admin (all except roles.manage and audit.view)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE name NOT IN ('roles.manage', 'audit.view');

-- Editor permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE name IN 
('dashboard.view', 'posts.view', 'posts.edit', 'posts.publish', 'comments.moderate', 'media.manage', 'categories.manage', 'tags.manage', 'pages.manage', 'newsletters.manage', 'subscribers.manage', 'ai.sources');

-- Author permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE name IN 
('dashboard.view', 'posts.create', 'posts.edit', 'media.manage', 'comments.moderate');

-- Contributor permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE name IN 
('dashboard.view', 'posts.create', 'media.manage');

-- Insert default ad networks
INSERT INTO ad_networks (name, slug, code, description) VALUES
('Google AdSense', 'adsense', 'adsense', 'Google AdSense programmatic ads'),
('Adsterra', 'adsterra', 'adsterra', 'Adsterra ad network'),
('Monetag', 'monetag', 'monetag', 'Monetag ad network'),
('Media.net', 'media_net', 'medianet', 'Media.net contextual ads'),
('Ezoic', 'ezoic', 'ezoic', 'Ezoic AI-driven ad optimization'),
('PropellerAds', 'propellerads', 'propeller', 'PropellerAds push and native'),
('Custom HTML', 'custom', 'custom', 'Custom HTML advertisement code');

-- Insert default ad placements
INSERT INTO ad_placements (name, slug, description, location, device_target, priority) VALUES
('Header Banner', 'header_banner', 'Ad displayed in the header area', 'header', 'all', 1),
('Footer Banner', 'footer_banner', 'Ad displayed in the footer area', 'footer', 'all', 1),
('Sidebar Top', 'sidebar_top', 'Ad displayed at top of sidebar', 'sidebar', 'all', 2),
('Sidebar Bottom', 'sidebar_bottom', 'Ad displayed at bottom of sidebar', 'sidebar', 'all', 2),
('Sticky Sidebar', 'sticky_sidebar', 'Sticky ad that follows scroll in sidebar', 'sticky_sidebar', 'all', 3),
('Before Content', 'before_content', 'Ad displayed before article content', 'before_content', 'all', 4),
('After Content', 'after_content', 'Ad displayed after article content', 'after_content', 'all', 4),
('After Paragraph 2', 'after_para_2', 'Ad injected after 2nd paragraph', 'after_paragraph_2', 'all', 5),
('After Paragraph 5', 'after_para_5', 'Ad injected after 5th paragraph', 'after_paragraph_5', 'all', 5),
('In Content Auto', 'in_content_auto', 'Auto-injected in-content ads', 'in_content', 'all', 5),
('Floating Bottom', 'floating_bottom', 'Floating ad at bottom of screen', 'floating', 'all', 6),
('Mobile Header', 'mobile_header', 'Ad for mobile header only', 'header', 'mobile', 1),
('Mobile Footer', 'mobile_footer', 'Ad for mobile footer only', 'footer', 'mobile', 1),
('Desktop Sidebar', 'desktop_sidebar', 'Ad for desktop sidebar only', 'sidebar', 'desktop', 2),
('Breaking News', 'breaking_news', 'Ad in breaking news ticker area', 'breaking_news', 'all', 7),
('Native In-Feed', 'native_infeed', 'Native ad within post listings', 'related_content', 'all', 8);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, setting_group, is_public) VALUES
('site_name', 'TrendyPost', 'general', 1),
('site_tagline', 'Your Source for Trending News & Stories', 'general', 1),
('site_logo', 'assets/images/logo.png', 'general', 1),
('site_favicon', 'assets/images/favicon.ico', 'general', 1),
('site_email', 'admin@trendypost.com', 'general', 1),
('site_phone', '', 'general', 1),
('site_address', '', 'general', 0),
('footer_about', 'TrendyPost is your premier destination for trending news, insightful articles, and engaging stories across technology, lifestyle, business, and more.', 'general', 1),
('footer_copyright', '© 2026 TrendyPost. All rights reserved.', 'general', 1),
('posts_per_page', '10', 'content', 1),
('posts_per_home', '6', 'content', 1),
('excerpt_length', '200', 'content', 1),
('show_author_box', '1', 'content', 1),
('show_related_posts', '1', 'content', 1),
('related_posts_count', '4', 'content', 1),
('comment_moderation', '1', 'comments', 1),
('allow_guest_comments', '1', 'comments', 1),
('nested_comments', '3', 'comments', 1),
('social_facebook', 'https://facebook.com/trendypost', 'social', 1),
('social_twitter', 'https://twitter.com/trendypost', 'social', 1),
('social_instagram', 'https://instagram.com/trendypost', 'social', 1),
('social_youtube', '', 'social', 1),
('social_linkedin', '', 'social', 1),
('social_pinterest', '', 'social', 1),
('social_tiktok', '', 'social', 1),
('google_analytics_id', '', 'analytics', 0),
('google_tag_manager', '', 'analytics', 0),
('google_search_console', '', 'seo', 0),
('google_adsense_id', '', 'monetization', 0),
('adsterra_key', '', 'monetization', 0),
('monetag_key', '', 'monetization', 0),
('seo_default_title', 'TrendyPost - Trending News & Stories', 'seo', 1),
('seo_default_description', 'Discover the latest trending news, insightful articles, and engaging stories at TrendyPost.', 'seo', 1),
('seo_default_keywords', 'news, trending, blog, articles, stories', 'seo', 1),
('seo_default_robots', 'index,follow', 'seo', 1),
('seo_schema_org', '1', 'seo', 0),
('seo_open_graph', '1', 'seo', 0),
('seo_twitter_cards', '1', 'seo', 0),
('seo_breadcrumbs', '1', 'seo', 0),
('smtp_host', '', 'email', 0),
('smtp_port', '587', 'email', 0),
('smtp_username', '', 'email', 0),
('smtp_password', '', 'email', 0),
('smtp_encryption', 'tls', 'email', 0),
('smtp_from_email', 'noreply@trendypost.com', 'email', 0),
('smtp_from_name', 'TrendyPost', 'email', 0),
('notify_welcome_email', '1', 'email', 0),
('notify_on_comment', '1', 'email', 0),
('notify_on_publish', '1', 'email', 0),
('notify_on_contact', '1', 'email', 0),
('maintenance_mode', '0', 'system', 0),
('registration_enabled', '1', 'system', 1),
('cache_enabled', '1', 'system', 0),
('cache_duration', '3600', 'system', 0),
('gzip_enabled', '1', 'system', 0),
('lazy_loading', '1', 'system', 1),
('webp_conversion', '1', 'system', 0),
('dark_mode_enabled', '0', 'appearance', 1),
('primary_color', '#00C853', 'appearance', 1),
('secondary_color', '#1a1a1a', 'appearance', 1),
('header_style', 'default', 'appearance', 1),
('footer_style', 'default', 'appearance', 1),
('homepage_layout', 'magazine', 'appearance', 1),
('max_login_attempts', '5', 'security', 0),
('lockout_duration', '900', 'security', 0),
('user_llm_api_key', '', 'ai', 0),
('user_llm_base_url', 'https://generativelanguage.googleapis.com', 'ai', 0),
('user_llm_model', 'gemini-2.0-flash', 'ai', 0),
('gemini_api_keys', '', 'ai', 0),
('gemini_model', 'gemini-2.0-flash', 'ai', 0);

-- Insert default SEO settings
INSERT INTO seo_settings (setting_key, setting_value, setting_group) VALUES
('google_verification', '', 'search_console'),
('bing_verification', '', 'search_console'),
('yandex_verification', '', 'search_console'),
('baidu_verification', '', 'search_console'),
('google_analytics_4', '', 'analytics'),
('gtm_container_id', '', 'analytics'),
('facebook_app_id', '', 'social'),
('facebook_pixel', '', 'analytics'),
('twitter_site', '@trendypost', 'social'),
('structured_data_type', 'NewsMediaOrganization', 'schema'),
('organization_name', 'TrendyPost', 'schema'),
('organization_logo', '', 'schema'),
('organization_phone', '', 'schema'),
('organization_email', '', 'schema'),
('organization_address', '', 'schema'),
('sitemap_include_images', '1', 'sitemap'),
('sitemap_include_videos', '1', 'sitemap'),
('sitemap_include_news', '1', 'sitemap'),
('sitemap_posts_limit', '1000', 'sitemap'),
('sitemap_auto_ping', '1', 'sitemap'),
('robots_txt', 'User-agent: *\nDisallow: /admin/\nDisallow: /api/\nDisallow: /install/\nAllow: /\n\nSitemap: /sitemap.xml', 'robots'),
('auto_indexing', '1', 'indexing'),
('indexing_api_key', '', 'indexing'),
('breadcrumb_home', 'Home', 'breadcrumbs'),
('breadcrumb_separator', '>', 'breadcrumbs'),
('enable_amp', '0', 'amp'),
('canonical_auto', '1', 'advanced'),
('nofollow_external', '1', 'advanced'),
('open_external_new_tab', '1', 'advanced'),
('seo_score_threshold', '70', 'advanced');

-- Insert default menu
INSERT INTO menus (name, slug, location, description, is_active) VALUES
('Main Navigation', 'main_nav', 'header', 'Primary site navigation', 1),
('Footer Links', 'footer_nav', 'footer', 'Footer navigation links', 1),
('Social Links', 'social_nav', 'social', 'Social media links', 1);

-- Insert sample menu items for main navigation
INSERT INTO menu_items (menu_id, title, url, sort_order, item_type) VALUES
(1, 'Home', '/', 1, 'custom'),
(1, 'Archive', '/archive', 2, 'custom'),
(1, 'Category', '/categories', 3, 'custom'),
(1, 'Post Types', '#', 4, 'custom'),
(1, 'About', '/page/about-us', 5, 'page'),
(1, 'Contact', '/page/contact', 6, 'page');

-- Create default super admin user (password: Admin@123)
INSERT INTO users (username, email, password_hash, display_name, first_name, last_name, role_id, status, email_verified) VALUES
('admin', 'admin@trendypost.com', '$2y$10$/x8mok40GANaeM8vlHnUsOGhi/TIiQxEGwvD5zcs9kApDAEYaYkjq', 'Super Admin', 'Super', 'Admin', 1, 'active', 1);

-- Insert default pages
INSERT INTO pages (title, slug, content, excerpt, author_id, status, show_in_menu, menu_order, is_homepage, seo_title, seo_description, allow_comments) VALUES
('About Us', 'about-us', '<h2>Welcome to TrendyPost</h2><p>TrendyPost is a leading digital publishing platform dedicated to delivering the latest trending news, insightful articles, and engaging stories across multiple categories including technology, lifestyle, business, entertainment, and more.</p><p>Our mission is to keep you informed, entertained, and inspired with high-quality content crafted by our team of experienced writers and editors.</p><h3>Our Story</h3><p>Founded in 2024, TrendyPost has grown from a small blog to a comprehensive digital publishing platform reaching millions of readers worldwide.</p><h3>Our Values</h3><ul><li>Accuracy and integrity in reporting</li><li>Diverse perspectives and voices</li><li>Reader-first approach</li><li>Innovation in digital storytelling</li></ul>', 'Learn more about TrendyPost, our mission, values, and the team behind the stories.', 1, 'published', 1, 1, 0, 'About Us | TrendyPost', 'Learn about TrendyPost - your premier destination for trending news and stories.', 0),
('Contact Us', 'contact', '<h2>Get in Touch</h2><p>We would love to hear from you. Whether you have a question, feedback, or a story tip, feel free to reach out to us.</p><h3>Contact Information</h3><p><strong>Email:</strong> contact@trendypost.com</p><p><strong>Phone:</strong> +1 (555) 123-4567</p><p><strong>Address:</strong> 123 Digital Street, Tech City, TC 12345</p>', 'Contact TrendyPost for inquiries, feedback, and collaboration opportunities.', 1, 'published', 1, 2, 0, 'Contact Us | TrendyPost', 'Get in touch with the TrendyPost team.', 0),
('Privacy Policy', 'privacy-policy', '<h2>Privacy Policy</h2><p>At TrendyPost, we take your privacy seriously. This Privacy Policy explains how we collect, use, and protect your personal information.</p><h3>Information We Collect</h3><p>We collect information you provide directly to us, such as when you create an account, subscribe to our newsletter, or contact us.</p><h3>How We Use Your Information</h3><p>We use the information we collect to provide, maintain, and improve our services, and to communicate with you.</p>', 'Read TrendyPost privacy policy to understand how we handle your data.', 1, 'published', 0, 0, 0, 'Privacy Policy | TrendyPost', 'TrendyPost privacy policy and data protection practices.', 0),
('Terms of Service', 'terms-of-service', '<h2>Terms of Service</h2><p>By accessing and using TrendyPost, you agree to these Terms of Service.</p><h3>Use of Content</h3><p>All content on TrendyPost is protected by copyright. You may not reproduce, distribute, or create derivative works without permission.</p>', 'Read the Terms of Service for using TrendyPost.', 1, 'published', 0, 0, 0, 'Terms of Service | TrendyPost', 'Terms and conditions for using TrendyPost.', 0);

-- Default categories used by AI ingest and the magazine home
INSERT INTO categories (name, slug, description, color, status, sort_order) VALUES
('News', 'news', 'Latest news and headlines', '#00C853', 'active', 1),
('Politics', 'politics', 'Politics and government', '#1565C0', 'active', 2),
('Business', 'business', 'Markets, economy and business', '#EF6C00', 'active', 3),
('Sports', 'sports', 'Sports news', '#2E7D32', 'active', 4),
('Tech', 'tech', 'Technology and innovation', '#6A1B9A', 'active', 5),
('Entertainment', 'entertainment', 'Entertainment and culture', '#C2185B', 'active', 6),
('Health', 'health', 'Health and wellness', '#00838F', 'active', 7),
('World', 'world', 'World news', '#37474F', 'active', 8);

-- Default AI source
INSERT INTO ai_sources (name, url, frequency_minutes, max_posts, enabled) VALUES
('Punch NG', 'https://punchng.com/', 360, 5, 1);


