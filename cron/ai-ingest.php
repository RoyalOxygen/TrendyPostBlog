<?php
/**
 * TRENDYPOST - AI ingest cron
 * Run every minute: php cron/ai-ingest.php
 * Example crontab: * * * * * php /path/to/trendypost/cron/ai-ingest.php >/dev/null 2>&1
 */

define('TRENDYPOST', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'Database.php';
require_once INCLUDES_PATH . 'Helpers.php';
require_once INCLUDES_PATH . 'Media.php';
require_once INCLUDES_PATH . 'Models.php';
require_once INCLUDES_PATH . 'AiIngest.php';

$lockFile = (defined('CACHE_PATH') ? CACHE_PATH : sys_get_temp_dir() . '/') . 'ai-ingest.lock';
$lockDir = dirname($lockFile);
if (!is_dir($lockDir)) {
    mkdir($lockDir, 0755, true);
}

$fp = fopen($lockFile, 'c');
if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
    exit(0);
}

try {
    $results = AiIngest::ingestDueSources();
    if (PHP_SAPI === 'cli') {
        echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;
    }
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
} finally {
    flock($fp, LOCK_UN);
    fclose($fp);
}
