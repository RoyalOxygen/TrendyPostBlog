-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 18, 2026 at 04:15 PM
-- Server version: 11.4.9-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `trendypost`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `type`, `message`, `ip_address`, `created_at`) VALUES
(1, 1, 'login', 'User logged in', '::1', '2026-06-16 12:37:45'),
(2, 2, 'register', 'New user registered: zamani', '::1', '2026-06-16 13:05:45'),
(3, 1, 'logout', 'User logged out', '::1', '2026-06-16 13:06:01'),
(4, 2, 'login', 'User logged in', '::1', '2026-06-16 13:06:20'),
(5, 2, 'logout', 'User logged out', '::1', '2026-06-16 13:09:34'),
(6, 1, 'login', 'User logged in', '::1', '2026-06-16 13:09:41'),
(7, 1, 'logout', 'User logged out', '::1', '2026-06-16 13:10:26'),
(8, 2, 'login', 'User logged in', '::1', '2026-06-16 13:10:37'),
(9, 2, 'logout', 'User logged out', '::1', '2026-06-16 13:10:57'),
(10, 1, 'login', 'User logged in', '::1', '2026-06-16 13:11:06'),
(11, 3, 'register', 'New user registered: zaks', '::1', '2026-06-16 13:11:45'),
(12, 1, 'logout', 'User logged out', '::1', '2026-06-16 13:12:01'),
(13, 3, 'login', 'User logged in', '::1', '2026-06-16 13:12:10'),
(14, 3, 'logout', 'User logged out', '::1', '2026-06-16 13:13:21'),
(15, 1, 'login', 'User logged in', '::1', '2026-06-16 13:13:28'),
(16, 4, 'register', 'New user registered: emmy', '::1', '2026-06-16 13:14:03'),
(17, 1, 'logout', 'User logged out', '::1', '2026-06-16 13:14:25'),
(18, 4, 'login', 'User logged in', '::1', '2026-06-16 13:14:36'),
(19, 4, 'logout', 'User logged out', '::1', '2026-06-16 13:15:54'),
(20, 5, 'register', 'New user registered: oxygen', '::1', '2026-06-16 13:16:33'),
(21, 5, 'login', 'User logged in', '::1', '2026-06-16 13:16:46'),
(22, 5, 'logout', 'User logged out', '::1', '2026-06-16 13:17:17'),
(23, 1, 'login', 'User logged in', '::1', '2026-06-16 13:17:25'),
(24, 1, 'logout', 'User logged out', '::1', '2026-06-16 13:24:32'),
(25, 1, 'login', 'User logged in', '::1', '2026-06-16 13:25:14'),
(26, 1, 'logout', 'User logged out', '::1', '2026-06-16 14:55:34'),
(27, 5, 'login', 'User logged in', '::1', '2026-06-16 14:55:51'),
(28, 5, 'logout', 'User logged out', '::1', '2026-06-16 14:56:06'),
(29, 1, 'login', 'User logged in', '::1', '2026-06-16 14:56:14'),
(30, 1, 'logout', 'User logged out', '::1', '2026-06-16 17:33:54'),
(31, NULL, 'logout', 'User logged out', '::1', '2026-06-16 17:39:19'),
(32, 1, 'login', 'User logged in', '::1', '2026-06-16 17:39:33'),
(33, 1, 'login', 'User logged in', '::1', '2026-06-17 12:05:55'),
(34, 6, 'register', 'New user registered: elkazy', '::1', '2026-06-17 12:07:32'),
(35, 1, 'logout', 'User logged out', '::1', '2026-06-17 12:07:41'),
(36, 6, 'login', 'User logged in', '::1', '2026-06-17 12:08:01');

-- --------------------------------------------------------

--
-- Table structure for table `advertisements`
--

CREATE TABLE `advertisements` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `network_id` int(10) UNSIGNED DEFAULT NULL,
  `placement_id` int(10) UNSIGNED NOT NULL,
  `ad_type` enum('display','native','smartlink','in_page_push','push_notification','vignette','popunder','banner','video','multitag','custom_html','affiliate') DEFAULT 'display',
  `ad_code` text DEFAULT NULL,
  `custom_html` longtext DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `target_blank` tinyint(1) DEFAULT 1,
  `nofollow` tinyint(1) DEFAULT 1,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `display_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`display_rules`)),
  `impression_count` int(10) UNSIGNED DEFAULT 0,
  `click_count` int(10) UNSIGNED DEFAULT 0,
  `revenue` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','paused','scheduled','expired') DEFAULT 'active',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `advertisements`
--

INSERT INTO `advertisements` (`id`, `title`, `network_id`, `placement_id`, `ad_type`, `ad_code`, `custom_html`, `image`, `link_url`, `target_blank`, `nofollow`, `start_date`, `end_date`, `display_rules`, `impression_count`, `click_count`, `revenue`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(18, 'Test Ad for header_banner', 1, 1, 'display', '<div style=\"background:#f8f9fa;padding:15px;border-radius:8px;text-align:center;border:1px solid #e9ecef;\"><p style=\"margin:0;color:#6c757d;\"><strong>Test Ad</strong><br>Placement: Header Banner</p></div>', '<div style=\"background:linear-gradient(135deg,#00C853,#00B34A);padding:15px;border-radius:8px;text-align:center;color:#fff;\"><h6>Sponsored Content</h6><p style=\"margin:0;font-size:14px;\">This is a test ad for Header Banner</p></div>', '', 'https://www.trendypost.com', 1, 1, NULL, NULL, NULL, 346, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-17 13:12:30'),
(19, 'Test Ad for footer_banner', 1, 2, 'display', '<div style=\"background:#f8f9fa;padding:15px;border-radius:8px;text-align:center;border:1px solid #e9ecef;\"><p style=\"margin:0;color:#6c757d;\"><strong>Test Ad</strong><br>Placement: Footer Banner</p></div>', '<div style=\"background:linear-gradient(135deg,#00C853,#00B34A);padding:15px;border-radius:8px;text-align:center;color:#fff;\"><h6>Sponsored Content</h6><p style=\"margin:0;font-size:14px;\">This is a test ad for Footer Banner</p></div>', '', 'https://www.trendypost.com', 1, 1, NULL, NULL, NULL, 227, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-17 13:12:30'),
(20, 'Test Ad for sidebar_top', 1, 3, 'display', '<div style=\"background:#f8f9fa;padding:15px;border-radius:8px;text-align:center;border:1px solid #e9ecef;\"><p style=\"margin:0;color:#6c757d;\"><strong>Test Ad</strong><br>Placement: Sidebar Top</p></div>', '<div style=\"background:linear-gradient(135deg,#00C853,#00B34A);padding:15px;border-radius:8px;text-align:center;color:#fff;\"><h6>Sponsored Content</h6><p style=\"margin:0;font-size:14px;\">This is a test ad for Sidebar Top</p></div>', '', 'https://www.trendypost.com', 1, 1, NULL, NULL, NULL, 358, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-17 13:11:24'),
(21, 'Test Ad for sidebar_bottom', 1, 4, 'display', '<div style=\"background:#f8f9fa;padding:15px;border-radius:8px;text-align:center;border:1px solid #e9ecef;\"><p style=\"margin:0;color:#6c757d;\"><strong>Test Ad</strong><br>Placement: Sidebar Bottom</p></div>', '<div style=\"background:linear-gradient(135deg,#00C853,#00B34A);padding:15px;border-radius:8px;text-align:center;color:#fff;\"><h6>Sponsored Content</h6><p style=\"margin:0;font-size:14px;\">This is a test ad for Sidebar Bottom</p></div>', '', 'https://www.trendypost.com', 1, 1, NULL, NULL, NULL, 120, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-16 19:36:16'),
(22, 'Test Ad for sticky_sidebar', 1, 5, 'display', '<div style=\"background:#f8f9fa;padding:15px;border-radius:8px;text-align:center;border:1px solid #e9ecef;\"><p style=\"margin:0;color:#6c757d;\"><strong>Test Ad</strong><br>Placement: Sticky Sidebar</p></div>', '<div style=\"background:linear-gradient(135deg,#00C853,#00B34A);padding:15px;border-radius:8px;text-align:center;color:#fff;\"><h6>Sponsored Content</h6><p style=\"margin:0;font-size:14px;\">This is a test ad for Sticky Sidebar</p></div>', '', 'https://www.trendypost.com', 1, 1, NULL, NULL, NULL, 207, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-17 13:11:25'),
(23, 'Test Ad for before_content', 1, 6, 'display', '<div style=\"background:#f8f9fa;padding:15px;border-radius:8px;text-align:center;border:1px solid #e9ecef;\"><p style=\"margin:0;color:#6c757d;\"><strong>Test Ad</strong><br>Placement: Before Content</p></div>', '<div style=\"background:linear-gradient(135deg,#00C853,#00B34A);padding:15px;border-radius:8px;text-align:center;color:#fff;\"><h6>Sponsored Content</h6><p style=\"margin:0;font-size:14px;\">This is a test ad for Before Content</p></div>', '', 'https://www.trendypost.com', 1, 1, NULL, NULL, NULL, 161, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-17 13:11:24'),
(24, 'Test Ad for after_content', 1, 7, 'display', '<div style=\"background:#f8f9fa;padding:15px;border-radius:8px;text-align:center;border:1px solid #e9ecef;\"><p style=\"margin:0;color:#6c757d;\"><strong>Test Ad</strong><br>Placement: After Content</p></div>', '<div style=\"background:linear-gradient(135deg,#00C853,#00B34A);padding:15px;border-radius:8px;text-align:center;color:#fff;\"><h6>Sponsored Content</h6><p style=\"margin:0;font-size:14px;\">This is a test ad for After Content</p></div>', '', 'https://www.trendypost.com', 1, 1, NULL, NULL, NULL, 161, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-17 13:11:24'),
(25, 'Test Ad for after_para_2', 1, 8, 'display', '<div style=\"background:#f8f9fa;padding:15px;border-radius:8px;text-align:center;border:1px solid #e9ecef;\"><p style=\"margin:0;color:#6c757d;\"><strong>Test Ad</strong><br>Placement: After Paragraph 2</p></div>', '<div style=\"background:linear-gradient(135deg,#00C853,#00B34A);padding:15px;border-radius:8px;text-align:center;color:#fff;\"><h6>Sponsored Content</h6><p style=\"margin:0;font-size:14px;\">This is a test ad for After Paragraph 2</p></div>', '', 'https://www.trendypost.com', 1, 1, NULL, NULL, NULL, 161, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-17 13:11:24'),
(26, 'Test Ad for after_para_5', 1, 9, 'display', '<div style=\"background:#f8f9fa;padding:15px;border-radius:8px;text-align:center;border:1px solid #e9ecef;\"><p style=\"margin:0;color:#6c757d;\"><strong>Test Ad</strong><br>Placement: After Paragraph 5</p></div>', '<div style=\"background:linear-gradient(135deg,#00C853,#00B34A);padding:15px;border-radius:8px;text-align:center;color:#fff;\"><h6>Sponsored Content</h6><p style=\"margin:0;font-size:14px;\">This is a test ad for After Paragraph 5</p></div>', '', 'https://www.trendypost.com', 1, 1, NULL, NULL, NULL, 160, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-17 13:11:24'),
(27, 'Test Ad for in_content_auto', 1, 10, 'display', '<div style=\"background:#f8f9fa;padding:15px;border-radius:8px;text-align:center;border:1px solid #e9ecef;\"><p style=\"margin:0;color:#6c757d;\"><strong>Test Ad</strong><br>Placement: In Content Auto</p></div>', '<div style=\"background:linear-gradient(135deg,#00C853,#00B34A);padding:15px;border-radius:8px;text-align:center;color:#fff;\"><h6>Sponsored Content</h6><p style=\"margin:0;font-size:14px;\">This is a test ad for In Content Auto</p></div>', '', 'https://www.trendypost.com', 1, 1, NULL, NULL, NULL, 124, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-16 19:36:17'),
(28, 'Test Ad for floating_bottom', 1, 11, 'display', '<div style=\"background:#f8f9fa;padding:15px;border-radius:8px;text-align:center;border:1px solid #e9ecef;\"><p style=\"margin:0;color:#6c757d;\"><strong>Test Ad</strong><br>Placement: Floating Bottom</p></div>', '<div style=\"background:linear-gradient(135deg,#00C853,#00B34A);padding:15px;border-radius:8px;text-align:center;color:#fff;\"><h6>Sponsored Content</h6><p style=\"margin:0;font-size:14px;\">This is a test ad for Floating Bottom</p></div>', '', 'https://www.trendypost.com', 1, 1, NULL, NULL, NULL, 227, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-17 13:12:30'),
(29, 'Test Ad for mobile_header', 1, 12, 'display', '<div style=\"background:#f8f9fa;padding:15px;border-radius:8px;text-align:center;border:1px solid #e9ecef;\"><p style=\"margin:0;color:#6c757d;\"><strong>Test Ad</strong><br>Placement: Mobile Header</p></div>', '<div style=\"background:linear-gradient(135deg,#00C853,#00B34A);padding:15px;border-radius:8px;text-align:center;color:#fff;\"><h6>Sponsored Content</h6><p style=\"margin:0;font-size:14px;\">This is a test ad for Mobile Header</p></div>', '', 'https://www.trendypost.com', 1, 1, NULL, NULL, NULL, 1, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-16 17:07:36'),
(30, 'Test Ad for mobile_footer', 1, 13, 'display', '<div style=\"background:#f8f9fa;padding:15px;border-radius:8px;text-align:center;border:1px solid #e9ecef;\"><p style=\"margin:0;color:#6c757d;\"><strong>Test Ad</strong><br>Placement: Mobile Footer</p></div>', '<div style=\"background:linear-gradient(135deg,#00C853,#00B34A);padding:15px;border-radius:8px;text-align:center;color:#fff;\"><h6>Sponsored Content</h6><p style=\"margin:0;font-size:14px;\">This is a test ad for Mobile Footer</p></div>', '', 'https://www.trendypost.com', 1, 1, NULL, NULL, NULL, 1, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-16 17:07:37'),
(31, 'Test Ad for desktop_sidebar', 1, 14, 'display', '<div style=\"background:#f8f9fa;padding:15px;border-radius:8px;text-align:center;border:1px solid #e9ecef;\"><p style=\"margin:0;color:#6c757d;\"><strong>Test Ad</strong><br>Placement: Desktop Sidebar</p></div>', '<div style=\"background:linear-gradient(135deg,#00C853,#00B34A);padding:15px;border-radius:8px;text-align:center;color:#fff;\"><h6>Sponsored Content</h6><p style=\"margin:0;font-size:14px;\">This is a test ad for Desktop Sidebar</p></div>', '', 'https://www.trendypost.com', 1, 1, NULL, NULL, NULL, 119, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-16 19:36:16'),
(32, 'Test Ad for breaking_news', 1, 15, 'display', '<div style=\"background:#f8f9fa;padding:15px;border-radius:8px;text-align:center;border:1px solid #e9ecef;\"><p style=\"margin:0;color:#6c757d;\"><strong>Test Ad</strong><br>Placement: Breaking News</p></div>', '<div style=\"background:linear-gradient(135deg,#00C853,#00B34A);padding:15px;border-radius:8px;text-align:center;color:#fff;\"><h6>Sponsored Content</h6><p style=\"margin:0;font-size:14px;\">This is a test ad for Breaking News</p></div>', '', 'https://www.trendypost.com', 1, 1, NULL, NULL, NULL, 120, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-16 19:36:18'),
(33, 'Test Ad for native_infeed', 1, 16, 'display', '<div style=\"background:#f8f9fa;padding:15px;border-radius:8px;text-align:center;border:1px solid #e9ecef;\"><p style=\"margin:0;color:#6c757d;\"><strong>Test Ad</strong><br>Placement: Native In-Feed</p></div>', '<div style=\"background:linear-gradient(135deg,#00C853,#00B34A);padding:15px;border-radius:8px;text-align:center;color:#fff;\"><h6>Sponsored Content</h6><p style=\"margin:0;font-size:14px;\">This is a test ad for Native In-Feed</p></div>', '', 'https://www.trendypost.com', 1, 1, NULL, NULL, NULL, 124, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-16 19:36:18'),
(34, 'Custom HTML Test Ad', 1, 1, 'custom_html', NULL, '<div style=\"background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:20px;border-radius:8px;text-align:center;color:#fff;\"><h5>✨ Custom HTML Ad</h5><p style=\"margin:0;font-size:14px;opacity:0.9;\">This is a fully customizable HTML ad</p></div>', '', '', 1, 1, NULL, NULL, NULL, 119, 0, 0.00, 'active', 1, '2026-06-16 16:58:06', '2026-06-16 19:36:19');

-- --------------------------------------------------------

--
-- Table structure for table `ad_networks`
--

CREATE TABLE `ad_networks` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ad_networks`
--

INSERT INTO `ad_networks` (`id`, `name`, `slug`, `code`, `description`, `is_active`, `created_at`) VALUES
(1, 'Google AdSense', 'adsense', 'adsense', 'Google AdSense programmatic ads', 1, '2026-06-16 13:02:01'),
(2, 'Adsterra', 'adsterra', 'adsterra', 'Adsterra ad network', 1, '2026-06-16 13:02:01'),
(3, 'Monetag', 'monetag', 'monetag', 'Monetag ad network', 1, '2026-06-16 13:02:01'),
(4, 'Media.net', 'media_net', 'medianet', 'Media.net contextual ads', 1, '2026-06-16 13:02:01'),
(5, 'Ezoic', 'ezoic', 'ezoic', 'Ezoic AI-driven ad optimization', 1, '2026-06-16 13:02:01'),
(6, 'PropellerAds', 'propellerads', 'propeller', 'PropellerAds push and native', 1, '2026-06-16 13:02:01'),
(7, 'Custom HTML', 'custom', 'custom', 'Custom HTML advertisement code', 1, '2026-06-16 13:02:01');

-- --------------------------------------------------------

--
-- Table structure for table `ad_placements`
--

CREATE TABLE `ad_placements` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `location` enum('header','footer','sidebar','in_content','before_content','after_content','sticky_sidebar','floating','popunder','vignette','native','related_content','breaking_news','breadcrumb','after_paragraph_2','after_paragraph_5','mobile_only','desktop_only') NOT NULL,
  `device_target` enum('all','desktop','mobile','tablet') DEFAULT 'all',
  `priority` int(10) UNSIGNED DEFAULT 0,
  `max_ads` int(10) UNSIGNED DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ad_placements`
--

INSERT INTO `ad_placements` (`id`, `name`, `slug`, `description`, `location`, `device_target`, `priority`, `max_ads`, `is_active`, `created_at`) VALUES
(1, 'Header Banner', 'header_banner', 'Ad displayed in the header area', 'header', 'all', 1, 1, 1, '2026-06-16 13:02:01'),
(2, 'Footer Banner', 'footer_banner', 'Ad displayed in the footer area', 'footer', 'all', 1, 1, 1, '2026-06-16 13:02:01'),
(3, 'Sidebar Top', 'sidebar_top', 'Ad displayed at top of sidebar', 'sidebar', 'all', 2, 1, 1, '2026-06-16 13:02:01'),
(4, 'Sidebar Bottom', 'sidebar_bottom', 'Ad displayed at bottom of sidebar', 'sidebar', 'all', 2, 1, 1, '2026-06-16 13:02:01'),
(5, 'Sticky Sidebar', 'sticky_sidebar', 'Sticky ad that follows scroll in sidebar', 'sticky_sidebar', 'all', 3, 1, 1, '2026-06-16 13:02:01'),
(6, 'Before Content', 'before_content', 'Ad displayed before article content', 'before_content', 'all', 4, 1, 1, '2026-06-16 13:02:01'),
(7, 'After Content', 'after_content', 'Ad displayed after article content', 'after_content', 'all', 4, 1, 1, '2026-06-16 13:02:01'),
(8, 'After Paragraph 2', 'after_para_2', 'Ad injected after 2nd paragraph', 'after_paragraph_2', 'all', 5, 1, 1, '2026-06-16 13:02:01'),
(9, 'After Paragraph 5', 'after_para_5', 'Ad injected after 5th paragraph', 'after_paragraph_5', 'all', 5, 1, 1, '2026-06-16 13:02:01'),
(10, 'In Content Auto', 'in_content_auto', 'Auto-injected in-content ads', 'in_content', 'all', 5, 1, 1, '2026-06-16 13:02:01'),
(11, 'Floating Bottom', 'floating_bottom', 'Floating ad at bottom of screen', 'floating', 'all', 6, 1, 1, '2026-06-16 13:02:01'),
(12, 'Mobile Header', 'mobile_header', 'Ad for mobile header only', 'header', 'mobile', 1, 1, 1, '2026-06-16 13:02:01'),
(13, 'Mobile Footer', 'mobile_footer', 'Ad for mobile footer only', 'footer', 'mobile', 1, 1, 1, '2026-06-16 13:02:01'),
(14, 'Desktop Sidebar', 'desktop_sidebar', 'Ad for desktop sidebar only', 'sidebar', 'desktop', 2, 1, 1, '2026-06-16 13:02:01'),
(15, 'Breaking News', 'breaking_news', 'Ad in breaking news ticker area', 'breaking_news', 'all', 7, 1, 1, '2026-06-16 13:02:01'),
(16, 'Native In-Feed', 'native_infeed', 'Native ad within post listings', 'related_content', 'all', 8, 1, 1, '2026-06-16 13:02:01');

-- --------------------------------------------------------

--
-- Table structure for table `ad_statistics`
--

CREATE TABLE `ad_statistics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ad_id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `impressions` int(10) UNSIGNED DEFAULT 0,
  `clicks` int(10) UNSIGNED DEFAULT 0,
  `revenue` decimal(10,2) DEFAULT 0.00,
  `ctr` decimal(5,2) DEFAULT 0.00,
  `device_type` enum('desktop','mobile','tablet') DEFAULT 'desktop',
  `country` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backups`
--

CREATE TABLE `backups` (
  `id` int(10) UNSIGNED NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL,
  `backup_type` enum('full','database','files') DEFAULT 'full',
  `tables` longtext DEFAULT NULL,
  `status` enum('pending','in_progress','completed','failed') DEFAULT 'pending',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#00C853',
  `seo_title` varchar(70) DEFAULT NULL,
  `seo_description` varchar(160) DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `schema_type` varchar(50) DEFAULT 'CollectionPage',
  `meta_robots` varchar(50) DEFAULT 'index,follow',
  `canonical_url` varchar(255) DEFAULT NULL,
  `og_image` varchar(255) DEFAULT NULL,
  `sort_order` int(10) UNSIGNED DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image`, `color`, `seo_title`, `seo_description`, `seo_keywords`, `schema_type`, `meta_robots`, `canonical_url`, `og_image`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(3, 'Sports', 'sports', 'football, soccer and other games', NULL, NULL, '#e14023', 'footbal, News, tennis', 'football, soccer and other games', NULL, 'CollectionPage', 'index,follow', NULL, NULL, 0, 'active', '2026-06-16 16:01:49', '2026-06-16 16:01:49'),
(4, 'Entertainment', 'entertainments', 'Movies, Videos, Audios, and other Entertainments', NULL, NULL, '#00c853', 'Movies, Videos, Audios', 'Movies, Videos, Audios, and other Entertainments', NULL, 'CollectionPage', 'index,follow', NULL, NULL, 0, 'active', '2026-06-16 16:02:58', '2026-06-16 16:04:24'),
(5, 'royaloxygen', 'royaloxygen', 'wfewfgergrg', NULL, NULL, '#00c853', 'srgreg dfgb srfgerg', 'rwgwerg srgerg swgrg', NULL, 'CollectionPage', 'index,follow', NULL, NULL, 0, 'active', '2026-06-16 16:03:53', '2026-06-16 16:03:53');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `author_name` varchar(100) DEFAULT NULL,
  `author_email` varchar(100) DEFAULT NULL,
  `author_website` varchar(255) DEFAULT NULL,
  `author_ip` varchar(45) DEFAULT NULL,
  `content` text NOT NULL,
  `status` enum('approved','pending','spam','trash') DEFAULT 'pending',
  `is_pinned` tinyint(1) DEFAULT 0,
  `like_count` int(10) UNSIGNED DEFAULT 0,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `parent_id`, `user_id`, `author_name`, `author_email`, `author_website`, `author_ip`, `content`, `status`, `is_pinned`, `like_count`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 3, NULL, NULL, 'wfwefvwfv', 'royaloxygen5@gmail.com', '', '::1', 'wefvwfvcwefc', 'pending', 0, 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 18:38:34', '2026-06-16 18:38:34'),
(2, 3, NULL, NULL, 'wfwefvwfv', 'royaloxygen5@gmail.com', '', '::1', 'wefvwfvcwefc', 'pending', 0, 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 18:38:34', '2026-06-16 18:38:34'),
(3, 3, NULL, NULL, 'wfwfwf', 'royaloxygen5@gmail.com', '', '::1', 'wfwfwf', 'pending', 0, 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 18:38:54', '2026-06-16 18:38:54'),
(4, 3, NULL, NULL, 'wfwfwf', 'royaloxygen5@gmail.com', '', '::1', 'wfwfwf', 'pending', 0, 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 18:38:54', '2026-06-16 18:38:54'),
(5, 2, NULL, NULL, 'wffcwfwf', 'royaloxygen5@gmail.com', '', '::1', 'wfqfwfv', 'approved', 0, 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 18:39:11', '2026-06-16 18:40:20'),
(6, 2, NULL, NULL, 'wffcwfwf', 'royaloxygen5@gmail.com', '', '::1', 'wfqfwfv', 'pending', 0, 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 18:39:11', '2026-06-16 18:39:11'),
(7, 2, NULL, 1, 'Super Admin', 'admin@trendypost.com', '', '::1', 'svsvwsvcac', 'approved', 0, 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 18:40:33', '2026-06-16 18:41:13'),
(8, 2, NULL, 1, 'Super Admin', 'admin@trendypost.com', '', '::1', 'svsvwsvcac', 'pending', 0, 0, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-16 18:40:33', '2026-06-16 18:40:33');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('new','read','replied','archived') DEFAULT 'new',
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` int(10) UNSIGNED NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL,
  `width` int(10) UNSIGNED DEFAULT NULL,
  `height` int(10) UNSIGNED DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `usage_count` int(10) UNSIGNED DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `media`
--

INSERT INTO `media` (`id`, `filename`, `original_name`, `file_path`, `file_url`, `file_type`, `mime_type`, `file_size`, `width`, `height`, `alt_text`, `caption`, `description`, `uploaded_by`, `is_featured`, `usage_count`, `created_at`) VALUES
(1, '6a3194db85305_2ee49690-6af1-11ef-a614-c125c4c5a25c_1781549964.jpg', '2ee49690-6af1-11ef-a614-c125c4c5a25c_1781549964.jpg', 'C:\\xampp\\htdocs\\trendypost/assets/uploads/6a3194db85305_2ee49690-6af1-11ef-a614-c125c4c5a25c_1781549964.jpg', 'http://localhost/trendypost/assets/uploads/6a3194db85305_2ee49690-6af1-11ef-a614-c125c4c5a25c_1781549964.jpg', 'jpg', 'image/jpeg', 46036, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2026-06-16 18:24:27');

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `location` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id`, `name`, `slug`, `location`, `description`, `is_active`, `created_at`) VALUES
(1, 'Main Navigation', 'main_nav', 'header', 'Primary site navigation', 1, '2026-06-16 13:02:02'),
(2, 'Footer Links', 'footer_nav', 'footer', 'Footer navigation links', 1, '2026-06-16 13:02:02'),
(3, 'Social Links', 'social_nav', 'social', 'Social media links', 1, '2026-06-16 13:02:02');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `menu_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `target` enum('_self','_blank') DEFAULT '_self',
  `icon` varchar(50) DEFAULT NULL,
  `css_class` varchar(100) DEFAULT NULL,
  `sort_order` int(10) UNSIGNED DEFAULT 0,
  `item_type` enum('custom','page','category','post','tag') DEFAULT 'custom',
  `linked_id` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `menu_id`, `parent_id`, `title`, `url`, `target`, `icon`, `css_class`, `sort_order`, `item_type`, `linked_id`, `is_active`, `created_at`) VALUES
(1, 1, NULL, 'Home', '/', '_self', NULL, NULL, 1, 'custom', NULL, 1, '2026-06-16 13:02:02'),
(2, 1, NULL, 'Archive', '/archive', '_self', NULL, NULL, 2, 'custom', NULL, 1, '2026-06-16 13:02:02'),
(3, 1, NULL, 'Category', '/categories', '_self', NULL, NULL, 3, 'custom', NULL, 1, '2026-06-16 13:02:02'),
(4, 1, NULL, 'Post Types', '#', '_self', NULL, NULL, 4, 'custom', NULL, 1, '2026-06-16 13:02:02'),
(5, 1, NULL, 'About', '/page/about-us', '_self', NULL, NULL, 5, 'page', NULL, 1, '2026-06-16 13:02:02'),
(6, 1, NULL, 'Contact', '/page/contact', '_self', NULL, NULL, 6, 'page', NULL, 1, '2026-06-16 13:02:02');

-- --------------------------------------------------------

--
-- Table structure for table `newsletters`
--

CREATE TABLE `newsletters` (
  `id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `template` varchar(50) DEFAULT 'default',
  `status` enum('draft','scheduled','sending','sent','paused') DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `sent_count` int(10) UNSIGNED DEFAULT 0,
  `open_count` int(10) UNSIGNED DEFAULT 0,
  `click_count` int(10) UNSIGNED DEFAULT 0,
  `bounce_count` int(10) UNSIGNED DEFAULT 0,
  `unsubscribe_count` int(10) UNSIGNED DEFAULT 0,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `template` varchar(50) DEFAULT 'default',
  `author_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'draft',
  `menu_order` int(10) UNSIGNED DEFAULT 0,
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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `title`, `slug`, `content`, `excerpt`, `featured_image`, `template`, `author_id`, `parent_id`, `status`, `menu_order`, `show_in_menu`, `is_homepage`, `allow_comments`, `seo_title`, `seo_description`, `seo_keywords`, `canonical_url`, `meta_robots`, `schema_type`, `og_image`, `created_at`, `updated_at`) VALUES
(1, 'About Us', 'about-us', '<h2>Welcome to TrendyPost</h2><p>TrendyPost is a leading digital publishing platform dedicated to delivering the latest trending news, insightful articles, and engaging stories across multiple categories including technology, lifestyle, business, entertainment, and more.</p><p>Our mission is to keep you informed, entertained, and inspired with high-quality content crafted by our team of experienced writers and editors.</p><h3>Our Story</h3><p>Founded in 2024, TrendyPost has grown from a small blog to a comprehensive digital publishing platform reaching millions of readers worldwide.</p><h3>Our Values</h3><ul><li>Accuracy and integrity in reporting</li><li>Diverse perspectives and voices</li><li>Reader-first approach</li><li>Innovation in digital storytelling</li></ul>', 'Learn more about TrendyPost, our mission, values, and the team behind the stories.', NULL, 'default', 1, NULL, 'published', 1, 1, 0, 0, 'About Us | TrendyPost', 'Learn about TrendyPost - your premier destination for trending news and stories.', NULL, NULL, 'index,follow', 'WebPage', NULL, '2026-06-16 13:02:02', '2026-06-16 13:02:02'),
(2, 'Contact Us', 'contact', '<h2>Get in Touch</h2><p>We would love to hear from you. Whether you have a question, feedback, or a story tip, feel free to reach out to us.</p><h3>Contact Information</h3><p><strong>Email:</strong> contact@trendypost.com</p><p><strong>Phone:</strong> +1 (555) 123-4567</p><p><strong>Address:</strong> 123 Digital Street, Tech City, TC 12345</p>', 'Contact TrendyPost for inquiries, feedback, and collaboration opportunities.', NULL, 'default', 1, NULL, 'published', 2, 1, 0, 0, 'Contact Us | TrendyPost', 'Get in touch with the TrendyPost team.', NULL, NULL, 'index,follow', 'WebPage', NULL, '2026-06-16 13:02:02', '2026-06-16 13:02:02'),
(3, 'Privacy Policy', 'privacy-policy', '<h2>Privacy Policy</h2><p>At TrendyPost, we take your privacy seriously. This Privacy Policy explains how we collect, use, and protect your personal information.</p><h3>Information We Collect</h3><p>We collect information you provide directly to us, such as when you create an account, subscribe to our newsletter, or contact us.</p><h3>How We Use Your Information</h3><p>We use the information we collect to provide, maintain, and improve our services, and to communicate with you.</p>', 'Read TrendyPost privacy policy to understand how we handle your data.', NULL, 'default', 1, NULL, 'published', 0, 0, 0, 0, 'Privacy Policy | TrendyPost', 'TrendyPost privacy policy and data protection practices.', NULL, NULL, 'index,follow', 'WebPage', NULL, '2026-06-16 13:02:02', '2026-06-16 13:02:02'),
(4, 'Terms of Service', 'terms-of-service', '<h2>Terms of Service</h2><p>By accessing and using TrendyPost, you agree to these Terms of Service.</p><h3>Use of Content</h3><p>All content on TrendyPost is protected by copyright. You may not reproduce, distribute, or create derivative works without permission.</p>', 'Read the Terms of Service for using TrendyPost.', NULL, 'default', 1, NULL, 'published', 0, 0, 0, 0, 'Terms of Service | TrendyPost', 'Terms and conditions for using TrendyPost.', NULL, NULL, 'index,follow', 'WebPage', NULL, '2026-06-16 13:02:02', '2026-06-16 13:02:02');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(200) NOT NULL,
  `module` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `display_name`, `module`, `description`, `created_at`) VALUES
(1, 'dashboard.view', 'View Dashboard', 'dashboard', 'Access admin dashboard', '2026-06-16 13:02:00'),
(2, 'posts.view', 'View Posts', 'posts', 'View all posts', '2026-06-16 13:02:00'),
(3, 'posts.create', 'Create Posts', 'posts', 'Create new posts', '2026-06-16 13:02:00'),
(4, 'posts.edit', 'Edit Posts', 'posts', 'Edit any post', '2026-06-16 13:02:00'),
(5, 'posts.delete', 'Delete Posts', 'posts', 'Delete any post', '2026-06-16 13:02:00'),
(6, 'posts.publish', 'Publish Posts', 'posts', 'Publish posts', '2026-06-16 13:02:00'),
(7, 'categories.manage', 'Manage Categories', 'categories', 'Create and manage categories', '2026-06-16 13:02:00'),
(8, 'tags.manage', 'Manage Tags', 'tags', 'Create and manage tags', '2026-06-16 13:02:00'),
(9, 'pages.manage', 'Manage Pages', 'pages', 'Create and manage pages', '2026-06-16 13:02:00'),
(10, 'comments.moderate', 'Moderate Comments', 'comments', 'Approve, reject, delete comments', '2026-06-16 13:02:00'),
(11, 'media.manage', 'Manage Media', 'media', 'Upload and manage media files', '2026-06-16 13:02:00'),
(12, 'users.view', 'View Users', 'users', 'View user list', '2026-06-16 13:02:00'),
(13, 'users.create', 'Create Users', 'users', 'Create new users', '2026-06-16 13:02:00'),
(14, 'users.edit', 'Edit Users', 'users', 'Edit user profiles', '2026-06-16 13:02:00'),
(15, 'users.delete', 'Delete Users', 'users', 'Delete users', '2026-06-16 13:02:00'),
(16, 'roles.manage', 'Manage Roles', 'roles', 'Manage roles and permissions', '2026-06-16 13:02:00'),
(17, 'ads.manage', 'Manage Advertisements', 'ads', 'Create and manage ads', '2026-06-16 13:02:00'),
(18, 'seo.manage', 'Manage SEO', 'seo', 'Manage SEO settings', '2026-06-16 13:02:00'),
(19, 'analytics.view', 'View Analytics', 'analytics', 'View site analytics', '2026-06-16 13:02:00'),
(20, 'newsletters.manage', 'Manage Newsletters', 'newsletters', 'Create and send newsletters', '2026-06-16 13:02:00'),
(21, 'subscribers.manage', 'Manage Subscribers', 'subscribers', 'Manage email subscribers', '2026-06-16 13:02:00'),
(22, 'settings.manage', 'Manage Settings', 'settings', 'Manage site settings', '2026-06-16 13:02:00'),
(23, 'menus.manage', 'Manage Menus', 'menus', 'Manage navigation menus', '2026-06-16 13:02:00'),
(24, 'backups.manage', 'Manage Backups', 'backups', 'Create and restore backups', '2026-06-16 13:02:00'),
(25, 'audit.view', 'View Audit Logs', 'audit', 'View system audit logs', '2026-06-16 13:02:00'),
(26, 'contact.view', 'View Contact Messages', 'contact', 'View contact form submissions', '2026-06-16 13:02:00');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `featured_image_alt` varchar(255) DEFAULT NULL,
  `featured_image_caption` varchar(255) DEFAULT NULL,
  `author_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('published','draft','scheduled','pending_review','trash') DEFAULT 'draft',
  `post_type` enum('standard','video','audio','gallery','quote') DEFAULT 'standard',
  `visibility` enum('public','password_protected','private') DEFAULT 'public',
  `password` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_breaking` tinyint(1) DEFAULT 0,
  `is_sticky` tinyint(1) DEFAULT 0,
  `allow_comments` tinyint(1) DEFAULT 1,
  `comment_count` int(10) UNSIGNED DEFAULT 0,
  `view_count` int(10) UNSIGNED DEFAULT 0,
  `like_count` int(10) UNSIGNED DEFAULT 0,
  `reading_time` tinyint(3) UNSIGNED DEFAULT NULL,
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
  `seo_score` tinyint(3) UNSIGNED DEFAULT 0,
  `readability_score` tinyint(3) UNSIGNED DEFAULT 0,
  `keyword_density` decimal(5,2) DEFAULT 0.00,
  `internal_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`internal_links`)),
  `article_modified_at` datetime DEFAULT NULL,
  `article_section` varchar(100) DEFAULT NULL,
  `article_tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`article_tags`)),
  `word_count` int(10) UNSIGNED DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `title`, `slug`, `excerpt`, `content`, `featured_image`, `featured_image_alt`, `featured_image_caption`, `author_id`, `category_id`, `status`, `post_type`, `visibility`, `password`, `is_featured`, `is_breaking`, `is_sticky`, `allow_comments`, `comment_count`, `view_count`, `like_count`, `reading_time`, `published_at`, `scheduled_at`, `password_expires`, `seo_title`, `seo_description`, `seo_keywords`, `focus_keyword`, `secondary_keywords`, `canonical_url`, `meta_robots`, `schema_type`, `og_title`, `og_description`, `og_image`, `twitter_card`, `twitter_title`, `twitter_description`, `twitter_image`, `seo_score`, `readability_score`, `keyword_density`, `internal_links`, `article_modified_at`, `article_section`, `article_tags`, `word_count`, `created_at`, `updated_at`) VALUES
(2, 'A Discount Toner Cartridge Is Better Than Ever.', 'a-discount-toner-cartridge-is-better-than-ever-1', 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco', 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur\r\nLorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur\r\n\r\nLorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur\r\n\r\nLorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur\r\n\r\nLorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur\r\nLorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur', 'posts/post_1781620997_6a31610582c00.webp', 'A Discount Toner Cartridge Is Better Than Ever.', 'A Discount Toner Cartridge Is Better Than Ever.', 1, 3, 'published', 'standard', 'public', NULL, 1, 0, 0, 1, 1, 18, 0, 2, '2026-06-16 16:06:05', NULL, NULL, 'A Discount Toner Cartridge Is Better Than Ever.', 'A Discount Toner Cartridge Is Better Than Ever.', 'A Discount, Toner, Cartridge, Is, Better, Than, Ever,', 'A Discount Toner Cartridge Is Better Than Ever.', 'A Discount, Toner, Cartridge, Is, Better, Than, Ever,', '', 'index,follow', 'NewsArticle', '', '', '', 'summary_large_image', '', '', '', 0, 0, 0.00, NULL, NULL, NULL, NULL, 312, '2026-06-16 14:43:17', '2026-06-16 18:42:09'),
(3, 'Help Finding Information Online is so easy', 'help-finding-information-online-is-so-easy', 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'posts/post_1781621619_6a31637388f18.jpeg', 'Help Finding Information Online is so easy', 'Help Finding Information Online is so easy', 1, 4, 'published', 'standard', 'public', NULL, 0, 0, 1, 1, 0, 6, 0, 1, '2026-06-16 16:05:47', NULL, NULL, 'Help Finding Information Online is so easy', 'Help Finding Information Online is so easy', '', 'Help Finding Information Online is so easy', '', '', 'index,follow', 'NewsArticle', '', '', '', 'summary', '', '', '', 0, 0, 0.00, NULL, NULL, NULL, NULL, 19, '2026-06-16 14:53:39', '2026-06-16 19:30:23'),
(4, 'Astronomy Binoculars A Great Alternative', 'astronomy-binoculars-a-great-alternative', 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco', 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco\r\nLorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamcoLorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamcoLorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco\r\n\r\nLorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco', 'posts/post_1781626361_6a3175f92720f.jpg', 'Ut enim ad minim veniam', 'Ut enim ad minim veniam', 1, 4, 'published', 'standard', 'public', NULL, 0, 1, 0, 1, 0, 8, 0, 1, '2026-06-16 16:12:41', NULL, NULL, 'Ut enim ad minim veniam', 'Ut enim ad minim veniam', 'Ut enim ad minim veniam', 'Ut enim ad minim veniam', 'Ut enim ad minim veniam', '', 'index,follow', 'NewsArticle', 'Ut enim ad minim veniam', 'Ut enim ad minim veniam', '', 'app', 'Ut enim ad minim veniam', 'Ut enim ad minim veniam', '', 0, 0, 0.00, NULL, NULL, NULL, NULL, 138, '2026-06-16 16:12:41', '2026-06-16 19:30:08'),
(5, 'World Cup 2026: Curaçao receives Guinness World Record plaque as smallest country to qualify', 'world-cup-2026-curaao-receives-guinness-world-record-plaque-as-smallest-country-to-qualify', 'The Caribbean nation was presented with a certificate by Guinness World Records in recognition of the historic achievement, with Curaçao Football Federation President Gilbert Martina receiving the award.', 'The Caribbean nation was presented with a certificate by Guinness World Records in recognition of the historic achievement, with Curaçao Football Federation President Gilbert Martina receiving the award.\r\n“We were delighted to present Gilbert Martina, the President of the Curaçao Football Federation, the certificate for the smallest country by population to qualify for the FIFA World Cup – 156,115 people,” Guinness World Records wrote on its verified X account.\r\nThe Caribbean nation was presented with a certificate by Guinness World Records in recognition of the historic achievement, with Curaçao Football Federation President Gilbert Martina receiving the award.\r\n\r\n\r\n“We were delighted to present Gilbert Martina, the President of the Curaçao Football Federation, the certificate for the smallest country by population to qualify for the FIFA World Cup – 156,115 people,” Guinness World Records wrote on its verified X account.\r\nThe Caribbean nation was presented with a certificate by Guinness World Records in recognition of the historic achievement, with Curaçao Football Federation President Gilbert Martina receiving the award.\r\n“We were delighted to present Gilbert Martina, the President of the Curaçao Football Federation, the certificate for the smallest country by population to qualify for the FIFA World Cup – 156,115 people,” Guinness World Records wrote on its verified X account.\r\nThe Caribbean nation was presented with a certificate by Guinness World Records in recognition of the historic achievement, with Curaçao Football Federation President Gilbert Martina receiving the award.\r\n\r\n\r\n“We were delighted to present Gilbert Martina, the President of the Curaçao Football Federation, the certificate for the smallest country by population to qualify for the FIFA World Cup – 156,115 people,” Guinness World Records wrote on its verified X account.\r\nThe Caribbean nation was presented with a certificate by Guinness World Records in recognition of the historic achievement, with Curaçao Football Federation President Gilbert Martina receiving the award.\r\n“We were delighted to present Gilbert Martina, the President of the Curaçao Football Federation, the certificate for the smallest country by population to qualify for the FIFA World Cup – 156,115 people,” Guinness World Records wrote on its verified X account.\r\nThe Caribbean nation was presented with a certificate by Guinness World Records in recognition of the historic achievement, with Curaçao Football Federation President Gilbert Martina receiving the award.\r\n“We were delighted to present Gilbert Martina, the President of the Curaçao Football Federation, the certificate for the smallest country by population to qualify for the FIFA World Cup – 156,115 people,” Guinness World Records wrote on its verified X account.\r\nThe Caribbean nation was presented with a certificate by Guinness World Records in recognition of the historic achievement, with Curaçao Football Federation President Gilbert Martina receiving the award.\r\n“We were delighted to present Gilbert Martina, the President of the Curaçao Football Federation, the certificate for the smallest country by population to qualify for the FIFA World Cup – 156,115 people,” Guinness World Records wrote on its verified X account.\r\nThe Caribbean nation was presented with a certificate by Guinness World Records in recognition of the historic achievement, with Curaçao Football Federation President Gilbert Martina receiving the award.\r\n\r\n\r\n\r\n“We were delighted to present Gilbert Martina, the President of the Curaçao Football Federation, the certificate for the smallest country by population to qualify for the FIFA World Cup – 156,115 people,” Guinness World Records wrote on its verified X account.\r\nThe Caribbean nation was presented with a certificate by Guinness World Records in recognition of the historic achievement, with Curaçao Football Federation President Gilbert Martina receiving the award.\r\n“We were delighted to present Gilbert Martina, the President of the Curaçao Football Federation, the certificate for the smallest country by population to qualify for the FIFA World Cup – 156,115 people,” Guinness World Records wrote on its verified X account.', 'posts/post_1781627133_6a3178fd69bbd.jpg', 'World Cup 2026: Curaçao receives Guinness World', 'World Cup 2026: Curaçao receives Guinness World', 1, 5, 'published', 'standard', 'public', NULL, 1, 1, 1, 1, 0, 10, 0, 4, '2026-06-16 16:25:33', NULL, NULL, 'World Cup 2026: Curaçao receives Guinness World', 'World Cup 2026: Curaçao receives Guinness World', 'World Cup 2026: Curaçao receives Guinness World', 'World Cup 2026: Curaçao receives Guinness World', 'World Cup 2026: Curaçao receives Guinness World', '', 'index,follow', 'BlogPosting', 'World Cup 2026: Curaçao receives Guinness World', 'World Cup 2026: Curaçao receives Guinness World', '', 'summary_large_image', 'World Cup 2026: Curaçao receives Guinness World', 'World Cup 2026: Curaçao receives Guinness World', '', 0, 0, 0.00, NULL, NULL, NULL, NULL, 648, '2026-06-16 16:25:33', '2026-06-16 18:24:56'),
(6, 'Thomas Tuchel can’t resign as England&#039;s coach after World Cup due to contract clause', 'thomas-tuchel-cant-resign-as-englands-coach-after-world-cup-due-to-contract-clause', 'The Football Association (FA) has insisted that Thomas Tuchel will remain England manager until at least Euro 2028, even if he leads the Three Lions to World Cup glory.', 'FA Chief Executive Mark Bullingham acknowledged that top clubs could attempt to lure the German coach away if England win the World Cup, but stressed that the FA expects Tuchel to honour his contract. “I think he could always get approaches. I’m sure his agent could get phone calls,” Bullingham said. “So, I think that’s a reality for any top performer in any walk of life. And I think if we were to win, we’d not be in a position where we would want anything other than him to coach us into 2028 and we’ve got him for that tournament. He’s very happy with us. We’re very happy with him. So, I don’t see it as a risk. But I’m not saying those approaches won’t happen because they could.”\r\n\r\nFA Chief Executive Mark Bullingham acknowledged that top clubs could attempt to lure the German coach away if England win the World Cup, but stressed that the FA expects Tuchel to honour his contract. “I think he could always get approaches. I’m sure his agent could get phone calls,” Bullingham said. “So, I think that’s a reality for any top performer in any walk of life. And I think if we were to win, we’d not be in a position where we would want anything other than him to coach us into 2028 and we’ve got him for that tournament. He’s very happy with us. We’re very happy with him. So, I don’t see it as a risk. But I’m not saying those approaches won’t happen because they could.”\r\n\r\n\r\nFA Chief Executive Mark Bullingham acknowledged that top clubs could attempt to lure the German coach away if England win the World Cup, but stressed that the FA expects Tuchel to honour his contract. “I think he could always get approaches. I’m sure his agent could get phone calls,” Bullingham said. “So, I think that’s a reality for any top performer in any walk of life. And I think if we were to win, we’d not be in a position where we would want anything other than him to coach us into 2028 and we’ve got him for that tournament. He’s very happy with us. We’re very happy with him. So, I don’t see it as a risk. But I’m not saying those approaches won’t happen because they could.”\r\n\r\nFA Chief Executive Mark Bullingham acknowledged that top clubs could attempt to lure the German coach away if England win the World Cup, but stressed that the FA expects Tuchel to honour his contract. “I think he could always get approaches. I’m sure his agent could get phone calls,” Bullingham said. “So, I think that’s a reality for any top performer in any walk of life. And I think if we were to win, we’d not be in a position where we would want anything other than him to coach us into 2028 and we’ve got him for that tournament. He’s very happy with us. We’re very happy with him. So, I don’t see it as a risk. But I’m not saying those approaches won’t happen because they could.”\r\nFA Chief Executive Mark Bullingham acknowledged that top clubs could attempt to lure the German coach away if England win the World Cup, but stressed that the FA expects Tuchel to honour his contract. “I think he could always get approaches. I’m sure his agent could get phone calls,” Bullingham said. “So, I think that’s a reality for any top performer in any walk of life. And I think if we were to win, we’d not be in a position where we would want anything other than him to coach us into 2028 and we’ve got him for that tournament. He’s very happy with us. We’re very happy with him. So, I don’t see it as a risk. But I’m not saying those approaches won’t happen because they could.”', 'posts/post_1781628039_6a317c8711e2c.jpg', 'FA Chief Executive Mark Bullingham', 'FA Chief Executive Mark Bullingham', 1, 3, 'published', 'standard', 'public', NULL, 1, 0, 1, 0, 0, 6, 0, 4, '2026-06-16 16:40:39', NULL, NULL, 'FA Chief Executive Mark Bullingham', 'FA Chief Executive Mark Bullingham acknowledged that top clubs could attempt to lure the German coach away if England win the World Cup, but stressed that the F', 'FA Chief Executive Mark Bullingham', 'FA Chief Executive Mark Bullingham', 'FA Chief Executive Mark Bullingham', '', 'index,follow', 'Article', '', '', '', 'summary', '', '', '', 0, 0, 0.00, NULL, NULL, NULL, NULL, 705, '2026-06-16 16:40:39', '2026-06-17 13:10:53'),
(7, 'Media personality Gbemi Olateru-Olagbegi reveals shocking question her brother asked all her male friends at her 30th birthday party', 'media-personality-gbemi-olateru-olagbegi-reveals-shocking-question-her-brother-asked-all-her-male-friends-at-her-30th-birthday-party', 'Gbemi Olateru-Olagbegi has shared a shocking question her brother asked her male friends at her 30th birthday party. \r\nShe wrote on X: \'Just remembered a moment from my 30th birthday dinner.  40 people  - family , friends , colleagues & they had gone on and on about how wonderful I am etc. ', 'Gbemi Olateru-Olagbegi has shared a shocking question her brother asked her male friends at her 30th birthday party. \r\n\r\nShe wrote on X: \'Just remembered a moment from my 30th birthday dinner.  40 people  - family , friends , colleagues & they had gone on and on about how wonderful I am etc. \r\n\r\n‘It’s my brothers turn to speak and he says “To all the male friends here who have said all these great things about her, SHAME ON YOU! Why haven’t you dated or married her then?“ ’\r\nOne X user asked Gbemi: \'How did you feel when he said that please?\' \r\n\r\nGbemi replied: \'I was shocked then amused.\' \r\n\r\nGbemi Olateru-Olagbegi has shared a shocking question her brother asked her male friends at her 30th birthday party. \r\n\r\nShe wrote on X: \'Just remembered a moment from my 30th birthday dinner.  40 people  - family , friends , colleagues & they had gone on and on about how wonderful I am etc. \r\n\r\n‘It’s my brothers turn to speak and he says “To all the male friends here who have said all these great things about her, SHAME ON YOU! Why haven’t you dated or married her then?“ ’\r\nOne X user asked Gbemi: \'How did you feel when he said that please?\' \r\n\r\nGbemi replied: \'I was shocked then amused.\' ', 'posts/post_1781630084_6a318484080f9.jpg', 'Media personality Gbemi Olateru', 'Media personality Gbemi Olateru-Olagbegi reveals shocking question her', 1, 4, 'published', 'gallery', 'public', NULL, 0, 1, 1, 1, 0, 2, 0, 2, '2026-06-16 17:14:44', NULL, NULL, 'Media personality Gbemi Olateru-Olagbegi reveals shocking question her', 'Media personality Gbemi Olateru-Olagbegi reveals shocking question her', 'Media personality Gbemi Olateru-Olagbegi reveals shocking question her', 'Media personality Gbemi Olateru', 'Media personality Gbemi Olateru-Olagbegi reveals shocking question her', '', 'index,follow', 'Article', '', '', '', 'summary', '', '', '', 0, 0, 0.00, NULL, NULL, NULL, NULL, 226, '2026-06-16 17:14:44', '2026-06-16 19:22:46'),
(8, 'Footballer Rafa Mir sentenced to 8.5 years in prison for sexual assault', 'footballer-rafa-mir-sentenced-to-85-years-in-prison-for-sexual-assault', 'Sevilla striker, Rafa Mir has been sentenced to eight-and-a-half years in prison after being convicted of sexual assault and bodily harm, the Valencian Community High Court of Justice said Monday.', '<p>Sevilla striker, Rafa Mir has been sentenced to eight-and-a-half years in prison after being convicted of sexual assault and bodily harm, the Valencian Community High Court of Justice said Monday. Mir, who was tried on May 28, was found guilty of \"one count of sexual assault and one count of bodily</p>\r\n<p>&nbsp;harm\" by the provincial court of Valencia, the high court said on the social media network X, noting the conviction could still be appealed. A second defendant, a friend of the footballer, was also convicted, and sentenced to two-and-a-half years in prison for a count of sexual assault and an offence \"against moral integrity\", in addition to a small fine for a minor offence of</p>\r\n<p>&nbsp;</p>\r\n<p>&nbsp;bodily harm. The court ordered Mir, who pleaded not guilty, to pay the victim 64,000 euros ($74,000) in compensation. \"The Fourth Section of the Valencia Court has sentenced the footballer of Elche CF, and formerly of Valencia CF, to eight and a half years in prison,\" a Valencia court announced</p>\r\n<p>&nbsp;on Monday. \"He was tried on 28 May on charges of sexual assault and causing bodily harm.\" Mir and his friend and fellow footballer Pablo Jara were both arrested in September 2024 after two women accused them of sexual assault at Mir\'s residence. Mir will also have to pay &euro;64,000 as compensation to the victim.</p>', 'posts/post_1781631309_6a31894dc66e5.jpg', 'Mir, who was tried on May 28, was found guilty of &amp;amp;quot;one count of sexual assault', 'Mir, who was tried on May 28, was found guilty of &amp;amp;quot;one count of sexual assault', 1, 3, 'published', 'standard', 'public', NULL, 1, 0, 1, 0, 0, 5, 0, 2, '2026-06-16 17:34:40', NULL, NULL, 'Mir, who was tried on May 28,', 'Mir, who was tried on May 28, was found guilty of &amp;amp;quot;one count of sexual assault', 'Mir, who was tried on May 28, was found guilty of &amp;amp;quot;one count of sexual assault', 'of &amp;amp;quot;one count of sexual assault', 'Mir, who was tried on May 28, was found guilty of &amp;amp;quot;one count of sexual assault', '', 'index,follow', 'TechArticle', '', '', '', 'summary', '', '', '', 0, 0, 0.00, NULL, NULL, NULL, NULL, 220, '2026-06-16 17:34:40', '2026-06-16 19:21:59');

-- --------------------------------------------------------

--
-- Table structure for table `post_meta`
--

CREATE TABLE `post_meta` (
  `id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `meta_key` varchar(100) NOT NULL,
  `meta_value` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_meta`
--

INSERT INTO `post_meta` (`id`, `post_id`, `meta_key`, `meta_value`, `created_at`) VALUES
(1, 2, 'vey', 'scwsc', '2026-06-16 14:43:17'),
(2, 2, 'sd', 'acscc', '2026-06-16 14:43:17'),
(3, 4, 'sd', 'acscc', '2026-06-16 16:12:41');

-- --------------------------------------------------------

--
-- Table structure for table `post_tags`
--

CREATE TABLE `post_tags` (
  `post_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_views`
--

CREATE TABLE `post_views` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet') DEFAULT 'desktop',
  `viewed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_views`
--

INSERT INTO `post_views` (`id`, `post_id`, `user_id`, `session_id`, `ip_address`, `user_agent`, `referrer`, `country`, `device_type`, `viewed_at`) VALUES
(1, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/', NULL, 'desktop', '2026-06-16 14:44:28'),
(2, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/a-discount-toner-cartridge-is-better-than-ever-1', NULL, 'desktop', '2026-06-16 14:45:35'),
(3, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/a-discount-toner-cartridge-is-better-than-ever-1', NULL, 'desktop', '2026-06-16 14:45:40'),
(4, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/', NULL, 'desktop', '2026-06-16 15:09:25'),
(5, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/', NULL, 'desktop', '2026-06-16 15:55:16'),
(6, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/admin/admin/?route=posts', NULL, 'desktop', '2026-06-16 15:56:27'),
(7, 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/', NULL, 'desktop', '2026-06-16 16:07:23'),
(8, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/', NULL, 'desktop', '2026-06-16 16:08:08'),
(9, 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/', NULL, 'desktop', '2026-06-16 16:13:09'),
(10, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/astronomy-binoculars-a-great-alternative', NULL, 'desktop', '2026-06-16 16:13:39'),
(11, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/', NULL, 'desktop', '2026-06-16 16:32:03'),
(12, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', 'http://localhost/trendypost/', NULL, 'mobile', '2026-06-16 16:32:30'),
(13, 5, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', 'http://localhost/trendypost/', NULL, 'mobile', '2026-06-16 16:34:05'),
(14, 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/', NULL, 'desktop', '2026-06-16 16:40:52'),
(15, 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/', NULL, 'desktop', '2026-06-16 16:41:24'),
(16, 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/', NULL, 'desktop', '2026-06-16 17:01:04'),
(17, 6, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', 'http://localhost/trendypost/', NULL, 'mobile', '2026-06-16 17:08:18'),
(18, 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/', NULL, 'desktop', '2026-06-16 17:15:04'),
(19, 6, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/media-personality-gbemi-olateru-olagbegi-reveals-shocking-question-her-brother-asked-all-her-male-friends-at-her-30th-birthday-party', NULL, 'desktop', '2026-06-16 17:15:55'),
(20, 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/thomas-tuchel-cant-resign-as-englands-coach-after-world-cup-due-to-contract-clause', NULL, 'desktop', '2026-06-16 17:16:03'),
(21, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/world-cup-2026-curaao-receives-guinness-world-record-plaque-as-smallest-country-to-qualify', NULL, 'desktop', '2026-06-16 17:16:14'),
(22, 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/', NULL, 'desktop', '2026-06-16 17:40:34'),
(23, 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/search?q=World+Cup+2026%3A+Cura%C3%A7ao+receives+Guinness', NULL, 'desktop', '2026-06-16 17:44:40'),
(24, 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/search?q=World+Cup+2026%3A+Cura%C3%A7ao+receives+Guinness', NULL, 'desktop', '2026-06-16 17:52:31'),
(25, 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/search?q=World+Cup+2026%3A+Cura%C3%A7ao+receives+Guinness', NULL, 'desktop', '2026-06-16 17:52:43'),
(26, 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/search?q=World+Cup+2026%3A+Cura%C3%A7ao+receives+Guinness', NULL, 'desktop', '2026-06-16 17:52:50'),
(27, 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/search?q=World+Cup+2026%3A+Cura%C3%A7ao+receives+Guinness', NULL, 'desktop', '2026-06-16 18:01:42'),
(28, 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/search?q=World+Cup+2026%3A+Cura%C3%A7ao+receives+Guinness', NULL, 'desktop', '2026-06-16 18:01:59'),
(30, 5, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/search?q=World+Cup+2026%3A+Cura%C3%A7ao+receives+Guinness', NULL, 'desktop', '2026-06-16 18:24:56'),
(31, 8, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', 'http://localhost/trendypost/', NULL, 'mobile', '2026-06-16 18:28:13'),
(32, 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/admin/admin/?route=posts', NULL, 'desktop', '2026-06-16 18:29:09'),
(33, 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/category/entertainments', NULL, 'desktop', '2026-06-16 18:32:23'),
(34, 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/', NULL, 'desktop', '2026-06-16 18:34:02'),
(35, 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/', NULL, 'desktop', '2026-06-16 18:37:52'),
(36, 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/help-finding-information-online-is-so-easy', NULL, 'desktop', '2026-06-16 18:38:35'),
(37, 3, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/help-finding-information-online-is-so-easy', NULL, 'desktop', '2026-06-16 18:38:55'),
(38, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/help-finding-information-online-is-so-easy', NULL, 'desktop', '2026-06-16 18:39:01'),
(39, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/a-discount-toner-cartridge-is-better-than-ever-1', NULL, 'desktop', '2026-06-16 18:39:12'),
(40, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/admin/admin/?route=comments', NULL, 'desktop', '2026-06-16 18:40:23'),
(41, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/a-discount-toner-cartridge-is-better-than-ever-1', NULL, 'desktop', '2026-06-16 18:40:35'),
(42, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/a-discount-toner-cartridge-is-better-than-ever-1', NULL, 'desktop', '2026-06-16 18:41:02'),
(43, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/a-discount-toner-cartridge-is-better-than-ever-1', NULL, 'desktop', '2026-06-16 18:41:20'),
(44, 2, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/a-discount-toner-cartridge-is-better-than-ever-', NULL, 'desktop', '2026-06-16 18:42:09'),
(45, 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/', NULL, 'desktop', '2026-06-16 18:50:01'),
(46, 8, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/', NULL, 'desktop', '2026-06-16 19:21:59'),
(47, 7, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', 'http://localhost/trendypost/', NULL, 'mobile', '2026-06-16 19:22:46'),
(48, 4, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', 'http://localhost/trendypost/post/media-personality-gbemi-olateru-olagbegi-reveals-shocking-question-her-brother-asked-all-her-male-friends-at-her-30th-birthday-party', NULL, 'mobile', '2026-06-16 19:23:05'),
(49, 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/media-personality-gbemi-olateru-olagbegi-reveals-shocking-question-her-brother-asked-all-her-male-friends-at-her-30th-birthday-party', NULL, 'desktop', '2026-06-16 19:26:36'),
(50, 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/media-personality-gbemi-olateru-olagbegi-reveals-shocking-question-her-brother-asked-all-her-male-friends-at-her-30th-birthday-party', NULL, 'desktop', '2026-06-16 19:28:22'),
(51, 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/media-personality-gbemi-olateru-olagbegi-reveals-shocking-question-her-brother-asked-all-her-male-friends-at-her-30th-birthday-party', NULL, 'desktop', '2026-06-16 19:29:45'),
(52, 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/media-personality-gbemi-olateru-olagbegi-reveals-shocking-question-her-brother-asked-all-her-male-friends-at-her-30th-birthday-party', NULL, 'desktop', '2026-06-16 19:29:58'),
(53, 4, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'http://localhost/trendypost/post/media-personality-gbemi-olateru-olagbegi-reveals-shocking-question-her-brother-asked-all-her-male-friends-at-her-30th-birthday-party', NULL, 'desktop', '2026-06-16 19:30:08'),
(54, 3, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', 'http://localhost/trendypost/post/astronomy-binoculars-a-great-alternative', NULL, 'mobile', '2026-06-16 19:30:23'),
(55, 6, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', 'http://localhost/trendypost/', NULL, 'mobile', '2026-06-17 13:09:44'),
(56, 6, NULL, NULL, '::1', 'Mozilla/5.0 (Linux; Android 8.0.0; SM-G955U Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36', 'http://localhost/trendypost/', NULL, 'mobile', '2026-06-17 13:10:53');

-- --------------------------------------------------------

--
-- Table structure for table `redirects`
--

CREATE TABLE `redirects` (
  `id` int(10) UNSIGNED NOT NULL,
  `old_url` varchar(500) NOT NULL,
  `new_url` varchar(500) NOT NULL,
  `type` enum('301','302','307') DEFAULT '301',
  `hits` int(10) UNSIGNED DEFAULT 0,
  `last_used` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `is_system` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `permissions`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'super_admin', 'Super Admin', 'Complete control over the entire platform', NULL, 1, '2026-06-16 13:02:00', '2026-06-16 13:02:00'),
(2, 'admin', 'Admin', 'Can manage most platform features', NULL, 1, '2026-06-16 13:02:00', '2026-06-16 13:02:00'),
(3, 'editor', 'Editor', 'Can review, edit and publish content', NULL, 1, '2026-06-16 13:02:00', '2026-06-16 13:02:00'),
(4, 'author', 'Author', 'Can create and manage own posts', NULL, 1, '2026-06-16 13:02:00', '2026-06-16 13:02:00'),
(5, 'contributor', 'Contributor', 'Can create posts for review only', NULL, 1, '2026-06-16 13:02:00', '2026-06-16 13:02:00'),
(6, 'subscriber', 'Subscriber', 'Registered user with commenting access', NULL, 1, '2026-06-16 13:02:00', '2026-06-16 13:02:00');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES
(1, 1, '2026-06-16 13:02:00'),
(1, 2, '2026-06-16 13:02:00'),
(1, 3, '2026-06-16 13:02:00'),
(1, 4, '2026-06-16 13:02:00'),
(1, 5, '2026-06-16 13:02:00'),
(1, 6, '2026-06-16 13:02:00'),
(1, 7, '2026-06-16 13:02:00'),
(1, 8, '2026-06-16 13:02:00'),
(1, 9, '2026-06-16 13:02:00'),
(1, 10, '2026-06-16 13:02:00'),
(1, 11, '2026-06-16 13:02:00'),
(1, 12, '2026-06-16 13:02:00'),
(1, 13, '2026-06-16 13:02:00'),
(1, 14, '2026-06-16 13:02:00'),
(1, 15, '2026-06-16 13:02:00'),
(1, 16, '2026-06-16 13:02:00'),
(1, 17, '2026-06-16 13:02:00'),
(1, 18, '2026-06-16 13:02:00'),
(1, 19, '2026-06-16 13:02:00'),
(1, 20, '2026-06-16 13:02:00'),
(1, 21, '2026-06-16 13:02:00'),
(1, 22, '2026-06-16 13:02:00'),
(1, 23, '2026-06-16 13:02:00'),
(1, 24, '2026-06-16 13:02:00'),
(1, 25, '2026-06-16 13:02:00'),
(1, 26, '2026-06-16 13:02:00'),
(2, 1, '2026-06-16 13:02:01'),
(2, 2, '2026-06-16 13:02:01'),
(2, 3, '2026-06-16 13:02:01'),
(2, 4, '2026-06-16 13:02:01'),
(2, 5, '2026-06-16 13:02:01'),
(2, 6, '2026-06-16 13:02:01'),
(2, 7, '2026-06-16 13:02:01'),
(2, 8, '2026-06-16 13:02:01'),
(2, 9, '2026-06-16 13:02:01'),
(2, 10, '2026-06-16 13:02:01'),
(2, 11, '2026-06-16 13:02:01'),
(2, 12, '2026-06-16 13:02:01'),
(2, 13, '2026-06-16 13:02:01'),
(2, 14, '2026-06-16 13:02:01'),
(2, 15, '2026-06-16 13:02:01'),
(2, 17, '2026-06-16 13:02:01'),
(2, 18, '2026-06-16 13:02:01'),
(2, 19, '2026-06-16 13:02:01'),
(2, 20, '2026-06-16 13:02:01'),
(2, 21, '2026-06-16 13:02:01'),
(2, 22, '2026-06-16 13:02:01'),
(2, 23, '2026-06-16 13:02:01'),
(2, 24, '2026-06-16 13:02:01'),
(2, 26, '2026-06-16 13:02:01'),
(3, 1, '2026-06-16 13:02:01'),
(3, 2, '2026-06-16 13:02:01'),
(3, 4, '2026-06-16 13:02:01'),
(3, 6, '2026-06-16 13:02:01'),
(3, 7, '2026-06-16 13:02:01'),
(3, 8, '2026-06-16 13:02:01'),
(3, 9, '2026-06-16 13:02:01'),
(3, 10, '2026-06-16 13:02:01'),
(3, 11, '2026-06-16 13:02:01'),
(3, 20, '2026-06-16 13:02:01'),
(3, 21, '2026-06-16 13:02:01'),
(4, 1, '2026-06-16 13:02:01'),
(4, 3, '2026-06-16 13:02:01'),
(4, 4, '2026-06-16 13:02:01'),
(4, 10, '2026-06-16 13:02:01'),
(4, 11, '2026-06-16 13:02:01'),
(5, 1, '2026-06-16 13:02:01'),
(5, 3, '2026-06-16 13:02:01'),
(5, 11, '2026-06-16 13:02:01');

-- --------------------------------------------------------

--
-- Table structure for table `seo_settings`
--

CREATE TABLE `seo_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `is_json` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `seo_settings`
--

INSERT INTO `seo_settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `is_json`, `created_at`, `updated_at`) VALUES
(1, 'google_verification', '', 'search_console', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(2, 'bing_verification', '', 'search_console', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(3, 'yandex_verification', '', 'search_console', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(4, 'baidu_verification', '', 'search_console', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(5, 'google_analytics_4', '', 'analytics', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(6, 'gtm_container_id', '', 'analytics', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(7, 'facebook_app_id', '', 'social', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(8, 'facebook_pixel', '', 'analytics', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(9, 'twitter_site', '@trendypost', 'social', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(10, 'structured_data_type', 'NewsMediaOrganization', 'schema', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(11, 'organization_name', 'TrendyPost', 'schema', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(12, 'organization_logo', '', 'schema', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(13, 'organization_phone', '', 'schema', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(14, 'organization_email', '', 'schema', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(15, 'organization_address', '', 'schema', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(16, 'sitemap_include_images', '1', 'sitemap', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(17, 'sitemap_include_videos', '1', 'sitemap', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(18, 'sitemap_include_news', '1', 'sitemap', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(19, 'sitemap_posts_limit', '1000', 'sitemap', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(20, 'sitemap_auto_ping', '1', 'sitemap', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(21, 'robots_txt', 'User-agent: *\nDisallow: /admin/\nDisallow: /api/\nDisallow: /install/\nAllow: /\n\nSitemap: /sitemap.xml', 'robots', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(22, 'auto_indexing', '0', 'indexing', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(23, 'indexing_api_key', '', 'indexing', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(24, 'breadcrumb_home', 'Home', 'breadcrumbs', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(25, 'breadcrumb_separator', '>', 'breadcrumbs', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(26, 'enable_amp', '0', 'amp', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(27, 'canonical_auto', '1', 'advanced', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(28, 'nofollow_external', '1', 'advanced', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(29, 'open_external_new_tab', '1', 'advanced', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(30, 'seo_score_threshold', '70', 'advanced', 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `is_public` tinyint(1) DEFAULT 0,
  `is_json` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `is_public`, `is_json`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'TrendyPost', 'general', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(2, 'site_tagline', 'Your Source for Trending News & Stories', 'general', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(3, 'site_logo', 'assets/images/logo.png', 'general', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(4, 'site_favicon', 'assets/images/favicon.ico', 'general', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(5, 'site_email', 'admin@trendypost.com', 'general', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(6, 'site_phone', '', 'general', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(7, 'site_address', '', 'general', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(8, 'footer_about', 'TrendyPost is your premier destination for trending news, insightful articles, and engaging stories across technology, lifestyle, business, and more.', 'general', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(9, 'footer_copyright', '© 2026 TrendyPost. All rights reserved.', 'general', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(10, 'posts_per_page', '10', 'content', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(11, 'posts_per_home', '6', 'content', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(12, 'excerpt_length', '200', 'content', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(13, 'show_author_box', '1', 'content', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(14, 'show_related_posts', '1', 'content', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(15, 'related_posts_count', '4', 'content', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(16, 'comment_moderation', '1', 'comments', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(17, 'allow_guest_comments', '1', 'comments', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(18, 'nested_comments', '3', 'comments', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(19, 'social_facebook', 'https://facebook.com/trendypost', 'social', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(20, 'social_twitter', 'https://twitter.com/trendypost', 'social', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(21, 'social_instagram', 'https://instagram.com/trendypost', 'social', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(22, 'social_youtube', '', 'social', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(23, 'social_linkedin', '', 'social', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(24, 'social_pinterest', '', 'social', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(25, 'social_tiktok', '', 'social', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(26, 'google_analytics_id', '', 'analytics', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(27, 'google_tag_manager', '', 'analytics', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(28, 'google_search_console', '', 'seo', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(29, 'google_adsense_id', '', 'monetization', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(30, 'adsterra_key', '', 'monetization', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(31, 'monetag_key', '', 'monetization', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(32, 'seo_default_title', 'TrendyPost - Trending News & Stories', 'seo', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(33, 'seo_default_description', 'Discover the latest trending news, insightful articles, and engaging stories at TrendyPost.', 'seo', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(34, 'seo_default_keywords', 'news, trending, blog, articles, stories', 'seo', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(35, 'seo_default_robots', 'index,follow', 'seo', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(36, 'seo_schema_org', '1', 'seo', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(37, 'seo_open_graph', '1', 'seo', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(38, 'seo_twitter_cards', '1', 'seo', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(39, 'seo_breadcrumbs', '1', 'seo', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(40, 'smtp_host', '', 'email', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(41, 'smtp_port', '587', 'email', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(42, 'smtp_username', '', 'email', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(43, 'smtp_password', '', 'email', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(44, 'smtp_encryption', 'tls', 'email', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(45, 'smtp_from_email', 'noreply@trendypost.com', 'email', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(46, 'smtp_from_name', 'TrendyPost', 'email', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(47, 'maintenance_mode', '0', 'system', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(48, 'registration_enabled', '1', 'system', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(49, 'cache_enabled', '1', 'system', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(50, 'cache_duration', '3600', 'system', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(51, 'gzip_enabled', '1', 'system', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(52, 'lazy_loading', '1', 'system', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(53, 'webp_conversion', '1', 'system', 0, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(54, 'dark_mode_enabled', '0', 'appearance', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(55, 'primary_color', '#00C853', 'appearance', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(56, 'secondary_color', '#1a1a1a', 'appearance', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(57, 'header_style', 'default', 'appearance', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(58, 'footer_style', 'default', 'appearance', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01'),
(59, 'homepage_layout', 'magazine', 'appearance', 1, 0, '2026-06-16 13:02:01', '2026-06-16 13:02:01');

-- --------------------------------------------------------

--
-- Table structure for table `site_analytics`
--

CREATE TABLE `site_analytics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `page_views` int(10) UNSIGNED DEFAULT 0,
  `unique_visitors` int(10) UNSIGNED DEFAULT 0,
  `sessions` int(10) UNSIGNED DEFAULT 0,
  `bounce_rate` decimal(5,2) DEFAULT 0.00,
  `avg_session_duration` int(10) UNSIGNED DEFAULT 0,
  `organic_traffic` int(10) UNSIGNED DEFAULT 0,
  `referral_traffic` int(10) UNSIGNED DEFAULT 0,
  `direct_traffic` int(10) UNSIGNED DEFAULT 0,
  `social_traffic` int(10) UNSIGNED DEFAULT 0,
  `new_visitors` int(10) UNSIGNED DEFAULT 0,
  `returning_visitors` int(10) UNSIGNED DEFAULT 0,
  `device_desktop` int(10) UNSIGNED DEFAULT 0,
  `device_mobile` int(10) UNSIGNED DEFAULT 0,
  `device_tablet` int(10) UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_analytics`
--

INSERT INTO `site_analytics` (`id`, `date`, `page_views`, `unique_visitors`, `sessions`, `bounce_rate`, `avg_session_duration`, `organic_traffic`, `referral_traffic`, `direct_traffic`, `social_traffic`, `new_visitors`, `returning_visitors`, `device_desktop`, `device_mobile`, `device_tablet`) VALUES
(1, '2026-06-16', 313, 1, 1, 0.00, 0, 0, 0, 0, 0, 0, 0, 292, 21, 0),
(2, '2026-06-17', 16, 1, 1, 0.00, 0, 0, 0, 0, 0, 0, 0, 13, 3, 0);

-- --------------------------------------------------------

--
-- Table structure for table `subscribers`
--

CREATE TABLE `subscribers` (
  `id` int(10) UNSIGNED NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscribers`
--

INSERT INTO `subscribers` (`id`, `email`, `first_name`, `last_name`, `status`, `source`, `ip_address`, `verified_at`, `unsubscribe_token`, `unsubscribed_at`, `created_at`, `updated_at`) VALUES
(1, 'royaloxygen5@gmail.com', '', '', 'active', 'website', '::1', NULL, 'eeb21360f97b40323b305b74427600e2c77ee0cab6e7ee993114390b170e7636', NULL, '2026-06-16 13:35:49', '2026-06-16 13:35:49');

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `seo_title` varchar(70) DEFAULT NULL,
  `seo_description` varchar(160) DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `meta_robots` varchar(50) DEFAULT 'index,follow',
  `og_image` varchar(255) DEFAULT NULL,
  `post_count` int(10) UNSIGNED DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `role_id` int(10) UNSIGNED NOT NULL DEFAULT 6,
  `status` enum('active','inactive','banned','pending') DEFAULT 'pending',
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verified_at` datetime DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `login_attempts` tinyint(3) UNSIGNED DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `google_id` varchar(100) DEFAULT NULL,
  `facebook_id` varchar(100) DEFAULT NULL,
  `twitter_id` varchar(100) DEFAULT NULL,
  `seo_title` varchar(70) DEFAULT NULL,
  `seo_description` varchar(160) DEFAULT NULL,
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `display_name`, `first_name`, `last_name`, `avatar`, `bio`, `role_id`, `status`, `email_verified`, `email_verified_at`, `remember_token`, `reset_token`, `reset_expires`, `last_login_at`, `last_login_ip`, `login_attempts`, `locked_until`, `google_id`, `facebook_id`, `twitter_id`, `seo_title`, `seo_description`, `social_links`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@trendypost.com', '$2y$10$BdRiyo22AP1SSfFlYxo1juayQ4uVn2rSwYpDgnvkKz82e3QGiAUPO', 'Super Admin', 'Super', 'Admin', NULL, NULL, 1, 'active', 1, NULL, NULL, NULL, NULL, '2026-06-17 13:05:55', '::1', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-16 13:02:02', '2026-06-17 13:05:55'),
(2, 'zamani', 'manage@gmail.com', '$2y$12$Ch3XOQR3OI13ewe.84voPOFq1AaDoM48Jk/bDAyohIezCq/A85zj.', 'salim manage', '', '', NULL, NULL, 2, 'active', 1, NULL, NULL, NULL, NULL, '2026-06-16 14:10:37', '::1', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-16 14:05:45', '2026-06-16 14:10:37'),
(3, 'zaks', 'zaks@gmail.com', '$2y$12$//jQlqi5bM1NFGbsAtoFcOW6i5qhzgv2BV1qqx2CW4tRz6m/yM1rm', 'zakari', '', '', NULL, NULL, 3, 'active', 1, NULL, NULL, NULL, NULL, '2026-06-16 14:12:10', '::1', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-16 14:11:45', '2026-06-16 14:12:10'),
(4, 'emmy', 'emmy@gmail.com', '$2y$12$yjf0MiMsI6l/g7dNKSBlB..2GAsCC8hFHqAl9XqV5UyCwak1.mKkK', 'emmy', '', '', NULL, NULL, 4, 'active', 1, NULL, NULL, NULL, NULL, '2026-06-16 14:14:36', '::1', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-16 14:14:03', '2026-06-16 14:14:36'),
(5, 'oxygen', 'oxygen@gmail.com', '$2y$12$zoZfEQ063orplm.ZaPYOmeHOOvSLeNQg8SxogPLPagPIF77OJPGb2', 'oxygen', '', '', NULL, NULL, 6, 'active', 1, NULL, NULL, NULL, NULL, '2026-06-16 15:55:51', '::1', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-16 14:16:33', '2026-06-16 15:55:51'),
(6, 'elkazy', 'elkazy@gmail.com', '$2y$12$/3eArkq/BKJGUjLq9Snuh.xrj7dnXib8IcLTrPyMEdKQJE7bwByoK', 'elkazy', '', '', NULL, NULL, 5, 'active', 1, NULL, NULL, NULL, NULL, '2026-06-17 13:08:01', '::1', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-17 13:07:32', '2026-06-17 13:08:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `advertisements`
--
ALTER TABLE `advertisements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_placement` (`placement_id`),
  ADD KEY `idx_network` (`network_id`),
  ADD KEY `idx_type` (`ad_type`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indexes for table `ad_networks`
--
ALTER TABLE `ad_networks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `ad_placements`
--
ALTER TABLE `ad_placements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_location` (`location`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `ad_statistics`
--
ALTER TABLE `ad_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ad_date_device` (`ad_id`,`date`,`device_type`),
  ADD KEY `idx_ad_date` (`ad_id`,`date`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `backups`
--
ALTER TABLE `backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_type` (`backup_type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_post` (`post_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_post_status` (`post_id`,`status`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `replied_by` (`replied_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`file_type`),
  ADD KEY `idx_filename` (`filename`),
  ADD KEY `idx_uploaded` (`uploaded_by`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_location` (`location`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_menu` (`menu_id`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `newsletters`
--
ALTER TABLE `newsletters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_scheduled` (`scheduled_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_menu` (`show_in_menu`,`menu_order`),
  ADD KEY `idx_homepage` (`is_homepage`);
ALTER TABLE `pages` ADD FULLTEXT KEY `ft_content` (`title`,`content`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_author` (`author_id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_featured` (`is_featured`),
  ADD KEY `idx_breaking` (`is_breaking`),
  ADD KEY `idx_published` (`published_at`),
  ADD KEY `idx_status_date` (`status`,`published_at`),
  ADD KEY `idx_type` (`post_type`),
  ADD KEY `idx_created` (`created_at`);
ALTER TABLE `posts` ADD FULLTEXT KEY `ft_title_content` (`title`,`content`,`excerpt`);

--
-- Indexes for table `post_meta`
--
ALTER TABLE `post_meta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_post_key` (`post_id`,`meta_key`),
  ADD KEY `idx_key` (`meta_key`);

--
-- Indexes for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD PRIMARY KEY (`post_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `post_views`
--
ALTER TABLE `post_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_post` (`post_id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_date` (`viewed_at`),
  ADD KEY `idx_post_date` (`post_id`,`viewed_at`);

--
-- Indexes for table `redirects`
--
ALTER TABLE `redirects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_old_url` (`old_url`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `seo_settings`
--
ALTER TABLE `seo_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_key` (`setting_key`),
  ADD KEY `idx_group` (`setting_group`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_key` (`setting_key`),
  ADD KEY `idx_group` (`setting_group`);

--
-- Indexes for table `site_analytics`
--
ALTER TABLE `site_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_date` (`date`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `subscribers`
--
ALTER TABLE `subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_role` (`role_id`),
  ADD KEY `idx_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `advertisements`
--
ALTER TABLE `advertisements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `ad_networks`
--
ALTER TABLE `ad_networks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ad_placements`
--
ALTER TABLE `ad_placements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `ad_statistics`
--
ALTER TABLE `ad_statistics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `backups`
--
ALTER TABLE `backups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `newsletters`
--
ALTER TABLE `newsletters`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `post_meta`
--
ALTER TABLE `post_meta`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `post_views`
--
ALTER TABLE `post_views`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `redirects`
--
ALTER TABLE `redirects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `seo_settings`
--
ALTER TABLE `seo_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `site_analytics`
--
ALTER TABLE `site_analytics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subscribers`
--
ALTER TABLE `subscribers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `advertisements`
--
ALTER TABLE `advertisements`
  ADD CONSTRAINT `advertisements_ibfk_1` FOREIGN KEY (`network_id`) REFERENCES `ad_networks` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `advertisements_ibfk_2` FOREIGN KEY (`placement_id`) REFERENCES `ad_placements` (`id`),
  ADD CONSTRAINT `advertisements_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ad_statistics`
--
ALTER TABLE `ad_statistics`
  ADD CONSTRAINT `ad_statistics_ibfk_1` FOREIGN KEY (`ad_id`) REFERENCES `advertisements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `backups`
--
ALTER TABLE `backups`
  ADD CONSTRAINT `backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD CONSTRAINT `contact_messages_ibfk_1` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `media_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_items_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `newsletters`
--
ALTER TABLE `newsletters`
  ADD CONSTRAINT `newsletters_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pages`
--
ALTER TABLE `pages`
  ADD CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `pages_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `pages` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `post_meta`
--
ALTER TABLE `post_meta`
  ADD CONSTRAINT `post_meta_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD CONSTRAINT `post_tags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_views`
--
ALTER TABLE `post_views`
  ADD CONSTRAINT `post_views_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `redirects`
--
ALTER TABLE `redirects`
  ADD CONSTRAINT `redirects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
