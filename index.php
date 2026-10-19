<?php
/**
 * TRENDYPOST - Front Controller
 * =============================
 * All frontend requests are routed through this file.
 * Clean SEO-friendly URLs are parsed and dispatched to the correct handlers.
 */

define('TRENDYPOST', true);
require_once __DIR__ . '/config/config.php';
require_once INCLUDES_PATH . 'Database.php';
require_once INCLUDES_PATH . 'Helpers.php';
require_once INCLUDES_PATH . 'Seo.php';
require_once INCLUDES_PATH . 'Media.php';
require_once INCLUDES_PATH . 'Auth.php';
require_once INCLUDES_PATH . 'Models.php';
require_once INCLUDES_PATH . 'Mailer.php';
require_once INCLUDES_PATH . 'SearchIndex.php';
require_once INCLUDES_PATH . 'GeminiClient.php';
require_once INCLUDES_PATH . 'PublishSeo.php';

// Start session and init auth
Auth::init();

// Parse the request URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Get the base path correctly - FOR ROOT DOMAIN
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($basePath === '' || $basePath === '/') {
    $basePath = '';
}

// Remove the base path from the URI
if ($basePath !== '') {
    $route = substr($uri, strlen($basePath));
    $route = ltrim($route, '/');
} else {
    $route = ltrim($uri, '/');
}

// Remove query string from route
$route = explode('?', $route)[0];

// Default homepage
if (empty($route) || $route === 'index.php') {
    $route = 'home';
}

// IMPORTANT: Check if this is an admin route - FIXED for root domain
if (str_starts_with($route, 'admin/') || str_starts_with($route, 'admin')) {
    // Parse admin route from query string
    $_GET['route'] = $_GET['route'] ?? 'dashboard';
    // Remove admin prefix from route
    $_GET['route'] = preg_replace('#^admin/?|^/admin/?#', '', $_GET['route']);
    // Include the admin index
    $adminFile = ADMIN_PATH . 'index.php';
    if (file_exists($adminFile)) {
        require_once $adminFile;
        exit;
    }
}

if (preg_match('/^[a-zA-Z0-9\-]{8,64}\.txt$/', $route)) {
    $_GET['key'] = $route;
    require PAGES_PATH . 'indexnow-key.php';
    exit;
}

// Track analytics - exclude api/auth/preview/feed/sitemap routes, plus admin, staff and bots
$untracked = ['api/', 'login', 'register', 'logout', 'forgot-password', 'reset-password', 'preview/', 'newsletter/', 'sitemap', 'robots.txt', 'feed'];
$shouldTrack = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';
if ($shouldTrack) {
    foreach ($untracked as $prefix) {
        if ($route === rtrim($prefix, '/') || str_starts_with($route, $prefix)) {
            $shouldTrack = false;
            break;
        }
    }
}
if ($shouldTrack) {
    track_page_view(page_view_key($route));
}

// Route dispatch
$router = new Router();

try {
    $router->dispatch($route);
} catch (Exception $e) {
    http_response_code(500);
    if (APP_DEBUG) {
        die('Error: ' . $e->getMessage());
    }
    // In production, show 500 page
    $errorPage = PAGES_PATH . 'errors/500.php';
    if (file_exists($errorPage)) {
        include $errorPage;
    } else {
        die('Internal Server Error');
    }
}

// ============================================================
// ROUTER CLASS
// ============================================================
class Router {
    private array $routes = [];
    private string $basePath;
    
    public function __construct() {
        $this->basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($this->basePath === '' || $this->basePath === '/') {
            $this->basePath = '';
        }
        $this->defineRoutes();
    }
    
    private function defineRoutes(): void {
        // Home
        $this->get('home', PAGES_PATH . 'home.php');
        
        // Posts
        $this->get('post/{slug}', PAGES_PATH . 'single-post.php');
        $this->get('preview/{id}', PAGES_PATH . 'preview.php');
        
        // Categories
        $this->get('category/{slug}', PAGES_PATH . 'category.php');
        $this->get('categories', PAGES_PATH . 'categories.php');
        
        // Tags
        $this->get('tag/{slug}', PAGES_PATH . 'tag.php');
        $this->get('tags', PAGES_PATH . 'tags.php');
        
        // Authors
        $this->get('author/{username}', PAGES_PATH . 'author.php');
        $this->get('authors', PAGES_PATH . 'authors.php');
        
        // Pages
        $this->get('page/{slug}', PAGES_PATH . 'page.php');
        
        // Archive
        $this->get('archive', PAGES_PATH . 'archive.php');
        $this->get('archive/{year}', PAGES_PATH . 'archive.php');
        $this->get('archive/{year}/{month}', PAGES_PATH . 'archive.php');
        
        // Search
        $this->get('search', PAGES_PATH . 'search.php');
        
        // Newsletter
        $this->get('newsletter/subscribe', PAGES_PATH . 'newsletter/subscribe.php');
        $this->post('newsletter/subscribe', PAGES_PATH . 'newsletter/subscribe.php');
        $this->get('newsletter/unsubscribe', PAGES_PATH . 'newsletter/unsubscribe.php');
        $this->post('newsletter/unsubscribe', PAGES_PATH . 'newsletter/unsubscribe.php');
        
        // Contact
        $this->get('contact', PAGES_PATH . 'contact.php');
        $this->post('contact', PAGES_PATH . 'contact.php');
        
        // Sitemap
        $this->get('sitemap.xml', PAGES_PATH . 'sitemap.php');
        $this->get('sitemap-{type}.xml', PAGES_PATH . 'sitemap.php');
        
        // Robots.txt
        $this->get('robots.txt', PAGES_PATH . 'robots.php');
        
        // RSS Feed
        $this->get('feed', PAGES_PATH . 'feed.php');
        $this->get('feed/rss', PAGES_PATH . 'feed.php');
        
        // API Routes
        $this->get('api/search', API_PATH . 'search.php');
        $this->post('api/search', API_PATH . 'search.php');
        $this->post('api/comment', API_PATH . 'comment.php');
        $this->get('api/posts', API_PATH . 'posts.php');
        $this->get('api/track-impression/{id}', API_PATH . 'track.php');
        $this->post('api/track-impression/{id}', API_PATH . 'track.php');
        $this->post('api/track-click/{id}', API_PATH . 'track.php');
        $this->post('api/newsletter/subscribe', API_PATH . 'newsletter.php');
        
        // Auth
        $this->get('login', PAGES_PATH . 'auth/login.php');
        $this->post('login', PAGES_PATH . 'auth/login.php');
        $this->get('register', PAGES_PATH . 'auth/register.php');
        $this->post('register', PAGES_PATH . 'auth/register.php');
        $this->get('logout', PAGES_PATH . 'auth/logout.php');
        $this->get('forgot-password', PAGES_PATH . 'auth/forgot.php');
        $this->post('forgot-password', PAGES_PATH . 'auth/forgot.php');
        $this->get('reset-password', PAGES_PATH . 'auth/reset.php');
        $this->post('reset-password', PAGES_PATH . 'auth/reset.php');
    }
    
    private function get(string $pattern, string $handler): void {
        $this->addRoute('GET', $pattern, $handler);
    }
    
    private function post(string $pattern, string $handler): void {
        $this->addRoute('POST', $pattern, $handler);
    }
    
    private function addRoute(string $method, string $pattern, string $handler): void {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'regex' => $this->patternToRegex($pattern)
        ];
    }
    
    private function patternToRegex(string $pattern): string {
        $regex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }
    
    public function dispatch(string $route): void {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // If route is empty, set to home
        if (empty($route)) {
            $route = 'home';
        }
        
        foreach ($this->routes as $routeDef) {
            if ($routeDef['method'] !== $method && $routeDef['method'] !== 'GET') continue;
            
            if (preg_match($routeDef['regex'], $route, $matches)) {
                array_shift($matches); // Remove full match
                
                // Set route parameters
                preg_match_all('/\{([^}]+)\}/', $routeDef['pattern'], $paramNames);
                $params = [];
                foreach ($paramNames[1] as $index => $name) {
                    $params[$name] = $matches[$index] ?? null;
                }
                $_GET = array_merge($_GET, $params);
                
                // Check if handler exists
                if (!file_exists($routeDef['handler'])) {
                    error_log('Handler not found: ' . $routeDef['handler']);
                    $this->notFound();
                    return;
                }
                
                // Include handler
                require_once $routeDef['handler'];
                return;
            }
        }
        
        // No route matched - 404
        $this->notFound();
    }
    
    private function notFound(): void {
        http_response_code(404);
        $errorPage = PAGES_PATH . 'errors/404.php';
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            echo '<h1>404 - Page Not Found</h1>';
            echo '<p>The page you are looking for does not exist.</p>';
        }
    }
}

/**
 * Map a frontend route to a stable analytics page key.
 */
function page_view_key(string $route): string {
    if ($route === '' || $route === 'home' || $route === 'index.php') {
        return 'home';
    }
    // Track articles individually (`post:{id}`) so per-author weekly and
    // monthly traffic can be attributed to the right writer. Slugs are too
    // long for the 100-char page_key column, so resolve the post id.
    if (preg_match('#^post/([^/]+)#', $route, $m)) {
        try {
            $row = Database::fetchOne("SELECT id FROM posts WHERE slug = ? LIMIT 1", [$m[1]]);
            if ($row) {
                return 'post:' . (int) $row['id'];
            }
        } catch (Throwable $e) {
            return 'post';
        }
        return 'other';
    }
    $segment = explode('/', $route)[0];
    $known = ['post', 'category', 'categories', 'tag', 'tags', 'author', 'authors', 'page', 'archive', 'search', 'contact', 'feed'];
    return in_array($segment, $known, true) ? $segment : 'other';
}

/**
 * Track a page view. Only real, non-staff visitors are counted. Unique
 * visitors are recorded per day, and page views are deduplicated per visitor
 * so the home page (and every other page) gets its own honest view count.
 */
function track_page_view(string $pageKey = 'other'): void {
    if (!is_trackable_visitor()) {
        return;
    }

    $today = date('Y-m-d');
    $visitor = visitor_hash();
    $device = device_type();
    $deviceColumn = 'device_' . $device;
    $sourceColumn = traffic_source() . '_traffic';

    try {
        $visitorStmt = Database::query(
            "INSERT IGNORE INTO daily_visitors (date, visitor_hash, is_new) VALUES (?, ?, 0)",
            [$today, $visitor]
        );
        $isNewVisitorToday = $visitorStmt->rowCount() > 0;

        $isReturning = false;
        if ($isNewVisitorToday) {
            $isReturning = (bool) Database::fetchOne(
                "SELECT 1 FROM daily_visitors WHERE visitor_hash = ? AND date < ? LIMIT 1",
                [$visitor, $today]
            );
            if ($isReturning) {
                Database::query(
                    "UPDATE daily_visitors SET is_new = 0 WHERE date = ? AND visitor_hash = ?",
                    [$today, $visitor]
                );
            }
        }

        Database::query(
            "INSERT INTO page_views (date, page_key, visitor_hash, hits) VALUES (?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE hits = hits + 1",
            [$today, $pageKey, $visitor]
        );

        $exists = Database::fetchOne("SELECT id FROM site_analytics WHERE date = ?", [$today]);
        if ($exists) {
            $sql = "UPDATE site_analytics SET page_views = page_views + 1,
                        {$deviceColumn} = {$deviceColumn} + 1,
                        {$sourceColumn} = {$sourceColumn} + 1";
            if ($isNewVisitorToday) {
                $sql .= ", unique_visitors = unique_visitors + 1, sessions = sessions + 1";
                $sql .= $isReturning ? ", returning_visitors = returning_visitors + 1" : ", new_visitors = new_visitors + 1";
            }
            $sql .= " WHERE date = ?";
            Database::query($sql, [$today]);
        } else {
            Database::insert('site_analytics', [
                'date' => $today,
                'page_views' => 1,
                'unique_visitors' => 1,
                'sessions' => 1,
                'new_visitors' => $isReturning ? 0 : 1,
                'returning_visitors' => $isReturning ? 1 : 0,
                $sourceColumn => 1,
                $deviceColumn => 1,
            ]);
        }
    } catch (Exception $e) {
        error_log("Analytics tracking error: " . $e->getMessage());
    }
}