<?php
/**
 * TRENDYPOST - Admin Panel Router
 * ================================
 * All admin routes are handled here with RBAC protection.
 */

// Check if TRENDYPOST is already defined (when included from frontend)
if (!defined('TRENDYPOST')) {
    define('TRENDYPOST', true);
}
define('ADMIN', true);

// Only load dependencies if not already loaded
if (!defined('CONFIG_PATH')) {
    require_once __DIR__ . '/../config/config.php';
}
if (!class_exists('Installer')) {
    require_once INCLUDES_PATH . 'Installer.php';
}
if (!Installer::isInstalled()) {
    header('Location: ' . rtrim(BASE_URL, '/') . '/install/');
    exit;
}
if (!class_exists('Database')) {
    require_once INCLUDES_PATH . 'Database.php';
}
if (!function_exists('admin_url')) {
    require_once INCLUDES_PATH . 'Helpers.php';
}
if (!class_exists('Seo')) {
    require_once INCLUDES_PATH . 'Seo.php';
}
if (!class_exists('Media')) {
    require_once INCLUDES_PATH . 'Media.php';
}
if (!class_exists('Auth')) {
    require_once INCLUDES_PATH . 'Auth.php';
}
if (!class_exists('Post')) {
    require_once INCLUDES_PATH . 'Models.php';
}
if (!class_exists('GeminiClient')) {
    require_once INCLUDES_PATH . 'GeminiClient.php';
}
if (!class_exists('AiIngest')) {
    require_once INCLUDES_PATH . 'AiIngest.php';
}
if (!class_exists('PublishSeo')) {
    require_once INCLUDES_PATH . 'PublishSeo.php';
}
if (!class_exists('Mailer')) {
    require_once INCLUDES_PATH . 'Mailer.php';
}
if (!class_exists('SearchIndex')) {
    require_once INCLUDES_PATH . 'SearchIndex.php';
}

// Auth::init() is already called from frontend, but call it if this is accessed directly
if (session_status() === PHP_SESSION_NONE) {
    Auth::init();
}

// Check if logged in and has admin access
if (!Auth::check()) {
    header('Location: ' . url('login'));
    exit;
}

if (!Auth::isAdmin() && !Auth::isEditor()) {
    flash_set('error', 'You do not have access to the admin panel.');
    header('Location: ' . url());
    exit;
}

// Parse admin route - FIXED for root domain
$route = $_GET['route'] ?? 'dashboard';
// Remove any /admin/ prefix from the route
$route = preg_replace('#^admin/?|^/admin/?#', '', $route);
$action = $_GET['action'] ?? 'index';
$id = intval($_GET['id'] ?? 0);

// Route map
$routes = [
    'dashboard' => ADMIN_PATH . 'dashboard/index.php',
    'posts' => ADMIN_PATH . 'posts/index.php',
    'categories' => ADMIN_PATH . 'categories/index.php',
    'tags' => ADMIN_PATH . 'tags/index.php',
    'pages' => ADMIN_PATH . 'pages/index.php',
    'comments' => ADMIN_PATH . 'comments/index.php',
    'users' => ADMIN_PATH . 'users/index.php',
    'media' => ADMIN_PATH . 'media/index.php',
    'ads' => ADMIN_PATH . 'ads/index.php',
    'seo' => ADMIN_PATH . 'seo/index.php',
    'analytics' => ADMIN_PATH . 'analytics/index.php',
    'newsletters' => ADMIN_PATH . 'newsletters/index.php',
    'menus' => ADMIN_PATH . 'menus/index.php',
    'settings' => ADMIN_PATH . 'settings/index.php',
    'profile' => ADMIN_PATH . 'users/profile.php',
    'ai-sources' => ADMIN_PATH . 'ai-sources/index.php',
    'activity' => ADMIN_PATH . 'activity/index.php',
    'messages' => ADMIN_PATH . 'messages/index.php',
];

$handler = $routes[$route] ?? null;

if ($handler && file_exists($handler)) {
    include $handler;
} else {
    http_response_code(404);
    echo '<div class="container py-5"><div class="alert alert-danger">Admin page not found.</div></div>';
}