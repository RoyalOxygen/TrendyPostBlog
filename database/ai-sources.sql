-- Extra AI ingest tables for existing TrendyPost databases.
-- Fresh installs already include this in master_db.sql / schema.sql.
-- MySQL-compatible: do not use ADD COLUMN IF NOT EXISTS (MariaDB-only).

CREATE TABLE IF NOT EXISTS ai_sources (
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

CREATE TABLE IF NOT EXISTS ai_source_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_id INT UNSIGNED DEFAULT NULL,
    message TEXT NOT NULL,
    level ENUM('info','warn','error') DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (source_id) REFERENCES ai_sources(id) ON DELETE SET NULL,
    INDEX idx_source (source_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE posts ADD COLUMN source_id INT UNSIGNED DEFAULT NULL;
ALTER TABLE posts ADD COLUMN original_url VARCHAR(500) DEFAULT NULL;
ALTER TABLE posts ADD COLUMN original_title VARCHAR(255) DEFAULT NULL;
ALTER TABLE posts ADD UNIQUE INDEX idx_original_url (original_url);
ALTER TABLE posts ADD CONSTRAINT fk_posts_source FOREIGN KEY (source_id) REFERENCES ai_sources(id) ON DELETE SET NULL;

INSERT IGNORE INTO permissions (name, display_name, module, description) VALUES
('ai.sources', 'Manage AI Sources', 'ai', 'Add source websites and run AI ingest into drafts');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE name = 'ai.sources';
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE name = 'ai.sources';
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE name = 'ai.sources';

INSERT IGNORE INTO settings (setting_key, setting_value, setting_group, is_public) VALUES
('user_llm_api_key', '', 'ai', 0),
('user_llm_base_url', 'https://generativelanguage.googleapis.com', 'ai', 0),
('user_llm_model', 'gemini-2.0-flash', 'ai', 0),
('gemini_api_keys', '', 'ai', 0),
('gemini_model', 'gemini-2.0-flash', 'ai', 0);

UPDATE settings SET setting_value = 'https://generativelanguage.googleapis.com' WHERE setting_key = 'user_llm_base_url' AND setting_value LIKE '%deepseek%';
UPDATE settings SET setting_value = 'gemini-2.0-flash' WHERE setting_key = 'user_llm_model' AND setting_value LIKE '%deepseek%';
