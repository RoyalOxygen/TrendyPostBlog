<?php
/**
 * TRENDYPOST - Search indexing cron
 * Drains the indexing queue with retries.
 * Run every 10 minutes:
 *   php cron/indexing.php
 * Example crontab:
 *   *\/10 * * * * php /path/to/trendypost/cron/indexing.php >/dev/null 2>&1
 */

define('TRENDYPOST', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'Database.php';
require_once INCLUDES_PATH . 'Helpers.php';
require_once INCLUDES_PATH . 'Seo.php';
require_once INCLUDES_PATH . 'Models.php';
require_once INCLUDES_PATH . 'SearchIndex.php';

$lockFile = (defined('CACHE_PATH') ? CACHE_PATH : sys_get_temp_dir() . '/') . 'indexing.lock';
$lockDir = dirname($lockFile);
if (!is_dir($lockDir)) {
    mkdir($lockDir, 0755, true);
}

$fp = fopen($lockFile, 'c');
if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
    exit(0);
}

try {
    $result = SearchIndex::processQueue(25);
    SearchIndex::pingSitemaps();
    if (PHP_SAPI === 'cli') {
        echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    }
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
} finally {
    flock($fp, LOCK_UN);
    fclose($fp);
}
