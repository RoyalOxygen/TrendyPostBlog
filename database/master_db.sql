-- ============================================================
-- TRENDYPOST - MASTER DATABASE SCHEMA
-- Complete schema for a fresh install (MariaDB / MySQL 8+)
-- Includes all live tables, indexes, foreign keys, and default seed data.
-- Does not include user content (posts, comments, media, logs).
-- Default admin: admin@trendypost.com / Admin@123
-- ============================================================

CREATE DATABASE IF NOT EXISTS trendypost CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE trendypost;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `ad_networks`;
CREATE TABLE `ad_networks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `ad_placements`;
CREATE TABLE `ad_placements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `location` enum('header','footer','sidebar','in_content','before_content','after_content','sticky_sidebar','floating','popunder','vignette','native','related_content','breaking_news','breadcrumb','after_paragraph_2','after_paragraph_5','mobile_only','desktop_only') NOT NULL,
  `device_target` enum('all','desktop','mobile','tablet') DEFAULT 'all',
  `priority` int(10) unsigned DEFAULT 0,
  `max_ads` int(10) unsigned DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`),
  KEY `idx_location` (`location`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `ad_statistics`;
CREATE TABLE `ad_statistics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ad_id` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `impressions` int(10) unsigned DEFAULT 0,
  `clicks` int(10) unsigned DEFAULT 0,
  `revenue` decimal(10,2) DEFAULT 0.00,
  `ctr` decimal(5,2) DEFAULT 0.00,
  `device_type` enum('desktop','mobile','tablet') DEFAULT 'desktop',
  `country` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ad_date_device` (`ad_id`,`date`,`device_type`),
  KEY `idx_ad_date` (`ad_id`,`date`),
  KEY `idx_date` (`date`),
  CONSTRAINT `ad_statistics_ibfk_1` FOREIGN KEY (`ad_id`) REFERENCES `advertisements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `advertisements`;
CREATE TABLE `advertisements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `network_id` int(10) unsigned DEFAULT NULL,
  `placement_id` int(10) unsigned NOT NULL,
  `ad_type` enum('display','native','smartlink','in_page_push','push_notification','vignette','popunder','banner','video','multitag','custom_html','affiliate','google_adsense') DEFAULT 'display',
  `ad_code` text DEFAULT NULL,
  `custom_html` longtext DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `target_blank` tinyint(1) DEFAULT 1,
  `nofollow` tinyint(1) DEFAULT 1,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `display_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`display_rules`)),
  `impression_count` int(10) unsigned DEFAULT 0,
  `click_count` int(10) unsigned DEFAULT 0,
  `revenue` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','paused','scheduled','expired') DEFAULT 'active',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_status` (`status`),
  KEY `idx_placement` (`placement_id`),
  KEY `idx_network` (`network_id`),
  KEY `idx_type` (`ad_type`),
  KEY `idx_dates` (`start_date`,`end_date`),
  CONSTRAINT `advertisements_ibfk_1` FOREIGN KEY (`network_id`) REFERENCES `ad_networks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `advertisements_ibfk_2` FOREIGN KEY (`placement_id`) REFERENCES `ad_placements` (`id`),
  CONSTRAINT `advertisements_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `ai_source_logs`;
CREATE TABLE `ai_source_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int(10) unsigned DEFAULT NULL,
  `message` text NOT NULL,
  `level` enum('info','warn','error') DEFAULT 'info',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_source` (`source_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `ai_source_logs_ibfk_1` FOREIGN KEY (`source_id`) REFERENCES `ai_sources` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `ai_sources`;
CREATE TABLE `ai_sources` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `url` varchar(500) NOT NULL,
  `frequency_minutes` int(10) unsigned NOT NULL DEFAULT 360,
  `max_posts` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `last_checked_at` datetime DEFAULT NULL,
  `last_status` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_enabled` (`enabled`),
  KEY `idx_last_checked` (`last_checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(10) unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_module` (`module`),
  KEY `idx_created` (`created_at`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `backups`;
CREATE TABLE `backups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(10) unsigned DEFAULT NULL,
  `backup_type` enum('full','database','files') DEFAULT 'full',
  `tables` longtext DEFAULT NULL,
  `status` enum('pending','in_progress','completed','failed') DEFAULT 'pending',
  `created_by` int(10) unsigned DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_type` (`backup_type`),
  KEY `idx_status` (`status`),
  CONSTRAINT `backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#00C853',
  `seo_title` varchar(70) DEFAULT NULL,
  `seo_description` varchar(160) DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `schema_type` varchar(50) DEFAULT 'CollectionPage',
  `meta_robots` varchar(50) DEFAULT 'index,follow',
  `canonical_url` varchar(255) DEFAULT NULL,
  `og_image` varchar(255) DEFAULT NULL,
  `sort_order` int(10) unsigned DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_sort` (`sort_order`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `author_name` varchar(100) DEFAULT NULL,
  `author_email` varchar(100) DEFAULT NULL,
  `author_website` varchar(255) DEFAULT NULL,
  `author_ip` varchar(45) DEFAULT NULL,
  `content` text NOT NULL,
  `status` enum('approved','pending','spam','trash') DEFAULT 'pending',
  `is_pinned` tinyint(1) DEFAULT 0,
  `like_count` int(10) unsigned DEFAULT 0,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_post` (`post_id`),
  KEY `idx_status` (`status`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_post_status` (`post_id`,`status`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE `contact_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('new','read','replied','archived') DEFAULT 'new',
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `replied_by` (`replied_by`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `contact_messages_ibfk_1` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `media`;
CREATE TABLE `media` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(10) unsigned DEFAULT NULL,
  `width` int(10) unsigned DEFAULT NULL,
  `height` int(10) unsigned DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `uploaded_by` int(10) unsigned DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `usage_count` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_type` (`file_type`),
  KEY `idx_filename` (`filename`),
  KEY `idx_uploaded` (`uploaded_by`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `media_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE `menu_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `target` enum('_self','_blank') DEFAULT '_self',
  `icon` varchar(50) DEFAULT NULL,
  `css_class` varchar(100) DEFAULT NULL,
  `sort_order` int(10) unsigned DEFAULT 0,
  `item_type` enum('custom','page','category','post','tag') DEFAULT 'custom',
  `linked_id` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_menu` (`menu_id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_sort` (`sort_order`),
  CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `menu_items_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `menus`;
CREATE TABLE `menus` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `location` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`),
  KEY `idx_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `newsletters`;
CREATE TABLE `newsletters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `template` varchar(50) DEFAULT 'default',
  `status` enum('draft','scheduled','sending','sent','paused') DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `sent_count` int(10) unsigned DEFAULT 0,
  `open_count` int(10) unsigned DEFAULT 0,
  `click_count` int(10) unsigned DEFAULT 0,
  `bounce_count` int(10) unsigned DEFAULT 0,
  `unsubscribe_count` int(10) unsigned DEFAULT 0,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_status` (`status`),
  KEY `idx_scheduled` (`scheduled_at`),
  CONSTRAINT `newsletters_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_read` (`is_read`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `template` varchar(50) DEFAULT 'default',
  `author_id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'draft',
  `menu_order` int(10) unsigned DEFAULT 0,
  `show_in_menu` tinyint(1) DEFAULT 0,
  `is_homepage` tinyint(1) DEFAULT 0,
  `allow_comments` tinyint(1) DEFAULT 0,
  `seo_title` varchar(70) DEFAULT NULL,
  `seo_description` varchar(160) DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `canonical_url` varchar(255) DEFAULT NULL,
  `meta_robots` varchar(50) DEFAULT 'index,follow',
  `schema_type` varchar(50) DEFAULT 'WebPage',
  `og_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `author_id` (`author_id`),
  KEY `parent_id` (`parent_id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_menu` (`show_in_menu`,`menu_order`),
  KEY `idx_homepage` (`is_homepage`),
  FULLTEXT KEY `ft_content` (`title`,`content`),
  CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`),
  CONSTRAINT `pages_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(200) NOT NULL,
  `module` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_module` (`module`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `post_meta`;
CREATE TABLE `post_meta` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `meta_key` varchar(100) NOT NULL,
  `meta_value` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_post_key` (`post_id`,`meta_key`),
  KEY `idx_key` (`meta_key`),
  CONSTRAINT `post_meta_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `post_tags`;
CREATE TABLE `post_tags` (
  `post_id` int(10) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`post_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `post_tags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `post_views`;
CREATE TABLE `post_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet') DEFAULT 'desktop',
  `viewed_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_post` (`post_id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_session` (`session_id`),
  KEY `idx_date` (`viewed_at`),
  KEY `idx_post_date` (`post_id`,`viewed_at`),
  CONSTRAINT `post_views_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `featured_image_alt` varchar(255) DEFAULT NULL,
  `featured_image_caption` varchar(255) DEFAULT NULL,
  `author_id` int(10) unsigned NOT NULL,
  `source_id` int(10) unsigned DEFAULT NULL,
  `original_url` varchar(500) DEFAULT NULL,
  `original_title` varchar(255) DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `status` enum('published','draft','scheduled','pending_review','trash') DEFAULT 'draft',
  `post_type` enum('standard','video','audio','gallery','quote') DEFAULT 'standard',
  `visibility` enum('public','password_protected','private') DEFAULT 'public',
  `password` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_breaking` tinyint(1) DEFAULT 0,
  `is_sticky` tinyint(1) DEFAULT 0,
  `allow_comments` tinyint(1) DEFAULT 1,
  `comment_count` int(10) unsigned DEFAULT 0,
  `view_count` int(10) unsigned DEFAULT 0,
  `like_count` int(10) unsigned DEFAULT 0,
  `reading_time` tinyint(3) unsigned DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `password_expires` datetime DEFAULT NULL,
  `seo_title` varchar(70) DEFAULT NULL,
  `seo_description` varchar(160) DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `focus_keyword` varchar(100) DEFAULT NULL,
  `secondary_keywords` varchar(255) DEFAULT NULL,
  `canonical_url` varchar(255) DEFAULT NULL,
  `meta_robots` varchar(50) DEFAULT 'index,follow',
  `schema_type` varchar(50) DEFAULT 'NewsArticle',
  `og_title` varchar(200) DEFAULT NULL,
  `og_description` varchar(300) DEFAULT NULL,
  `og_image` varchar(255) DEFAULT NULL,
  `twitter_card` varchar(20) DEFAULT 'summary_large_image',
  `twitter_title` varchar(200) DEFAULT NULL,
  `twitter_description` varchar(300) DEFAULT NULL,
  `twitter_image` varchar(255) DEFAULT NULL,
  `seo_score` tinyint(3) unsigned DEFAULT 0,
  `readability_score` tinyint(3) unsigned DEFAULT 0,
  `keyword_density` decimal(5,2) DEFAULT 0.00,
  `internal_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`internal_links`)),
  `article_modified_at` datetime DEFAULT NULL,
  `article_section` varchar(100) DEFAULT NULL,
  `article_tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`article_tags`)),
  `word_count` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `idx_original_url` (`original_url`),
  KEY `idx_slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_author` (`author_id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_breaking` (`is_breaking`),
  KEY `idx_published` (`published_at`),
  KEY `idx_status_date` (`status`,`published_at`),
  KEY `idx_type` (`post_type`),
  KEY `idx_created` (`created_at`),
  KEY `fk_posts_source` (`source_id`),
  FULLTEXT KEY `ft_title_content` (`title`,`content`,`excerpt`),
  CONSTRAINT `fk_posts_source` FOREIGN KEY (`source_id`) REFERENCES `ai_sources` (`id`) ON DELETE SET NULL,
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`),
  CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `redirects`;
CREATE TABLE `redirects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `old_url` varchar(500) NOT NULL,
  `new_url` varchar(500) NOT NULL,
  `type` enum('301','302','307') DEFAULT '301',
  `hits` int(10) unsigned DEFAULT 0,
  `last_used` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_old_url` (`old_url`),
  KEY `idx_type` (`type`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `redirects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `role_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `is_system` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `seo_settings`;
CREATE TABLE `seo_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `is_json` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_key` (`setting_key`),
  KEY `idx_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `is_public` tinyint(1) DEFAULT 0,
  `is_json` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_key` (`setting_key`),
  KEY `idx_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `site_analytics`;
CREATE TABLE `site_analytics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `page_views` int(10) unsigned DEFAULT 0,
  `unique_visitors` int(10) unsigned DEFAULT 0,
  `sessions` int(10) unsigned DEFAULT 0,
  `bounce_rate` decimal(5,2) DEFAULT 0.00,
  `avg_session_duration` int(10) unsigned DEFAULT 0,
  `organic_traffic` int(10) unsigned DEFAULT 0,
  `referral_traffic` int(10) unsigned DEFAULT 0,
  `direct_traffic` int(10) unsigned DEFAULT 0,
  `social_traffic` int(10) unsigned DEFAULT 0,
  `new_visitors` int(10) unsigned DEFAULT 0,
  `returning_visitors` int(10) unsigned DEFAULT 0,
  `device_desktop` int(10) unsigned DEFAULT 0,
  `device_mobile` int(10) unsigned DEFAULT 0,
  `device_tablet` int(10) unsigned DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_date` (`date`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `daily_visitors`;
CREATE TABLE `daily_visitors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `visitor_hash` char(64) NOT NULL,
  `is_new` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_date_visitor` (`date`,`visitor_hash`),
  KEY `idx_date` (`date`),
  KEY `idx_visitor` (`visitor_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `page_views`;
CREATE TABLE `page_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `page_key` varchar(100) NOT NULL,
  `visitor_hash` char(64) NOT NULL,
  `hits` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_page_visitor` (`date`,`page_key`,`visitor_hash`),
  KEY `idx_page` (`page_key`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `indexing_queue`;
CREATE TABLE `indexing_queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(500) NOT NULL,
  `url_hash` char(40) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'URL_UPDATED',
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `last_error` varchar(500) DEFAULT NULL,
  `available_at` datetime NOT NULL DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_url_hash` (`url_hash`),
  KEY `idx_status_available` (`status`,`available_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `subscribers`;
CREATE TABLE `subscribers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `status` enum('active','unsubscribed','bounced','complained') DEFAULT 'active',
  `source` varchar(50) DEFAULT 'website',
  `ip_address` varchar(45) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `unsubscribe_token` varchar(100) DEFAULT NULL,
  `unsubscribed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `seo_title` varchar(70) DEFAULT NULL,
  `seo_description` varchar(160) DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `meta_robots` varchar(50) DEFAULT 'index,follow',
  `og_image` varchar(255) DEFAULT NULL,
  `post_count` int(10) unsigned DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `role_id` int(10) unsigned NOT NULL DEFAULT 6,
  `status` enum('active','inactive','banned','pending') DEFAULT 'pending',
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verified_at` datetime DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `login_attempts` tinyint(3) unsigned DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `google_id` varchar(100) DEFAULT NULL,
  `facebook_id` varchar(100) DEFAULT NULL,
  `twitter_id` varchar(100) DEFAULT NULL,
  `seo_title` varchar(70) DEFAULT NULL,
  `seo_description` varchar(160) DEFAULT NULL,
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_role` (`role_id`),
  KEY `idx_username` (`username`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
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

SET FOREIGN_KEY_CHECKS = 1;
