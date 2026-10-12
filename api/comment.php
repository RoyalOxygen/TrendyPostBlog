<?php
/**
 * TRENDYPOST - Comment API
 * Handles comment submissions via AJAX
 */

if (!defined('TRENDYPOST')) {
    define('TRENDYPOST', true);
}

require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . 'Database.php';
require_once INCLUDES_PATH . 'Helpers.php';
require_once INCLUDES_PATH . 'Models.php';
require_once INCLUDES_PATH . 'Auth.php';
if (!class_exists('Mailer')) {
    require_once INCLUDES_PATH . 'Mailer.php';
}

ini_set('display_errors', '0');
if (ob_get_level() === 0) {
    ob_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Get POST data
$postData = json_decode(file_get_contents('php://input'), true);

// If no JSON data, try regular POST
if (empty($postData)) {
    $postData = $_POST;
}

if (!isset($postData['_token']) || !verify_csrf($postData['_token'])) {
    json_response(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit;
}

// Get comment data
$postId = intval($postData['post_id'] ?? 0);
$content = trim($postData['content'] ?? '');
$parentId = intval($postData['parent_id'] ?? 0);
$authorName = trim($postData['author_name'] ?? '');
$authorEmail = trim($postData['author_email'] ?? '');
$authorWebsite = trim($postData['author_website'] ?? '');

// Validate required fields
if (!$postId) {
    json_response(['success' => false, 'message' => 'Post ID is required.']);
    exit;
}

if (empty($content)) {
    json_response(['success' => false, 'message' => 'Comment content is required.']);
    exit;
}

// Check if post exists and allows comments
$post = Database::fetchOne("SELECT id, allow_comments FROM posts WHERE id = ? AND status = 'published'", [$postId]);
if (!$post) {
    json_response(['success' => false, 'message' => 'Post not found.']);
    exit;
}

if (!$post['allow_comments']) {
    json_response(['success' => false, 'message' => 'Comments are disabled for this post.']);
    exit;
}

// Check if user is logged in
$userId = null;
$displayName = '';
$userEmail = '';

if (Auth::check()) {
    $user = Auth::user();
    $userId = $user['id'];
    $displayName = $user['display_name'] ?? $user['username'] ?? 'User';
    $userEmail = $user['email'] ?? '';
} else {
    if (!(string) Setting::get('allow_guest_comments', '1')) {
        json_response(['success' => false, 'message' => 'Please log in to comment.']);
        exit;
    }
    if (empty($authorName)) {
        json_response(['success' => false, 'message' => 'Name is required for guest comments.']);
        exit;
    }
    if (empty($authorEmail) || !is_valid_email($authorEmail)) {
        json_response(['success' => false, 'message' => 'Valid email is required for guest comments.']);
        exit;
    }
    $displayName = $authorName;
    $userEmail = $authorEmail;
}

// Check if moderation is enabled
$moderation = Setting::get('comment_moderation', 1);
$status = $moderation ? 'pending' : 'approved';

// Prepare comment data
$commentData = [
    'post_id' => $postId,
    'parent_id' => $parentId ?: null,
    'user_id' => $userId,
    'author_name' => sanitize($displayName),
    'author_email' => sanitize($userEmail),
    'author_website' => sanitize($authorWebsite),
    'content' => sanitize($content),
    'author_ip' => client_ip(),
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'status' => $status
];

try {
    // Insert comment
    $commentId = Database::insert('comments', $commentData);
    
    if (!$commentId) {
        json_response(['success' => false, 'message' => 'Failed to save comment. Please try again.']);
        exit;
    }

    Database::query(
        "UPDATE posts SET comment_count = (SELECT COUNT(*) FROM comments WHERE post_id = ? AND status = 'approved') WHERE id = ?",
        [$postId, $postId]
    );

    $postRow = Database::fetchOne(
        "SELECT p.id, p.title, p.slug, u.email as author_email, u.display_name as author_name
         FROM posts p LEFT JOIN users u ON p.author_id = u.id WHERE p.id = ?",
        [$postId]
    );
    Auth::logActivity('comment', 'Comment on post #' . $postId . ' by ' . $displayName, $userId);

    $response = [
        'success' => true,
        'message' => $moderation ? 'Your comment is awaiting moderation.' : 'Comment posted successfully!',
        'comment' => [
            'id' => $commentId,
            'author_name' => htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'),
            'content' => nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')),
            'created_at' => 'Just now',
            'status' => $status
        ]
    ];

    if ($moderation) {
        $response['moderation'] = true;
    }

    try {
        if (class_exists('Mailer') && $postRow) {
            Mailer::sendCommentNotice($commentData + ['id' => $commentId], $postRow);
        }
    } catch (Throwable $mailError) {
        error_log('Comment notice failed: ' . $mailError->getMessage());
    }

    json_response($response);

} catch (Throwable $e) {
    error_log("Comment Error: " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'An error occurred while posting your comment. Please try again.'
    ], 500);
}