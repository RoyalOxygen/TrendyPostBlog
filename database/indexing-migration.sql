-- TrendyPost - search indexing queue migration
-- Adds the queue used by the indexing cron worker with retries/backoff.
-- Run once on an existing database:
--   mysql -u USER -p DB_NAME < database/indexing-migration.sql
-- Fresh installs already include this table via database/master_db.sql.

CREATE TABLE IF NOT EXISTS `indexing_queue` (
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
