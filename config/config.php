<?php
/**
 * TRENDYPOST - Main Configuration File
 * =====================================
 * Centralized configuration for the entire application.
 * All URLs are generated dynamically - NO HARDCODED URLS.
 */

// Prevent direct access
if (!defined('TRENDYPOST')) {
    define('TRENDYPOST', true);
}

$localConfig = __DIR__ . '/local.php';
if (is_file($localConfig)) {
    require_once $localConfig;
}

// ============================================================
// BASE URL CONFIGURATION - CENTRALIZED
// ============================================================
// Auto-detect base URL or set manually for production
$forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
$forwardedProto = explode(',', $forwardedProto)[0];
$httpsOn = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
    || $forwardedProto === 'https'
    || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] !== 'off');
$protocol = $httpsOn ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = dirname($scriptName);
$basePath = ($basePath == '/' || $basePath == '\\') ? '/' : $basePath . '/';
if (str_ends_with(rtrim($basePath, '/'), '/install') || rtrim($basePath, '/') === '/install') {
    $basePath = preg_replace('#/install/?$#', '/', $basePath) ?: '/';
}

if (getenv('TRENDYPOST_BASE_URL')) {
    define('BASE_URL', rtrim(getenv('TRENDYPOST_BASE_URL'), '/') . '/');
} elseif (($host ?? '') !== 'www.trendypost.com.ng' && ($host ?? '') !== 'trendypost.com.ng' && ($host ?? '') !== '') {
    define('BASE_URL', $protocol . $host . ($basePath === '/' ? '/' : $basePath));
} else {
    define('BASE_URL', 'https://www.trendypost.com.ng/');
}

// Clean base URL (no trailing slash for building paths)
// For root domain, this should be empty string
define('BASE_PATH', '');

// ============================================================
// DATABASE CONFIGURATION
// ============================================================
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1'));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'trendypost'));
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'trendypost'));
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($_ENV['DB_PASS'] ?? 'trendypost'));
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}
if (!defined('DB_COLLATE')) {
    define('DB_COLLATE', 'utf8mb4_unicode_ci');
}

// ============================================================
// APPLICATION SETTINGS
// ============================================================
define('APP_NAME', 'TrendyPost');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development'));
define('APP_DEBUG', APP_ENV !== 'production');
define('APP_TIMEZONE', $_ENV['APP_TIMEZONE'] ?? 'Africa/Lagos');
define('APP_LOCALE', $_ENV['APP_LOCALE'] ?? 'en');

// ============================================================
// SECURITY SETTINGS
// ============================================================
define('SESSION_NAME', 'trendypost_session');
define('SESSION_LIFETIME', 7200); // 2 hours
define('CSRF_TOKEN_NAME', '_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes
define('PASSWORD_MIN_LENGTH', 8);

// Encryption keys - CHANGE THESE IN PRODUCTION
if (!defined('ENCRYPTION_KEY')) {
    define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? 'trendypost_secret_key_change_in_production');
}
if (!defined('APP_KEY')) {
    define('APP_KEY', $_ENV['APP_KEY'] ?? 'base64:trendypost_app_key_change_this');
}

// ============================================================
// PATH DEFINITIONS
// ============================================================
define('ROOT_PATH', dirname(__DIR__) . '/');
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('ADMIN_PATH', ROOT_PATH . 'admin/');
define('PAGES_PATH', ROOT_PATH . 'pages/');
define('API_PATH', ROOT_PATH . 'api/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');
define('UPLOADS_PATH', ASSETS_PATH . 'uploads/');
define('CACHE_PATH', ROOT_PATH . 'cache/');
define('LOGS_PATH', ROOT_PATH . 'logs/');

// ============================================================
// URL HELPERS - FIXED FOR ROOT DOMAIN
// ============================================================
define('ASSETS_URL', BASE_URL . 'assets/');
define('CSS_URL', ASSETS_URL . 'css/');
define('JS_URL', ASSETS_URL . 'js/');
define('IMAGES_URL', ASSETS_URL . 'images/');
define('UPLOADS_URL', ASSETS_URL . 'uploads/');

// ADMIN_URL - Fixed for root domain
// Since you removed the /trendypost/ directory, admin is at /admin/
define('ADMIN_URL', BASE_URL . 'admin/');
define('ADMIN_ASSETS_URL', BASE_URL . 'admin/assets/');
define('API_URL', BASE_URL . 'api/');

// ============================================================
// SEO DEFAULTS
// ============================================================
define('SEO_DEFAULT_TITLE', 'TrendyPost - Trending News & Stories');
define('SEO_DEFAULT_DESCRIPTION', 'Discover the latest trending news, insightful articles, and engaging stories at TrendyPost.');
define('SEO_DEFAULT_KEYWORDS', 'news, trending, blog, articles, stories');
define('SEO_DEFAULT_IMAGE', IMAGES_URL . 'og-default.jpg');

// ============================================================
// CONTENT SETTINGS
// ============================================================
define('POSTS_PER_PAGE', 10);
define('EXCERPT_LENGTH', 200);
define('COMMENT_NESTING_LEVEL', 3);

// ============================================================
// ERROR REPORTING
// ============================================================
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
    ini_set('error_log', LOGS_PATH . 'php_errors.log');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LOGS_PATH . 'php_errors.log');
}

// ============================================================
// TIMEZONE & LOCALE
// ============================================================
date_default_timezone_set(APP_TIMEZONE);

// ============================================================
// SESSION CONFIGURATION
// ============================================================
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_secure', $httpsOn ? '1' : '0');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);