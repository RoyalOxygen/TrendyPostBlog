-- TrendyPost - analytics migration
-- Adds the tables used for real (unique) traffic counting.
-- Run once on an existing database:
--   mysql -u USER -p DB_NAME < database/analytics-migration.sql
-- Fresh installs already include these tables via database/master_db.sql.

CREATE TABLE IF NOT EXISTS `daily_visitors` (
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

CREATE TABLE IF NOT EXISTS `page_views` (
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
